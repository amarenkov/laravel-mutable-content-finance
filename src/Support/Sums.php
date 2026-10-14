<?php

namespace Amarenkov\MutableContentFinance\Support;

use Illuminate\Database\Eloquent\Builder;

use Amarenkov\MutableContent\Helpers\DatabaseHelper;

use Amarenkov\MutableContent\ValueObjects\Money;

/**
 * Exact sums of money fields in SQL: the amount is a JSON number, summed as a decimal.
 */
class Sums
{
    // static
    /**
     * Sum of the amounts of a money field over the query, as a decimal string ("0" for no rows).
     */
    public static function amount(Builder $query, string $field): string
    {
        $model = $query->getModel();

        $expression = DatabaseHelper::isMariaDb($model->getConnection())
            ? "cast(json_value(".$query->getQuery()->getGrammar()->wrap($model->qualifyColumn('fields')).", '$.".$field.".".Money::KEY_AMOUNT."') as decimal(30, 10))"
            : '(('.$query->getQuery()->getGrammar()->wrap($model->qualifyColumn('fields')).")->'".$field."'->>'".Money::KEY_AMOUNT."')::numeric";

        $sum = $query->toBase()->cloneWithout(['columns', 'orders', 'limit', 'offset'])->selectRaw('sum('.$expression.') as aggregate')->value('aggregate');

        return Money::toDecimalString((string)($sum ?? '0'));
    }
}
