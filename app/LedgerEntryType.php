<?php

namespace App;

enum LedgerEntryType: string
{
    case Deposit = 'deposit';
    case Transfer = 'transfer';
    case Withdrawal = 'withdrawal';
    case Fee = 'fee';
    case Adjustment = 'adjustment';
}
