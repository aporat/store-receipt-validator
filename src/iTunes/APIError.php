<?php

namespace ReceiptValidator\iTunes;

/**
 * Represents status codes from the legacy iTunes verifyReceipt endpoint.
 *
 * This enum provides a type-safe way to handle specific status codes returned
 * by the iTunes API, encapsulating the integer code and its human-readable message.
 *
 * @deprecated since version 2.0. Use {@see \ReceiptValidator\AppleAppStore\APIError} instead.
 *             Apple has deprecated the verifyReceipt endpoint in favor of the App Store Server API.
 * @see https://developer.apple.com/documentation/appstorereceipts/status
 * @see https://developer.apple.com/documentation/appstoreserverapi
 */
enum APIError: int
{
    /**
     * The receipt is valid.
     */
    case VALID = 0;

    /**
     * The App Store could not read the JSON object you provided.
     */
    case JSON_INVALID = 21000;

    /**
     * The data in the receipt-data property was malformed or missing.
     */
    case RECEIPT_DATA_MALFORMED = 21002;

    /**
     * The receipt could not be authenticated.
     */
    case RECEIPT_AUTHENTICATION_FAILED = 21003;

    /**
     * The shared secret you provided does not match the shared secret on file for your account.
     */
    case SHARED_SECRET_INVALID = 21004;

    /**
     * The receipt server is not currently available.
     */
    case SERVER_UNAVAILABLE = 21005;

    /**
     * This receipt is valid but the subscription has expired.
     */
    case SUBSCRIPTION_EXPIRED = 21006;

    /**
     * This receipt is from the test environment, but it was sent to the production environment.
     */
    case SANDBOX_RECEIPT_ON_PRODUCTION = 21007;

    /**
     * This receipt is from the production environment, but it was sent to the test environment.
     */
    case PRODUCTION_RECEIPT_ON_SANDBOX = 21008;

    /**
     * Internal data access error.
     */
    case INTERNAL_DATA_ACCESS_ERROR = 21009;

    /**
     * The user account cannot be found or has been deleted.
     */
    case USER_ACCOUNT_NOT_FOUND = 21010;

    /**
     * An internal server error occurred (within a range of 21100-21199).
     */
    case INTERNAL_ERROR = 21100;

    /**
     * Returns a human-readable description for the error case.
     *
     * @return string
     */
    public function message(): string
    {
        return match ($this) {
            self::VALID                         => 'The receipt is valid.',
            self::JSON_INVALID                  => 'The App Store could not read the JSON object you provided.',
            self::RECEIPT_DATA_MALFORMED        => 'The data in the receipt-data property was malformed.',
            self::RECEIPT_AUTHENTICATION_FAILED => 'The receipt could not be authenticated.',
            self::SHARED_SECRET_INVALID         => 'The shared secret you provided does not match the shared secret on file for your account.',
            self::SERVER_UNAVAILABLE            => 'The receipt server is not currently available.',
            self::SUBSCRIPTION_EXPIRED          => 'This receipt is valid but the subscription has expired.',
            self::SANDBOX_RECEIPT_ON_PRODUCTION => 'This receipt is from the test environment, but it was sent to the production environment.',
            self::PRODUCTION_RECEIPT_ON_SANDBOX => 'This receipt is from the production environment, but it was sent to the test environment.',
            self::INTERNAL_DATA_ACCESS_ERROR    => 'Internal data access error.',
            self::USER_ACCOUNT_NOT_FOUND        => 'The user account cannot be found or has been deleted.',
            self::INTERNAL_ERROR                => 'An internal server error occurred.',
        };
    }

    /**
     * Safely creates an APIError case from an int value, or null if unknown.
     *
     * @param int $value
     * @return self|null
     */
    public static function fromInt(int $value): ?self
    {
        return self::tryFrom($value);
    }
}
