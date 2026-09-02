<?php

namespace App;

enum ReferralCommissionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Released = 'released';
    case Held = 'held';
    case ClawedBack = 'clawed_back';
}
