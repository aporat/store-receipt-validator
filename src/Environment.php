<?php

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
     * Creates an Environment case from a string value.
     *
     * This factory method allows for robust creation of an Environment instance
     * from dynamic input, such as configuration files. It is case-insensitive.
     *
     * @param string $value The string representation of the environment (e.g., 'sandbox' or 'production').
     * @return self The corresponding Environment case.
     * @throws InvalidArgumentException If the provided string is not a valid environment.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower($value))
            ?? throw new InvalidArgumentException("Invalid environment: $value");
    }
}
