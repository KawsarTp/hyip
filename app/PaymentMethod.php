<?php

namespace App;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case PayPal = 'paypal';
    case Crypto = 'crypto';
    case Manual = 'manual';
}
