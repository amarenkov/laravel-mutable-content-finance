<?php

namespace Amarenkov\MutableContentFinance\Tests\Feature;

use DomainException;

use Illuminate\Support\Facades\Event;

use Amarenkov\MutableContent\Models\Lov\Currency;
use Amarenkov\MutableContent\Models\Lov\Item;
use Amarenkov\MutableContent\ValueObjects\Money;

use Amarenkov\MutableContentFinance\Events\OperationCreated;
use Amarenkov\MutableContentFinance\Events\OperationDeleted;
use Amarenkov\MutableContentFinance\Events\OperationUpdated;
use Amarenkov\MutableContentFinance\Lovs\AccountType;
use Amarenkov\MutableContentFinance\Lovs\CategoryKind;
use Amarenkov\MutableContentFinance\Lovs\OperationKind;
use Amarenkov\MutableContentFinance\Models\Account;
use Amarenkov\MutableContentFinance\Models\Category;
use Amarenkov\MutableContentFinance\Models\Counterparty;
use Amarenkov\MutableContentFinance\Models\Operation;
use Amarenkov\MutableContentFinance\Services\Operations;

use Amarenkov\MutableContentFinance\Tests\TestCase;

class OperationsTest extends TestCase
{
    protected function makeAccount(string $label, string $currency = Currency::USD): Account
    {
        $account = new Account();
        $account->fill(['label' => $label, Account::FIELD_USER_ID => $this->user->id, Account::FIELD_TYPE => AccountType::CARD, Account::FIELD_CURRENCY => $currency]);
        $account->save();

        return $account;
    }

    protected function makeCategory(string $label): Category
    {
        $category = new Category();
        $category->fill(['label' => $label, Category::FIELD_USER_ID => $this->user->id, Category::FIELD_KIND => CategoryKind::EXPENSE]);
        $category->save();

        return $category;
    }

    protected function makeCounterparty(string $label, bool $business = false): Counterparty
    {
        $counterparty = new Counterparty();
        $counterparty->fill(['label' => $label, Counterparty::FIELD_USER_ID => $this->user->id, Counterparty::FIELD_IS_BUSINESS => $business ?: null]);
        $counterparty->save();

        return $counterparty;
    }

    protected function addCurrency(string $code): void
    {
        $item = new Item();
        $item->fill([Item::FIELD_LOV_ID => Currency::instance()->id, 'code' => $code, 'label' => $code, Currency::ITEM_FIELD_DECIMALS => 2]);
        $item->save();
    }

    public function test_balance_is_the_exact_sum_of_operations(): void
    {
        $card = $this->makeAccount('Card');

        Operations::new($card)->kind(OperationKind::INCOME)->amount('0.10')->at('2026-10-01 10:00')->save();
        Operations::new($card)->kind(OperationKind::INCOME)->amount('0.20')->at('2026-10-02 10:00')->save();
        Operations::new($card)->kind(OperationKind::EXPENSE)->amount(-0.05)->at('2026-10-03 10:00')->by($this->user)->save();

        $this->assertSame('0.25', $card->balance()->amount);
        $this->assertSame('0.3', $card->balance(new \DateTime('2026-10-02 12:00', new \DateTimeZone('UTC')))->amount);
        $this->assertSame('USD', $card->balance()->currency);
        $this->assertSame('0', $this->makeAccount('Empty')->balance()->amount);
    }

    public function test_operation_fields_and_business_flag_from_the_counterparty(): void
    {
        $card = $this->makeAccount('Card');
        $client = $this->makeCounterparty('Client', business: true);

        $operation = Operations::new($card)->kind(OperationKind::INCOME)->amount(1500)->counterparty($client)->comment('Invoice 6')->at('2026-01-27 00:00')->by($this->user)->save()->fresh();

        $this->assertSame(['amount' => 1500, 'currency' => 'USD'], $operation->amount);
        $this->assertTrue($operation->isBusiness());
        $this->assertSame('Invoice 6', $operation->comment);
        $this->assertSame($client->id, $operation->counterparty->id);
        $this->assertSame('2026-01-27T00:00:00Z', $operation->date);

        $private = Operations::new($card)->kind(OperationKind::INCOME)->amount(10)->counterparty($client)->business(false)->save();

        $this->assertFalse($private->isBusiness());
    }

    public function test_amount_must_be_in_the_account_currency(): void
    {
        $this->expectException(DomainException::class);

        Operations::new($this->makeAccount('Card'))->kind(OperationKind::EXPENSE)->amount(Money::of(-1, 'EUR'))->save();
    }

    public function test_sign_follows_the_kind(): void
    {
        $card = $this->makeAccount('Card');

        foreach ([[OperationKind::INCOME, -5], [OperationKind::EXPENSE, 5], [OperationKind::DEBT, 0], [OperationKind::REFUND, '0.00']] as [$kind, $amount]) {
            try {
                Operations::new($card)->kind($kind)->amount($amount)->save();
                $this->fail($kind.' with '.$amount.' must be rejected');
            } catch (DomainException) {
            }
        }

        $this->assertSame(0, $card->operations()->count());
    }

    public function test_refund_goes_back_in_the_categories_of_the_purchase(): void
    {
        $card = $this->makeAccount('Card');
        $food = $this->makeCategory('Food');
        $shop = $this->makeCounterparty('Shop');

        $purchase = Operations::new($card)->kind(OperationKind::EXPENSE)->amount(-30)->counterparty($shop)->split([[$food, -30]])->save();

        $refund = Operations::new($card)->refundOf($purchase)->amount(12)->split([[$food, 12]])->save()->fresh();

        $this->assertSame(OperationKind::REFUND, $refund->kind());
        $this->assertSame($shop->id, $refund->counterparty->id);
        $this->assertSame([$refund->id], $purchase->refunds()->pluck('id')->all());
        $this->assertSame('-18', $card->balance()->amount);

        $this->expectException(DomainException::class);

        Operations::new($card)->refundOf($purchase)->amount(-1)->save();
    }

