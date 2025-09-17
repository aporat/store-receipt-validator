<?php

declare(strict_types=1);

namespace ReceiptValidator;

use InvalidArgumentException;

/**
 * Represents the validation environment for store APIs.
 *
 * This enum provides type-safe values for sandbox and production endpoints,
 * preventing the use of arbitrary strings throughout the application.
 */
enum Environment: string
{
    /**
     * The sandbox or testing environment.
     *
     * Use this for validation during development and testing phases.
     */
    case SANDBOX = 'sandbox';

    /**
     * The live or production environment.
     *
     * Use this for validating receipts from a live application.
     */
    case PRODUCTION = 'production';

    /**
     * Creates an Environment case from a case-insensitive string value.
     *
     * @throws InvalidArgumentException
     */
    public static function fromString(string $value): self
    {
        return match (strtolower(trim($value))) {
            'sandbox'          => self::SANDBOX,
            'production', 'prod' => self::PRODUCTION,
            default              => throw new InvalidArgumentException("Invalid environment: $value"),
        };
    }
}
