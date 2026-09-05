<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * Well-known `error.errors[].reason` values returned by the Android Publisher API.
 *
 * Google's error envelope looks like:
 *
 *     {"error": {"code": 400, "message": "...", "status": "INVALID_ARGUMENT",
 *                "errors": [{"domain": "androidpublisher", "reason": "purchaseTokenDoesNotMatchPackageName", ...}]}}
 *
 * The reason string is the most specific signal, so this enum keys on it.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2/get
 */
enum APIError: string
{
    /** The purchase token was issued for a different package name. */
    case PURCHASE_TOKEN_DOES_NOT_MATCH_PACKAGE_NAME = 'purchaseTokenDoesNotMatchPackageName';

    /** The purchase token is too old or has been superseded (HTTP 410). */
    case PURCHASE_TOKEN_NO_LONGER_VALID = 'purchaseTokenNoLongerValid';

    /** The subscription purchase is no longer available to query. */
    case SUBSCRIPTION_PURCHASE_NO_LONGER_AVAILABLE = 'subscriptionPurchaseNoLongerAvailable';

    /** The product is not owned by the user identified by the purchase token. */
    case PRODUCT_NOT_OWNED_BY_USER = 'productNotOwnedByUser';

    /** A request parameter was invalid (e.g. malformed purchase token). */
    case INVALID = 'invalid';

    /** A required parameter was missing. */
    case REQUIRED = 'required';

    /** The purchase token / resource was not found. */
    case NOT_FOUND = 'notFound';

    /** The service account lacks permission for this app. */
    case PERMISSION_DENIED = 'permissionDenied';

    /** The Google Cloud project is not linked to the Play developer account. */
    case PROJECT_NOT_LINKED = 'projectNotLinked';

    /** The developer account does not own the requested application. */
    case DEVELOPER_DOES_NOT_OWN_APPLICATION = 'developerDoesNotOwnApplication';

    /** Authentication failed (bad or expired access token). */
    case AUTH_ERROR = 'authError';

    /** Per-project quota exhausted. */
    case QUOTA_EXCEEDED = 'quotaExceeded';

    /** Too many requests in a short period. */
    case RATE_LIMIT_EXCEEDED = 'rateLimitExceeded';

    /** Daily request limit exhausted. */
    case DAILY_LIMIT_EXCEEDED = 'dailyLimitExceeded';

    /** Transient error on Google's side. */
    case BACKEND_ERROR = 'backendError';

    /** Internal error on Google's side. */
    case INTERNAL_ERROR = 'internalError';

    /**
     * Returns a concise human-readable description for the error.
     */
    public function message(): string
    {
        return match ($this) {
            self::PURCHASE_TOKEN_DOES_NOT_MATCH_PACKAGE_NAME => 'The purchase token does not belong to this package name.',
            self::PURCHASE_TOKEN_NO_LONGER_VALID             => 'The purchase token is no longer valid.',
            self::SUBSCRIPTION_PURCHASE_NO_LONGER_AVAILABLE  => 'The subscription purchase is no longer available.',
            self::PRODUCT_NOT_OWNED_BY_USER                  => 'The product is not owned by the user.',
            self::INVALID                                    => 'A request parameter was invalid.',
            self::REQUIRED                                   => 'A required request parameter was missing.',
            self::NOT_FOUND                                  => 'The purchase was not found.',
            self::PERMISSION_DENIED                          => 'The service account is not permitted to access this app.',
            self::PROJECT_NOT_LINKED                         => 'The Google Cloud project is not linked to the Play developer account.',
            self::DEVELOPER_DOES_NOT_OWN_APPLICATION         => 'The developer account does not own this application.',
            self::AUTH_ERROR                                 => 'Authentication with the Android Publisher API failed.',
            self::QUOTA_EXCEEDED                             => 'The API quota has been exceeded.',
            self::RATE_LIMIT_EXCEEDED                        => 'The request rate limit has been exceeded.',
            self::DAILY_LIMIT_EXCEEDED                       => 'The daily request limit has been exceeded.',
            self::BACKEND_ERROR                              => 'A transient backend error occurred at Google.',
            self::INTERNAL_ERROR                             => 'An internal error occurred at Google.',
        };
    }

    /**
     * Whether the request is worth retrying later without changes.
     */
    public function isRetryable(): bool
    {
        return match ($this) {
            self::QUOTA_EXCEEDED,
            self::RATE_LIMIT_EXCEEDED,
            self::DAILY_LIMIT_EXCEEDED,
            self::BACKEND_ERROR,
            self::INTERNAL_ERROR => true,
            default              => false,
        };
    }

    /**
     * Safely creates an APIError case from a reason string, or null if unknown.
     */
    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
