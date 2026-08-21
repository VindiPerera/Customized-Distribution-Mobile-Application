<?php

namespace App\Exceptions;

use Exception;

class InvalidReturnException extends Exception
{
    public static function tooMany(string $productName, int $requested, int $available): self
    {
        return new self(
            "Cannot return {$requested} x {$productName} — only {$available} remain returnable from that sale."
        );
    }

    public static function notOwnedByCustomer(string $productName): self
    {
        return new self(
            "Cannot return {$productName} — it wasn't purchased by this customer."
        );
    }
}
