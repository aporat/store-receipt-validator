<?php

namespace ReceiptValidator\iTunes;

/**
 * iTunes status codes returned during receipt validation.
 *
 * @see https://developer.apple.com/documentation/appstorereceipts/status
 */
final class APIError
{
    /**
     * The receipt is valid.
     */
    public const int VALID = 0;

    /**
     * The App Store could not read the JSON object you provided.
     */
    public const int JSON_INVALID = 21000;

    /**
     * The data in the receipt-data property was malformed or missing.
     */
    public const int RECEIPT_DATA_MALFORMED = 21002;

    /**
     * The receipt could not be authenticated.
     */
    public const int RECEIPT_AUTHENTICATION_FAILED = 21003;

    /**
     * The shared secret you provided does not match the shared secret on file for your account.
     */
    public const int SHARED_SECRET_INVALID = 21004;

    /**
     * The receipt server is not currently available.
     */
    public const int SERVER_UNAVAILABLE = 21005;

    /**
     * This receipt is valid but the subscription has expired.
     */
    public const int SUBSCRIPTION_EXPIRED = 21006;

    /**
     * This receipt is from the test environment, but it was sent to the production environment.
     */
    public const int SANDBOX_RECEIPT_ON_PRODUCTION = 21007;

    /**
     * This receipt is from the production environment, but it was sent to the test environment.
     */
    public const int PRODUCTION_RECEIPT_ON_SANDBOX = 21008;

    /**
     * Internal data access error.
     */
    public const int INTERNAL_DATA_ACCESS_ERROR = 21009;

    /**
     * The user account cannot be found or has been deleted.
     */
    public const int USER_ACCOUNT_NOT_FOUND = 21010;

    /**
     * An internal server error occurred.
     */
    public const int INTERNAL_ERROR = 21100;

    /**
     * Maps API error codes to human-readable messages.
     *
     * @return array<int, string>
     */
    public static function messages(): array
    {
        return [
            self::JSON_INVALID => 'The App Store could not read the JSON object you provided.',
            self::RECEIPT_DATA_MALFORMED => 'The data in the receipt-data property was malformed.',
            self::RECEIPT_AUTHENTICATION_FAILED => 'The receipt could not be authenticated.',
            self::SHARED_SECRET_INVALID => 'The shared secret you provided does not match the shared secret on file for your account.',
            self::SERVER_UNAVAILABLE => 'The receipt server is not currently available.',
            self::SUBSCRIPTION_EXPIRED => 'This receipt is valid but the subscription has expired.',
            self::SANDBOX_RECEIPT_ON_PRODUCTION => 'This receipt is from the test environment, but it was sent to the production environment.',
            self::PRODUCTION_RECEIPT_ON_SANDBOX => 'This receipt is from the production environment, but it was sent to the test environment.',
            self::USER_ACCOUNT_NOT_FOUND => 'The user account cannot be found or has been deleted.',
            self::INTERNAL_ERROR => 'An internal server error occurred.',
        ];
    }
}
