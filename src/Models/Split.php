<?php

namespace Amarenkov\MutableContentFinance\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\ValueObjects\Money;

use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;

use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Type as CFAType;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Label as CFALabel;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsRequired as CFAIsRequired;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsImmutable as CFAIsImmutable;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\ObjectClass as CFAObjectClass;

/**
 * Part of an operation amount in a category; the parts of an operation add up to its amount.
 */
#[Table('finance.splits')]
#[ClassLabel('Split')]
class Split extends ModelWithFields
{
    // const
    #[CFAType(Type::TYPE_OBJECT), CFALabel('Operation'), CFAIsRequired, CFAIsImmutable, CFAObjectClass(Operation::class)]
    public const FIELD_OPERATION_ID = 'operation_id';

    #[CFAType(Type::TYPE_OBJECT), CFALabel('Category'), CFAIsRequired, CFAObjectClass(Category::class)]
    public const FIELD_CATEGORY_ID = 'category_id';

    #[CFAType(Type::TYPE_MONEY), CFALabel('Amount'), CFAIsRequired]
    public const FIELD_AMOUNT = 'amount';

    // static
    protected static array|bool|null $fieldDefinitions = null;

    // public
    /**
     * @return BelongsTo<Operation, $this>
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, self::FIELD_OPERATION_ID);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, self::FIELD_CATEGORY_ID);
    }

    public function money(): ?Money
    {
        return $this->getMoney(self::FIELD_AMOUNT);
    }
}
