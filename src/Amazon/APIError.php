<?php

namespace ReceiptValidator\Amazon;

/**
 * Amazon RVS (Receipt Verification Service) error codes and descriptions.
 *
 * Reference:
 * https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#error-response
 */
final class APIError
{
    /**
     * The receipt ID is not valid.
     * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#error-response
     */
    public const string INVALID_RECEIPT_ID = 'InvalidReceiptId';

    /**
     * The user ID is not valid.
     * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#error-response
     */
    public const string INVALID_USER_ID = 'InvalidUserId';

    /**
     * The developer secret is invalid.
     * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#error-response
     */
    public const string INVALID_DEVELOPER_SECRET = 'InvalidDeveloperSecret';

    /**
     * The request body was malformed or not valid JSON.
     * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#error-response
     */
    public const string INVALID_JSON = 'InvalidJson';

    /**
     * An unknown error occurred on Amazon’s server.
     * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#error-response
     */
    public const string INTERNAL_ERROR = 'InternalError';

    /**
     * Returns a mapping of error codes to human-readable descriptions.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            self::INVALID_RECEIPT_ID => 'The receipt ID is not valid.',
            self::INVALID_USER_ID => 'The user ID is not valid.',
            self::INVALID_DEVELOPER_SECRET => 'The developer secret is not valid.',
            self::INVALID_JSON => 'The request JSON was malformed.',
            self::INTERNAL_ERROR => 'An unknown error occurred on the Amazon server.',
        ];
    }
}
