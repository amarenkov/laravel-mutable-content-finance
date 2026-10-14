<?php

return [
    'amount_zero' => 'An operation amount cannot be zero.',
    'income_negative' => 'Income must be positive.',
    'expense_positive' => 'Expense must be negative: money goes out. Money back is a refund.',
    'refund_same_sign' => 'A refund goes the other way than the refunded operation.',
    'currency_mismatch' => 'The amount is in :currency, the account is in :account.',
    'kind_and_amount_required' => 'An operation needs a kind and an amount.',
    'split_currency' => 'A split is in :currency, the operation is in :operation.',
    'split_sum' => 'The splits add up to :sum, the operation amount is :amount.',
    'transfer_same_account' => 'A transfer needs two different accounts.',
    'transfer_amount_required' => 'A transfer between currencies needs both amounts.',
];
