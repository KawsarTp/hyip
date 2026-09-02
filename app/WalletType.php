<?php

namespace App;

enum WalletType: string
{
    case Available = 'available';
    case Pending = 'pending';
    case Locked = 'locked';
    case Accrued = 'accrued';
    case Referral = 'referral';
}
