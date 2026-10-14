<?php

namespace Amarenkov\MutableContentFinance\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\ValueObjects\Money;

use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Field\Common\Label as CommonFieldLabel;

use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Type as CFAType;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Label as CFALabel;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsRequired as CFAIsRequired;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsImmutable as CFAIsImmutable;

use Amarenkov\MutableContentFinance\Lovs\OperationKind;
use Amarenkov\MutableContentFinance\Support\Users;

/**
 * Who money goes to or comes from: a shop, a client, a person. Debts are counted by counterparty.
 */
#[Table('finance.counterparties')]
#[ClassLabel('Counterparty')]
#[CommonFieldLabel]
class Counterparty extends ModelWithFields
{
    // const
    #[CFAType(Type::TYPE_INT), CFALabel('Owner'), CFAIsRequired, CFAIsImmutable]
    public const FIELD_USER_ID = 'user_id';

    /**
     * Default of the business flag of a new operation with the counterparty.
     */
    #[CFAType(Type::TYPE_BOOL), CFALabel('Business income')]
    public const FIELD_IS_BUSINESS = 'is_business';

    // static
    protected static array|bool|null $fieldDefinitions = null;

    // public
    public function scopeOwnedBy(Builder $query, Authenticatable|int $user): Builder
    {
        return $query->whereField(self::FIELD_USER_ID, $user instanceof Authenticatable ? $user->getAuthIdentifier() : $user);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Users::model(), self::FIELD_USER_ID);
    }

    /**
     * @return HasMany<Operation, $this>
     */
    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class, Operation::FIELD_COUNTERPARTY_ID);
    }

    public function isBusiness(): bool
    {
        return (bool)$this->getField(self::FIELD_IS_BUSINESS);
    }

    /**
     * Debt by currency from the debt operations: positive when the counterparty owes, negative when it is owed.
     *
     * @return array<string, Money>
     */
    public function debts(): array
    {
        $result = [];

        foreach ($this->operations()->whereField(Operation::FIELD_KIND, OperationKind::DEBT)->get() as $operation) {
            $money = $operation->money();

            if ($money) {
                $result[$money->currency] = ($result[$money->currency] ?? Money::zero($money->currency))->subtract($money);
            }
        }

        return array_filter($result, fn (Money $debt) => !$debt->isZero());
    }
}
