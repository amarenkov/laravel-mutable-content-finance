<?php

namespace Amarenkov\MutableContentFinance;

use Illuminate\Support\ServiceProvider;

use Amarenkov\MutableContent\Domain\LovRegistry;
use Amarenkov\MutableContent\Domain\MutableClassRegistry;

use Amarenkov\MutableContentFinance\Lovs\AccountType;
use Amarenkov\MutableContentFinance\Lovs\CategoryKind;
use Amarenkov\MutableContentFinance\Lovs\OperationKind;
use Amarenkov\MutableContentFinance\Models\Account;
use Amarenkov\MutableContentFinance\Models\Category;
use Amarenkov\MutableContentFinance\Models\Counterparty;
use Amarenkov\MutableContentFinance\Models\Operation;
use Amarenkov\MutableContentFinance\Models\Receipt;
use Amarenkov\MutableContentFinance\Models\Split;

class MutableContentFinanceServiceProvider extends ServiceProvider
{
    // public
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mutable-content-finance.php', 'mutable-content-finance');

        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');

        $this->callAfterResolving(MutableClassRegistry::class, function (MutableClassRegistry $registry) {
            $registry
                ->add(Account::class)
                ->add(Category::class)
                ->add(Counterparty::class)
                ->add(Operation::class)
                ->add(Split::class)
                ->add(Receipt::class);
        });

        $this->callAfterResolving(LovRegistry::class, function (LovRegistry $registry) {
            $registry
                ->addClass(AccountType::class)
                ->addClass(OperationKind::class)
                ->addClass(CategoryKind::class);
        });
    }

    public function boot(): void
    {
        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'mutable-content-finance-migrations');

        $this->publishes([
            __DIR__.'/../config/mutable-content-finance.php' => config_path('mutable-content-finance.php'),
        ], 'mutable-content-finance-config');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'mutable-content-finance');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/mutable-content-finance'),
        ], 'mutable-content-finance-lang');
    }
}
