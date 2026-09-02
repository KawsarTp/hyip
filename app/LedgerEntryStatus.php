<?php

namespace App;

enum LedgerEntryStatus: string
{
    case Posted = 'posted';
    case Reversed = 'reversed';
}
