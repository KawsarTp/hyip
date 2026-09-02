<?php

namespace App;

enum PaymentIntentStatus: string
{
    case Created = 'created';
    case RequiresAction = 'requires_action';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Canceled = 'canceled';
    case Expired = 'expired';
}
