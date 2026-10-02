<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

use ReceiptValidator\Exceptions\ValidationException;

/**
 * Outcomes of a Receipt Verification Service (RVS) request, keyed by HTTP status code.
 *
 * Amazon signals the result of a verification purely through the status code; the
 * response body of an error is not documented. The status code is also the code of the
 * {@see ValidationException} the validator throws, so a caller can recover the case with
 * {@see APIError::fromException()}.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#response-codes
 */
enum APIError: int
{
    /** The receipt ID is invalid, or no transaction exists for it. */
    case INVALID_RECEIPT = 400;

    /**
     * The transaction represented by the receipt ID is no longer valid. Amazon says to
     * treat it as a cancelled receipt: revoke the content it granted.
     */
    case RECEIPT_NO_LONGER_VALID = 410;

    /** The request was throttled. Reduce the calling rate and retry later. */
    case THROTTLED = 429;

    /** The shared secret does not match the developer account. */
    case INVALID_SHARED_SECRET = 496;

    /** The user ID is not valid for this receipt. */
    case INVALID_USER_ID = 497;

    /** Amazon's server failed to process the request. */
    case INTERNAL_ERROR = 500;

    /**
     * Returns a human-readable description for the error case.
     */
    public function message(): string
    {
        return match ($this) {
            self::INVALID_RECEIPT         => 'The receipt ID is invalid or no transaction was found for it.',
            self::RECEIPT_NO_LONGER_VALID => 'The receipt is no longer valid and should be treated as canceled.',
            self::THROTTLED               => 'The request was throttled; reduce the calling rate and retry later.',
            self::INVALID_SHARED_SECRET   => 'The shared secret is not valid.',
            self::INVALID_USER_ID         => 'The user ID is not valid.',
            self::INTERNAL_ERROR          => 'An internal error occurred on the Amazon server.',
        };
    }

    /**
     * Whether Amazon says the receipt should be treated as cancelled.
     *
     * The receipt was once valid, so the caller should revoke whatever it granted rather
     * than treat the failure as a bad request.
     */
    public function isCanceledReceipt(): bool
    {
        return $this === self::RECEIPT_NO_LONGER_VALID;
    }

    /**
     * Whether the same request may succeed if retried after a delay.
     */
    public function isRetryable(): bool
    {
        return match ($this) {
            self::THROTTLED, self::INTERNAL_ERROR => true,
            default                               => false,
        };
    }

    /**
     * Recovers the error case from a {@see ValidationException} thrown by the validator.
     *
     * Returns null for exceptions that did not originate from an RVS status code, such as
     * a connection failure or a missing parameter.
     */
    public static function fromException(ValidationException $exception): ?self
    {
        return self::tryFrom($exception->getCode());
    }
}
