<?php

namespace App;

enum AccountStatus: string
{
    case Active = 'active';
    case PendingVerification = 'pending_verification';
    case Restricted = 'restricted';
    case Frozen = 'frozen';
    case Closed = 'closed';
}
