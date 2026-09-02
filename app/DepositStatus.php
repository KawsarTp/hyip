<?php

namespace App;

enum DepositStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';
    case Rejected = 'rejected';
    case Refunded = 'refunded';
}
