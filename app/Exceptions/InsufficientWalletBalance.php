<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientWalletBalance extends RuntimeException
{
    public function __construct(string $currency)
    {
        parent::__construct("Insufficient {$currency} wallet balance.");
    }
}
