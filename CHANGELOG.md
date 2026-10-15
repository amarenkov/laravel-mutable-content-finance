# Changelog

All notable changes to this package are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this package adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Requires core ^0.7.7: money arithmetic no longer needs the bcmath extension, `ext-bcmath` is not required.

## [0.1.0] - 2026-10-15

### Added

- `Account` (a user's wallet, card or bank account, the `account_type` LOV, one currency of the core `currency` LOV, `balance()` up to a moment), `Operation` (kind of the `operation_kind` LOV: income, expense, refund, transfer, debt, the sign of the amount following the kind; date, `money` amount in the account currency, counterparty, comment, business flag, transfer pair, refunded operation), `Split` (part of the amount in a category), `Category` (a tree, the `category_kind` LOV), `Counterparty` (business flag as the default of operations, `debts()` by currency), `Receipt` (QR code text, `fiscal()`).
- Services `Operations::new($account)` (`refundOf()` for a refund), `Operations::transfer($from, $to)` (between currencies with both amounts) and `Operations::of($operation)` (update with splits, split, add a receipt, delete with splits, receipts and the transfer pair); the user given to `by()` is the author of the change log entry.
- `Support\ReceiptQr`: a Russian fiscal receipt QR code (time in the display timezone, total, fn, fd, fp, type, `key()`); `Support\Sums`: exact SQL sums of money fields on PostgreSQL and MariaDB.
- Events dispatched after commit: `OperationCreated`, `OperationUpdated`, `OperationDeleted`.
- Migration in the `finance` schema (publish tag `mutable-content-finance-migrations`), LOVs and classes registered in the core registries, Russian labels.

[Unreleased]: https://github.com/amarenkov/laravel-mutable-content-finance/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/amarenkov/laravel-mutable-content-finance/releases/tag/v0.1.0
