<?php

namespace App\Exceptions;

use Exception;

class CreditLimitExceededException extends Exception
{
    public function __construct(float $attemptedTotal, float $availableCredit)
    {
        parent::__construct(
            "Sale of {$attemptedTotal} exceeds available credit of {$availableCredit} for this customer."
        );
    }
}
