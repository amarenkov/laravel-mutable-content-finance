<?php

namespace Amarenkov\MutableContentFinance\Models;

use DomainException;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\ValueObjects\Money;

use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;

use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Type as CFAType;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Label as CFALabel;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsRequired as CFAIsRequired;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsImmutable as CFAIsImmutable;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\LovCode as CFALovCode;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\ObjectClass as CFAObjectClass;

use Amarenkov\MutableContentFinance\Lovs\OperationKind;

/**
 * Money in or out of one account: the amount is in the account currency, negative when money goes out.
 * The sign follows the kind: income is positive, expense negative, a refund opposite to the refunded operation,
 * a transfer and a debt are not zero. A transfer is two operations pointing at each other; the amount may be split by categories.
 */
#[Table('finance.operations')]
#[ClassLabel('Operation')]
class Operation extends ModelWithFields
{
    // const
    #[CFAType(Type::TYPE_OBJECT), CFALabel('Account'), CFAIsRequired, CFAIsImmutable, CFAObjectClass(Account::class)]
    public const FIELD_ACCOUNT_ID = 'account_id';

    #[CFAType(Type::TYPE_DATETIME), CFALabel('Date'), CFAIsRequired]
    public const FIELD_DATE = 'date';

    #[CFAType(Type::TYPE_LOV_ITEM), CFALabel('Kind'), CFAIsRequired, CFALovCode(OperationKind::CLASS_CODE)]
    public const FIELD_KIND = 'kind';

    #[CFAType(Type::TYPE_MONEY), CFALabel('Amount'), CFAIsRequired]
    public const FIELD_AMOUNT = 'amount';

    #[CFAType(Type::TYPE_OBJECT), CFALabel('Counterparty'), CFAObjectClass(Counterparty::class)]
    public const FIELD_COUNTERPARTY_ID = 'counterparty_id';

    #[CFAType(Type::TYPE_STRING), CFALabel('Comment')]
    public const FIELD_COMMENT = 'comment';

    #[CFAType(Type::TYPE_BOOL), CFALabel('Business income')]
    public const FIELD_IS_BUSINESS = 'is_business';

    #[CFAType(Type::TYPE_OBJECT), CFALabel('Transfer pair'), CFAObjectClass(Operation::class)]
    public const FIELD_TRANSFER_ID = 'transfer_id';

    #[CFAType(Type::TYPE_OBJECT), CFALabel('Refunded operation'), CFAObjectClass(Operation::class)]
    public const FIELD_REFUND_OF_ID = 'refund_of_id';

    // static
    protected static array|bool|null $fieldDefinitions = null;

    protected static function booted(): void
    {
        static::saving(function (Operation $operation) {
            $amount = $operation->money();
            $account = $operation->account;

            if ($amount && $account && $amount->currency !== $account->currency()) {
                throw new DomainException(__('mutable-content-finance::finance.currency_mismatch', [
                    'currency' => $amount->currency,
                    'account' => $account->currency(),
                ]));
            }

            if ($amount && $error = $operation->signError($amount)) {
                throw new DomainException(__('mutable-content-finance::finance.'.$error));
            }
        });
    }

    // protected
    /**
     * Key of the message when the sign of the amount does not suit the kind, null when it does.
     */
    protected function signError(Money $amount): ?string
    {
        if ($amount->isZero()) {
            return 'amount_zero';
        }

        return match ($this->kind()) {
            OperationKind::INCOME => $amount->isNegative() ? 'income_negative' : null,
            OperationKind::EXPENSE => $amount->isPositive() ? 'expense_positive' : null,
            OperationKind::REFUND => ($refunded = $this->refundOf?->money()) && $refunded->isNegative() === $amount->isNegative() ? 'refund_same_sign' : null,
            default => null,
        };
    }

    // public
    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, self::FIELD_ACCOUNT_ID);
    }

    /**
     * @return BelongsTo<Counterparty, $this>
     */
    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, self::FIELD_COUNTERPARTY_ID);
    }

    /**
     * @return BelongsTo<Operation, $this>
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Operation::class, self::FIELD_TRANSFER_ID);
    }

    /**
     * @return BelongsTo<Operation, $this>
     */
    public function refundOf(): BelongsTo
    {
        return $this->belongsTo(Operation::class, self::FIELD_REFUND_OF_ID);
    }

    /**
     * @return HasMany<Operation, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Operation::class, self::FIELD_REFUND_OF_ID);
    }

    /**
     * @return HasMany<Split, $this>
     */
    public function splits(): HasMany
    {
        return $this->hasMany(Split::class, Split::FIELD_OPERATION_ID);
    }

    /**
     * @return HasMany<Receipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, Receipt::FIELD_OPERATION_ID);
    }

    public function money(): ?Money
    {
        return $this->getMoney(self::FIELD_AMOUNT);
    }

    public function kind(): string
    {
        return (string)$this->getField(self::FIELD_KIND);
    }

    public function isBusiness(): bool
    {
        return (bool)$this->getField(self::FIELD_IS_BUSINESS);
    }
}
