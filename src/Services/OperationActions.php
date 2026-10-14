<?php

namespace Amarenkov\MutableContentFinance\Services;

use Illuminate\Support\Facades\DB;

use Amarenkov\MutableContent\Domain\Log\Changes;

use Amarenkov\MutableContent\ValueObjects\Money;

use Amarenkov\MutableContentFinance\Events\OperationDeleted;
use Amarenkov\MutableContentFinance\Events\OperationUpdated;
use Amarenkov\MutableContentFinance\Models\Operation;
use Amarenkov\MutableContentFinance\Models\Receipt;
use Amarenkov\MutableContentFinance\Models\Split;
use Amarenkov\MutableContentFinance\Services\Concerns\ActsAs;
use Amarenkov\MutableContentFinance\Services\Concerns\WritesSplits;

/**
 * Changes of an operation. The date of a transfer changes on both sides; deleting a transfer deletes both.
 */
class OperationActions
{
    use ActsAs;
    use WritesSplits;

    // protected
    protected function amountOf(mixed $amount): mixed
    {
        return is_int($amount) || is_float($amount) || is_string($amount) ? Money::of($amount, $this->operation->account->currency()) : $amount;
    }

    protected function deleteOne(Operation $operation): void
    {
        foreach ([...$operation->splits()->get(), ...$operation->receipts()->get()] as $child) {
            $this->prepareLog($child);
            $child->delete();
        }

        $this->prepareLog($operation);
        $operation->delete();
    }

    // public
    public function __construct(
        protected Operation $operation
    )
    {
    }

    /**
     * Change fields; with splits given they replace the current ones, otherwise the current ones must still add up.
     *
     * @param ?array $splits [[category, amount], ...]
     */
    public function update(array $fields, ?array $splits = null): Operation
    {
        if (array_key_exists(Operation::FIELD_AMOUNT, $fields)) {
            $fields[Operation::FIELD_AMOUNT] = $this->amountOf($fields[Operation::FIELD_AMOUNT]);
        }

        return DB::transaction(function () use ($fields, $splits) {
            $operation = $this->operation;
            $before = $operation->fields?->toArray() ?? [];

            $operation->fill($fields);
            $this->prepareLog($operation);
            $operation->save();

            $changes = Changes::between($before, $operation->fields?->toArray() ?? []);

            $amount = $operation->money();

            if ($splits !== null) {
                $this->writeSplits($operation, $this->normalizeSplits($splits, $amount));
            } elseif (isset($changes->items[Operation::FIELD_AMOUNT]) && $operation->splits()->exists()) {
                $this->normalizeSplits($operation->splits()->get()->map(fn ($split) => [$split->getField(Split::FIELD_CATEGORY_ID), $split->money()])->all(), $amount);
            }

            $pair = $operation->transfer;

            if ($pair && isset($changes->items[Operation::FIELD_DATE])) {
                $pair->{Operation::FIELD_DATE} = $operation->{Operation::FIELD_DATE};
                $this->prepareLog($pair);
                $pair->save();
            }

            if (!$changes->isEmpty() || $splits !== null) {
                event(new OperationUpdated($operation->id, $this->userId, $changes, $splits !== null));
            }

            return $operation;
        });
    }

    /**
     * Replace the splits: [[category, amount], ...]; an empty list removes them.
     */
    public function split(array $splits): Operation
    {
        return $this->update([], $splits);
    }

    public function addReceipt(string $qr): Receipt
    {
        $receipt = new Receipt();
        $receipt->fill([Receipt::FIELD_OPERATION_ID => $this->operation->id, Receipt::FIELD_QR => $qr]);
        $this->prepareLog($receipt);
        $receipt->save();

        return $receipt;
    }

    public function delete(): void
    {
        DB::transaction(function () {
            $pair = $this->operation->transfer;

            $this->deleteOne($this->operation);

            if ($pair) {
                $this->deleteOne($pair);
            }

            event(new OperationDeleted($this->operation->id, $this->userId));

            if ($pair) {
                event(new OperationDeleted($pair->id, $this->userId));
            }
        });
    }
}
