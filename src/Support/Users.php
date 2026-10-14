<?php

namespace Amarenkov\MutableContentFinance\Support;

/**
 * The application user model: accounts, categories and counterparties belong to a user.
 */
class Users
{
    // static
    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public static function model(): string
    {
        return config('mutable-content-finance.user_model') ?? config('auth.providers.users.model');
    }
}
