<?php

namespace Amarenkov\MutableContentFinance\Services;

use DateTimeInterface;
use DomainException;

use Illuminate\Support\Facades\DB;

use Amarenkov\MutableContent\ValueObjects\Money;

use Amarenkov\MutableContentFinance\Events\OperationCreated;
use Amarenkov\MutableContentFinance\Lovs\OperationKind;
use Amarenkov\MutableContentFinance\Models\Account;
use Amarenkov\MutableContentFinance\Models\Operation;
use Amarenkov\MutableContentFinance\Services\Concerns\ActsAs;

/**
 * Money moved between two accounts: an outgoing and an incoming operation pointing at each other.
 * Between currencies both amounts are given.
 */
class NewTransfer
{
    use ActsAs;

    // protected
    protected ?Money $out = null;

    protected ?Money $in = null;

    protected DateTimeInterface|string|null $date = null;

    protected ?string $comment = null;

    protected function make(Account $account, Money $amount, DateTimeInterface|string $date): Operation
    {
        $operation = new Operation();
        $operation->fill([
            Operation::FIELD_ACCOUNT_ID => $account->id,
            Operation::FIELD_KIND => OperationKind::TRANSFER,
            Operation::FIELD_AMOUNT => $amount,
            Operation::FIELD_DATE => $date,
            Operation::FIELD_COMMENT => $this->comment,
        ]);
        $this->prepareLog($operation);
        $operation->save();

        return $operation;
    }

    // public
    public function __construct(
        protected Account $from,
        protected Account $to
    )
    {
    }

    /**
     * Amount leaving the source account and, for another currency, arriving at the target one; both positive.
     */
    public function amount(Money|int|float|string $out, Money|int|float|string|null $in = null): static
    {
        $this->out = ($out instanceof Money ? $out : Money::of($out, $this->from->currency()))->abs();

        if ($in !== null) {
            $this->in = ($in instanceof Money ? $in : Money::of($in, $this->to->currency()))->abs();
        } elseif ($this->from->currency() === $this->to->currency()) {
            $this->in = $this->out;
        }

        return $this;
    }

    public function at(DateTimeInterface|string|null $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function comment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return array{0: Operation, 1: Operation} the outgoing and the incoming operation
     */
    public function save(): array
    {
        if ($this->from->id === $this->to->id) {
            throw new DomainException(__('mutable-content-finance::finance.transfer_same_account'));
        }

        if ($this->out === null || $this->in === null) {
            throw new DomainException(__('mutable-content-finance::finance.transfer_amount_required'));
        }

        return DB::transaction(function () {
            $date = $this->date ?? now();

            $out = $this->make($this->from, $this->out->negate(), $date);
            $in = $this->make($this->to, $this->in, $date);

            foreach ([[$out, $in], [$in, $out]] as [$operation, $pair]) {
                $operation->{Operation::FIELD_TRANSFER_ID} = $pair->id;
                $this->prepareLog($operation);
                $operation->save();
            }

            event(new OperationCreated($out->id, $this->userId));
            event(new OperationCreated($in->id, $this->userId));

            return [$out, $in];
        });
    }
}
