<?php

namespace Amarenkov\MutableContentFinance\Models;

use DateTimeInterface;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;

use Amarenkov\MutableContent\Models\ModelWithFields;
use Amarenkov\MutableContent\Models\Lov\Currency;

use Amarenkov\MutableContent\ValueObjects\Money;

use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Field\Common\Label as CommonFieldLabel;
use Amarenkov\MutableContent\Attributes\Field\Common\Description as CommonFieldDescription;

use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Type as CFAType;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Label as CFALabel;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsRequired as CFAIsRequired;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsImmutable as CFAIsImmutable;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\LovCode as CFALovCode;

use Amarenkov\MutableContentFinance\Lovs\AccountType;
use Amarenkov\MutableContentFinance\Support\Sums;
use Amarenkov\MutableContentFinance\Support\Users;

/**
 * Where money is: a wallet, a card, a bank account. One currency; the balance is the sum of its operations.
 */
#[Table('finance.accounts')]
#[ClassLabel('Account')]
#[CommonFieldLabel]
#[CommonFieldDescription]
class Account extends ModelWithFields
{
    // const
    #[CFAType(Type::TYPE_INT), CFALabel('Owner'), CFAIsRequired, CFAIsImmutable]
    public const FIELD_USER_ID = 'user_id';

    #[CFAType(Type::TYPE_LOV_ITEM), CFALabel('Type'), CFAIsRequired, CFALovCode(AccountType::CLASS_CODE)]
    public const FIELD_TYPE = 'type';

    #[CFAType(Type::TYPE_LOV_ITEM), CFALabel('Currency'), CFAIsRequired, CFAIsImmutable, CFALovCode(Currency::CODE)]
    public const FIELD_CURRENCY = 'currency';

    #[CFAType(Type::TYPE_BOOL), CFALabel('Closed')]
    public const FIELD_IS_CLOSED = 'is_closed';

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
        return $this->hasMany(Operation::class, Operation::FIELD_ACCOUNT_ID);
    }

    public function currency(): string
    {
        return (string)$this->getField(self::FIELD_CURRENCY);
    }

    public function isClosed(): bool
    {
        return (bool)$this->getField(self::FIELD_IS_CLOSED);
    }

    /**
     * Sum of the operations, up to the moment when given.
     */
    public function balance(?DateTimeInterface $at = null): Money
    {
        $operations = $this->operations()->getQuery()
            ->when($at !== null, fn (Builder $query) => $query->whereField(Operation::FIELD_DATE, '<=', $at));

        return Money::of(Sums::amount($operations, Operation::FIELD_AMOUNT), $this->currency());
    }
}
