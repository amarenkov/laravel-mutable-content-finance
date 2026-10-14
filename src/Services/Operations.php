<?php

namespace Amarenkov\MutableContentFinance\Services;

use Amarenkov\MutableContentFinance\Models\Account;
use Amarenkov\MutableContentFinance\Models\Operation;

/**
 * Entry point of the operation services: Operations::new($account)->...->save(),
 * Operations::transfer($from, $to)->...->save(), Operations::of($operation)->...->update().
 */
class Operations
{
    // static
    public static function new(Account $account): NewOperation
    {
        return new NewOperation($account);
    }

    public static function transfer(Account $from, Account $to): NewTransfer
    {
        return new NewTransfer($from, $to);
    }

    public static function of(Operation $operation): OperationActions
    {
        return new OperationActions($operation);
    }
}
