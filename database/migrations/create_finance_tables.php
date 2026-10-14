<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Amarenkov\MutableContentFinance\Models\Account;
use Amarenkov\MutableContentFinance\Models\Category;
use Amarenkov\MutableContentFinance\Models\Counterparty;
use Amarenkov\MutableContentFinance\Models\Operation;
use Amarenkov\MutableContentFinance\Models\Receipt;
use Amarenkov\MutableContentFinance\Models\Split;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createSchema('finance');

        Schema::createWithLog('finance.accounts', function (Blueprint $table) {
            $table->fieldsBase();
            $table->fieldsUpdatedAt();
            $table->softDeletes();

            $table->fieldExtract(Account::FIELD_USER_ID)->type('bigint');

            $table->index(Account::FIELD_USER_ID);
        });

        Schema::createWithLog('finance.categories', function (Blueprint $table) {
            $table->fieldsBase();
            $table->fieldsUpdatedAt();
            $table->softDeletes();

            $table->fieldExtract(Category::FIELD_USER_ID)->type('bigint');
            $table->fieldExtract(Category::FIELD_PARENT_ID)->type('bigint');

            $table->index(Category::FIELD_USER_ID);
            $table->index(Category::FIELD_PARENT_ID);
        });

        Schema::createWithLog('finance.counterparties', function (Blueprint $table) {
            $table->fieldsBase();
            $table->fieldsUpdatedAt();
            $table->softDeletes();

            $table->fieldExtract(Counterparty::FIELD_USER_ID)->type('bigint');

            $table->index(Counterparty::FIELD_USER_ID);
        });

        Schema::createWithLog('finance.operations', function (Blueprint $table) {
            $table->fieldsBase();
            $table->fieldsUpdatedAt();
            $table->softDeletes();

            $table->fieldExtract(Operation::FIELD_ACCOUNT_ID)->type('bigint');
            $table->fieldExtract(Operation::FIELD_DATE)->type('varchar(20)');
            $table->fieldExtract(Operation::FIELD_COUNTERPARTY_ID)->type('bigint');
            $table->fieldExtract(Operation::FIELD_TRANSFER_ID)->type('bigint');
            $table->fieldExtract(Operation::FIELD_REFUND_OF_ID)->type('bigint');

            $table->index([Operation::FIELD_ACCOUNT_ID, Operation::FIELD_DATE]);
            $table->index(Operation::FIELD_COUNTERPARTY_ID);
            $table->index(Operation::FIELD_TRANSFER_ID);
            $table->index(Operation::FIELD_REFUND_OF_ID);
        });

        Schema::createWithLog('finance.splits', function (Blueprint $table) {
            $table->fieldsBase();
            $table->fieldsUpdatedAt();
            $table->softDeletes();

            $table->fieldExtract(Split::FIELD_OPERATION_ID)->type('bigint');
            $table->fieldExtract(Split::FIELD_CATEGORY_ID)->type('bigint');

            $table->index(Split::FIELD_OPERATION_ID);
            $table->index(Split::FIELD_CATEGORY_ID);
        });

        Schema::createWithLog('finance.receipts', function (Blueprint $table) {
            $table->fieldsBase();
            $table->fieldsUpdatedAt();
            $table->softDeletes();

            $table->fieldExtract(Receipt::FIELD_OPERATION_ID)->type('bigint');

            $table->index(Receipt::FIELD_OPERATION_ID);
        });
    }
};
