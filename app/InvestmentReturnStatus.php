<?php

namespace App;

enum InvestmentReturnStatus: string
{
    case Accrued = 'accrued';
    case Approved = 'approved';
    case Released = 'released';
    case Reversed = 'reversed';
}
