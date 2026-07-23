<?php

namespace App\Exceptions;

use Exception;

class InvalidSplitPaymentException extends Exception
{
    public function __construct(float $paymentsSum, float $totalAmount)
    {
        parent::__construct(
            "Split payment amounts ({$paymentsSum}) must add up to the sale total ({$totalAmount})."
        );
    }
}
