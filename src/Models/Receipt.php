<?php

namespace Amarenkov\MutableContentFinance\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;

use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Type as CFAType;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Label as CFALabel;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsRequired as CFAIsRequired;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsImmutable as CFAIsImmutable;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\ObjectClass as CFAObjectClass;

use Amarenkov\MutableContentFinance\Support\ReceiptQr;

/**
 * A receipt of an operation: the text of its QR code as scanned.
 */
#[Table('finance.receipts')]
#[ClassLabel('Receipt')]
class Receipt extends ModelWithFields
{
    // const
    #[CFAType(Type::TYPE_OBJECT), CFALabel('Operation'), CFAIsRequired, CFAIsImmutable, CFAObjectClass(Operation::class)]
    public const FIELD_OPERATION_ID = 'operation_id';

    #[CFAType(Type::TYPE_TEXT), CFALabel('QR code'), CFAIsRequired, CFAIsImmutable]
    public const FIELD_QR = 'qr';

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
     * The QR code read as a fiscal receipt; null if it is not one.
     */
    public function fiscal(): ?ReceiptQr
    {
        return ReceiptQr::parse((string)$this->getField(self::FIELD_QR));
    }
}
