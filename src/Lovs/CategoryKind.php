<?php

namespace Amarenkov\MutableContentFinance\Lovs;

use Amarenkov\MutableContent\Domain\Lov\Lov;

use Amarenkov\MutableContent\Attributes\Class\Code as ClassCode;
use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Lov\Item as LovItem;

#[ClassCode(CategoryKind::CLASS_CODE)]
#[ClassLabel('Category kind')]
#[LovItem(CategoryKind::EXPENSE, 'Expense')]
#[LovItem(CategoryKind::INCOME, 'Income')]
class CategoryKind extends Lov
{
    // const
    public const CLASS_CODE = 'category_kind';

    public const EXPENSE = 'expense';
    public const INCOME = 'income';
}
