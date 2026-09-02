<?php

namespace App;

enum LedgerAccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Revenue = 'revenue';
    case Expense = 'expense';
    case Equity = 'equity';
}
