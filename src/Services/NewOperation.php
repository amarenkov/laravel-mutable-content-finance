<?php

namespace Amarenkov\MutableContentFinance\Services;

use DateTimeInterface;
use DomainException;

use Illuminate\Support\Facades\DB;

use Amarenkov\MutableContent\ValueObjects\Money;

use Amarenkov\MutableContentFinance\Events\OperationCreated;
use Amarenkov\MutableContentFinance\Lovs\OperationKind;
use Amarenkov\MutableContentFinance\Models\Account;
use Amarenkov\MutableContentFinance\Models\Counterparty;
use Amarenkov\MutableContentFinance\Models\Operation;
use Amarenkov\MutableContentFinance\Models\Receipt;
use Amarenkov\MutableContentFinance\Services\Concerns\ActsAs;
use Amarenkov\MutableContentFinance\Services\Concerns\WritesSplits;

/**
 * An operation being created on an account. The business flag follows the counterparty unless set.
 */
class NewOperation
{
    use ActsAs;
    use WritesSplits;

    // protected
    protected ?string $kind = null;

    protected ?Money $amount = null;

    protected DateTimeInterface|string|null $date = null;

    protected ?Counterparty $counterparty = null;

    protected ?Operation $refundOf = null;

    protected ?string $comment = null;

    protected ?bool $business = null;

    protected array $splits = [];

    /** @var array<string> */
    protected array $receipts = [];

    protected array $fields = [];

    // public
    public function __construct(
        protected Account $account
    )
    {
    }

    public function kind(string $kind): static
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Amount in the account currency, negative when money goes out.
     */
    public function amount(Money|int|float|string $amount): static
    {
        $this->amount = $amount instanceof Money ? $amount : Money::of($amount, $this->account->currency());

        return $this;
    }

    /**
     * When it happened; now by default. A string is in the display timezone.
     */
    public function at(DateTimeInterface|string|null $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function counterparty(?Counterparty $counterparty): static
    {
        $this->counterparty = $counterparty;

        return $this;
    }

    /**
     * A refund of the operation: the kind becomes refund, the counterparty is taken from it unless set.
     */
    public function refundOf(Operation $operation): static
    {
        $this->refundOf = $operation;
        $this->kind ??= OperationKind::REFUND;

        return $this;
    }

    public function comment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function business(?bool $business = true): static
    {
        $this->business = $business;

        return $this;
    }

    /**
     * Amount by categories: [[category, amount], ...], adding up to the operation amount.
     */
    public function split(array $splits): static
    {
        $this->splits = $splits;

        return $this;
    }

    /**
     * The text of a receipt QR code; may be called several times.
     */
    public function receipt(string $qr): static
    {
        $this->receipts[] = $qr;

        return $this;
    }

    /**
     * Other fields of the operation, the ones added by the application among them.
     */
    public function fields(array $fields): static
    {
        $this->fields = $fields + $this->fields;

        return $this;
    }

    public function save(): Operation
    {
        if ($this->refundOf && !$this->counterparty) {
            $this->counterparty = $this->refundOf->counterparty;
        }

        if ($this->kind === null || $this->amount === null) {
            throw new DomainException(__('mutable-content-finance::finance.kind_and_amount_required'));
        }

        $splits = $this->normalizeSplits($this->splits, $this->amount);

        return DB::transaction(function () use ($splits) {
            $operation = new Operation();
            $operation->fill([
                Operation::FIELD_ACCOUNT_ID => $this->account->id,
                Operation::FIELD_KIND => $this->kind,
                Operation::FIELD_AMOUNT => $this->amount,
                Operation::FIELD_DATE => $this->date ?? now(),
                Operation::FIELD_COUNTERPARTY_ID => $this->counterparty?->id,
                Operation::FIELD_REFUND_OF_ID => $this->refundOf?->id,
                Operation::FIELD_COMMENT => $this->comment,
                Operation::FIELD_IS_BUSINESS => ($this->business ?? $this->counterparty?->isBusiness()) ?: null,
            ] + $this->fields);
            $this->prepareLog($operation);
            $operation->save();

            $this->writeSplits($operation, $splits);

            foreach ($this->receipts as $qr) {
                $receipt = new Receipt();
                $receipt->fill([Receipt::FIELD_OPERATION_ID => $operation->id, Receipt::FIELD_QR => $qr]);
                $this->prepareLog($receipt);
                $receipt->save();
            }

            event(new OperationCreated($operation->id, $this->userId));

            return $operation;
        });
    }
}
