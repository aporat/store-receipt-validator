<?php

namespace ReceiptValidator;

use InvalidArgumentException;

enum Environment
{
    case SANDBOX;
    case PRODUCTION;

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'sandbox' => self::SANDBOX,
            'production' => self::PRODUCTION,
            default => throw new InvalidArgumentException("Invalid environment: {$value}"),
        };
    }
}
