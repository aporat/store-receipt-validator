<?php

namespace ReceiptValidator\Amazon;

/**
 * Represents error responses from the Amazon RVS (Receipt Verification Service).
 *
 * This enum provides a type-safe way to handle specific error codes returned
 * by the Amazon API, encapsulating the error code, its string value, and a
 * human-readable message.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#error-response
 */
enum APIError: string
{
    /**
     * The receipt ID provided in the request is not valid.
     */
    case INVALID_RECEIPT_ID = 'InvalidReceiptId';

    /**
     * The user ID provided in the request is not valid.
     */
    case INVALID_USER_ID = 'InvalidUserId';

    /**
     * The developer secret provided in the request is not valid.
     */
    case INVALID_DEVELOPER_SECRET = 'InvalidDeveloperSecret';

    /**
     * The request body was malformed or was not valid JSON.
     */
    case INVALID_JSON = 'InvalidJson';

    /**
     * An unknown or internal error occurred on Amazon’s server.
     */
    case INTERNAL_ERROR = 'InternalError';

    /**
     * Returns a human-readable description for the error case.
     */
    public function message(): string
    {
        return match ($this) {
            self::INVALID_RECEIPT_ID => 'The receipt ID is not valid.',
            self::INVALID_USER_ID => 'The user ID is not valid.',
            self::INVALID_DEVELOPER_SECRET => 'The developer secret is not valid.',
            self::INVALID_JSON => 'The request JSON was malformed.',
            self::INTERNAL_ERROR => 'An unknown error occurred on the Amazon server.',
        };
    }
}
