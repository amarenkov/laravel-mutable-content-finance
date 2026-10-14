<?php

namespace Amarenkov\MutableContentFinance\Lovs;

use Amarenkov\MutableContent\Domain\Lov\Lov;

use Amarenkov\MutableContent\Attributes\Class\Code as ClassCode;
use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Lov\Item as LovItem;

#[ClassCode(OperationKind::CLASS_CODE)]
#[ClassLabel('Operation kind')]
#[LovItem(OperationKind::INCOME, 'Income')]
#[LovItem(OperationKind::EXPENSE, 'Expense')]
#[LovItem(OperationKind::REFUND, 'Refund')]
#[LovItem(OperationKind::TRANSFER, 'Transfer')]
#[LovItem(OperationKind::DEBT, 'Debt')]
class OperationKind extends Lov
{
    // const
    public const CLASS_CODE = 'operation_kind';

    public const INCOME = 'income';
    public const EXPENSE = 'expense';
    public const REFUND = 'refund';
    public const TRANSFER = 'transfer';
    public const DEBT = 'debt';
}
