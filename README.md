# amarenkov/laravel-mutable-content-finance

[![tests](https://github.com/amarenkov/laravel-mutable-content-finance/actions/workflows/tests.yml/badge.svg)](https://github.com/amarenkov/laravel-mutable-content-finance/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/amarenkov/laravel-mutable-content-finance)](https://packagist.org/packages/amarenkov/laravel-mutable-content-finance)
![coverage](https://img.shields.io/badge/coverage-90%25-brightgreen)

Personal finance domain for [`amarenkov/laravel-mutable-content`](https://github.com/amarenkov/laravel-mutable-content):
accounts, operations split by categories, transfers between accounts and currencies, debts and
receipts, with services and events to build any interface on.

> Work in progress. Nothing is released yet.

## Scope

- Models with mutable fields, so an application adds its own fields without migrations.
- Amounts are `money` fields of the core: an amount and a currency of the `currency` LOV, exact
  decimal arithmetic, exact sums in SQL.
- Services and events: creating and changing operations, transfers, splits. Every change lands in
  the core change log with its author.
- **No user interface.** Screens, bots and imports belong to the application.

## Model

| Model | What it is |
|---|---|
| `Account` | A wallet, a card, a bank account of a user; one currency; the balance is the sum of its operations |
| `Operation` | Money in or out of one account: kind, date, amount (negative when money goes out), counterparty, comment, business flag |
| `Split` | Part of an operation amount in a category; the parts add up to the amount |
| `Category` | What money is spent on or comes from; a tree, expense or income |
| `Counterparty` | Who money goes to or comes from; its business flag is the default of new operations |
| `Receipt` | The QR code of a receipt of an operation; `fiscal()` reads a Russian fiscal receipt |

Operation kinds (the `operation_kind` LOV): `income`, `expense`, `refund`, `transfer` and `debt`.
The sign follows the kind: income is positive, expense negative, a refund goes the other way than
the operation it refunds (`refund_of_id`), a transfer and a debt are not zero. A transfer is two
operations pointing at each other. A debt operation is money lent or borrowed, given back or
returned: its sign tells the direction, `Counterparty::debts()` sums them by currency.

## Requirements

- PHP 8.4 with bcmath
- Laravel 13
- PostgreSQL or MariaDB 10.7+, as required by `amarenkov/laravel-mutable-content`

## Installation

```bash
composer require amarenkov/laravel-mutable-content-finance
php artisan vendor:publish --tag=mutable-content-finance-migrations
php artisan migrate
php artisan db:seed --class="Amarenkov\MutableContent\Database\Seeders\LovsSeeder"
php artisan db:seed --class="Amarenkov\MutableContent\Database\Seeders\FieldsSeeder"
```

Add the currencies you use as items of the `currency` LOV of the core: it has only USD.

## Usage

```php
use Amarenkov\MutableContentFinance\Lovs\OperationKind;
use Amarenkov\MutableContentFinance\Services\Operations;

Operations::new($card)
    ->kind(OperationKind::EXPENSE)
    ->amount('-1250.50')                       // in the account currency
    ->at('2026-10-14 13:05')                   // display timezone; now by default
    ->counterparty($shop)
    ->split([[$food, '-1000'], [$home, '-250.50']])
    ->receipt($qrText)
    ->by($request->user())
    ->save();

Operations::new($card)->refundOf($purchase)->amount(250)->split([[$food, 250]])->save();

Operations::transfer($rubles, $dollars)->amount(9500, 100)->by($user)->save();

Operations::of($operation)->by($user)->update(['comment' => 'Groceries']);
Operations::of($operation)->by($user)->split([[$food, '-1250.50']]);
Operations::of($operation)->by($user)->delete();   // with its splits, receipts and transfer pair

$card->balance();            // Money
$card->balance($endOfMonth); // up to a moment
$friend->debts();            // ['USD' => Money, ...], positive when the friend owes
```

Wrong currencies, a sign not suiting the kind, splits not adding up and a transfer between currencies without both amounts throw
`DomainException`. Events are dispatched after the transaction commits: `OperationCreated`,
`OperationUpdated` (with the changed fields), `OperationDeleted`.

## Translations

Strings are in `lang/{en,ru}/finance.php` under the `mutable-content-finance` namespace. Publish
them to override:

```bash
php artisan vendor:publish --tag=mutable-content-finance-lang
```

## License

MIT. See [LICENSE](LICENSE).