    public function test_splits_add_up_to_the_amount(): void
    {
        $card = $this->makeAccount('Card');
        $food = $this->makeCategory('Food');
        $home = $this->makeCategory('Home');

        $operation = Operations::new($card)->kind(OperationKind::EXPENSE)->amount('-100.50')->split([[$food, '-70.35'], [$home, '-30.15']])->save();

        $this->assertSame(['-70.35', '-30.15'], $operation->splits()->orderBy('id')->get()->map(fn ($split) => $split->money()->amount)->all());
        $this->assertSame(1, $food->splits()->count());

        Operations::of($operation)->split([[$food, '-100.5']]);

        $this->assertSame(1, $operation->splits()->count());

        try {
            Operations::new($card)->kind(OperationKind::EXPENSE)->amount(-10)->split([[$food, -3]])->save();
            $this->fail('Splits not adding up must be rejected');
        } catch (DomainException) {
        }

        $this->expectException(DomainException::class);

        Operations::of($operation)->update([Operation::FIELD_AMOUNT => -99]);
    }

    public function test_update_changes_the_amount_with_its_splits(): void
    {
        Event::fake([OperationUpdated::class]);

        $operation = Operations::new($this->makeAccount('Card'))->kind(OperationKind::EXPENSE)->amount(-10)->split([[$this->makeCategory('Food'), -10]])->save();

        Operations::of($operation)->by($this->user)->update([Operation::FIELD_AMOUNT => -12, Operation::FIELD_COMMENT => 'More'], [[$this->makeCategory('Cafe'), -12]]);

        $this->assertSame('-12', $operation->fresh()->money()->amount);
        $this->assertSame('-12', $operation->splits()->first()->money()->amount);

        Event::assertDispatched(OperationUpdated::class, fn (OperationUpdated $event) => isset($event->changes->items['amount'], $event->changes->items['comment']) && $event->splitsChanged);
    }

    public function test_transfer_between_currencies(): void
    {
        $this->addCurrency('BYN');

        $usd = $this->makeAccount('Dollars');
        $byn = $this->makeAccount('Rubles', 'BYN');

        [$out, $in] = Operations::transfer($usd, $byn)->amount(100, 330)->at('2026-10-01 12:00')->comment('Exchange')->by($this->user)->save();

        $this->assertSame('-100', $usd->balance()->amount);
        $this->assertSame('330', $byn->balance()->amount);
        $this->assertSame($in->id, $out->fresh()->transfer->id);
        $this->assertSame($out->id, $in->fresh()->transfer->id);
        $this->assertSame(OperationKind::TRANSFER, $in->fresh()->kind());

        Operations::of($out->fresh())->update([Operation::FIELD_DATE => '2026-10-02 12:00']);

        $this->assertSame('2026-10-02T12:00:00Z', $in->fresh()->date);

        try {
            Operations::transfer($usd, $byn)->amount(100)->save();
            $this->fail('A transfer between currencies needs both amounts');
        } catch (DomainException) {
        }

        Operations::of($in->fresh())->delete();

        $this->assertSame('0', $usd->balance()->amount);
        $this->assertSame('0', $byn->balance()->amount);
    }

    public function test_debts_by_counterparty(): void
    {
        $this->addCurrency('BYN');

        $card = $this->makeAccount('Card');
        $cash = $this->makeAccount('Cash', 'BYN');
        $friend = $this->makeCounterparty('Friend');

        Operations::new($card)->kind(OperationKind::DEBT)->amount(-1000)->counterparty($friend)->save();
        Operations::new($card)->kind(OperationKind::DEBT)->amount(400)->counterparty($friend)->save();
        Operations::new($cash)->kind(OperationKind::DEBT)->amount(50)->counterparty($friend)->save();
        Operations::new($card)->kind(OperationKind::EXPENSE)->amount(-7)->counterparty($friend)->save();

        $debts = $friend->debts();

        $this->assertSame('600', $debts['USD']->amount);
        $this->assertSame('-50', $debts['BYN']->amount);
    }

    public function test_delete_removes_splits_and_receipts_and_events_are_dispatched(): void
    {
        Event::fake([OperationCreated::class, OperationDeleted::class]);

        $operation = Operations::new($this->makeAccount('Card'))
            ->kind(OperationKind::EXPENSE)
            ->amount(-10)
            ->split([[$this->makeCategory('Food'), -10]])
            ->receipt('t=20210307T120900&s=10.00&fn=1&i=2&fp=3&n=1')
            ->by($this->user)
            ->save();

        $this->assertSame(1, $operation->receipts()->count());

        Operations::of($operation)->by($this->user)->delete();

        $this->assertNull(Operation::find($operation->id));
        $this->assertSame(0, $operation->splits()->count());
        $this->assertSame(0, $operation->receipts()->count());

        Event::assertDispatched(OperationCreated::class, fn (OperationCreated $event) => $event->operationId === $operation->id && $event->userId === $this->user->id);
        Event::assertDispatched(OperationDeleted::class);
    }

    public function test_accounts_of_a_user(): void
    {
        $this->makeAccount('Card');

        $this->assertSame(['Card'], Account::query()->ownedBy($this->user)->get()->pluck('label')->all());
        $this->assertSame([], Account::query()->ownedBy($this->user->id + 1)->get()->pluck('label')->all());
    }
}
