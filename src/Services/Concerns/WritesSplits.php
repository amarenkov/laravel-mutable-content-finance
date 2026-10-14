<?php

namespace Amarenkov\MutableContentFinance\Services\Concerns;

use DomainException;

use Amarenkov\MutableContent\ValueObjects\Money;

use Amarenkov\MutableContentFinance\Models\Category;
use Amarenkov\MutableContentFinance\Models\Operation;
use Amarenkov\MutableContentFinance\Models\Split;

/**
 * Splits of an operation by categories: [[category, amount], ...], the amounts in the operation currency adding up to its amount.
 */
trait WritesSplits
{
    // protected
    /**
     * @param array<array{0: Category|int, 1: Money|int|float|string}> $splits
     * @return array<array{0: int, 1: Money}>
     */
    protected function normalizeSplits(array $splits, Money $amount): array
    {
        if ($splits === []) {
            return [];
        }

        $result = [];
        $sum = Money::zero($amount->currency);

        foreach ($splits as [$category, $splitAmount]) {
            $splitAmount = $splitAmount instanceof Money ? $splitAmount : Money::of($splitAmount, $amount->currency);

            if ($splitAmount->currency !== $amount->currency) {
                throw new DomainException(__('mutable-content-finance::finance.split_currency', ['currency' => $splitAmount->currency, 'operation' => $amount->currency]));
            }

            $sum = $sum->add($splitAmount);
            $result[] = [$category instanceof Category ? $category->id : $category, $splitAmount];
        }

        if (!$sum->equals($amount)) {
            throw new DomainException(__('mutable-content-finance::finance.split_sum', ['sum' => $sum->amount, 'amount' => $amount->amount]));
        }

        return $result;
    }

    /**
     * @param array<array{0: int, 1: Money}> $splits normalized
     */
    protected function writeSplits(Operation $operation, array $splits): void
    {
        foreach ($operation->splits()->get() as $split) {
            $this->prepareLog($split);
            $split->delete();
        }

        foreach ($splits as [$categoryId, $amount]) {
            $split = new Split();
            $split->fill([
                Split::FIELD_OPERATION_ID => $operation->id,
                Split::FIELD_CATEGORY_ID => $categoryId,
                Split::FIELD_AMOUNT => $amount,
            ]);
            $this->prepareLog($split);
            $split->save();
        }
    }
}
