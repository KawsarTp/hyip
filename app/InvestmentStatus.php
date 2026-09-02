<?php

namespace App;

enum InvestmentStatus: string
{
    case Active = 'active';
    case Matured = 'matured';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
