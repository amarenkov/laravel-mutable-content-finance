<?php

namespace Amarenkov\MutableContentFinance\Lovs;

use Amarenkov\MutableContent\Domain\Lov\Lov;

use Amarenkov\MutableContent\Attributes\Class\Code as ClassCode;
use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Lov\Item as LovItem;

#[ClassCode(AccountType::CLASS_CODE)]
#[ClassLabel('Account type')]
#[LovItem(AccountType::CASH, 'Cash')]
#[LovItem(AccountType::CARD, 'Card')]
#[LovItem(AccountType::BANK, 'Bank account')]
#[LovItem(AccountType::DEPOSIT, 'Deposit')]
#[LovItem(AccountType::CREDIT, 'Credit')]
class AccountType extends Lov
{
    // const
    public const CLASS_CODE = 'account_type';

    public const CASH = 'cash';
    public const CARD = 'card';
    public const BANK = 'bank';
    public const DEPOSIT = 'deposit';
    public const CREDIT = 'credit';
}
