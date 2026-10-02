<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\Exceptions\ValidationException;
use Throwable;

/**
 * A non-2xx response from the Android Publisher API.
 *
 * Extends {@see ValidationException} so existing catch blocks keep working, and adds
 * the HTTP status, Google's `reason` string and the matching {@see APIError} so
 * callers can branch on them (for example to retry a {@see APIError::isRetryable()}
 * failure) without parsing the message.
 */
class APIException extends ValidationException
{
    /**
     * @param int           $statusCode HTTP status of the response.
     * @param string|null   $reason     Google's `error.errors[0].reason`, when present.
     * @param APIError|null $error      The known error matching $reason, when there is one.
     */
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly ?string $reason = null,
        private readonly ?APIError $error = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** Google's raw `reason` string, including values this library does not know. */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getError(): ?APIError
    {
        return $this->error;
    }

    /**
     * Whether the request is worth retrying unchanged: a known retryable reason, HTTP
     * 429, or any 5xx.
     */
    public function isRetryable(): bool
    {
        if ($this->error !== null) {
            return $this->error->isRetryable();
        }

        return $this->statusCode === 429 || $this->statusCode >= 500;
    }
}
