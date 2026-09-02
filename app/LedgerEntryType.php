<?php

namespace App;

enum LedgerEntryType: string
{
    case Deposit = 'deposit';
    case Transfer = 'transfer';
    case Withdrawal = 'withdrawal';
    case Return = 'return';
    case Referral = 'referral';
    case Fee = 'fee';
    case Adjustment = 'adjustment';
}
