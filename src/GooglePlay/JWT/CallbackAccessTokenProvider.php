<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay\JWT;

use Closure;
use ReceiptValidator\Exceptions\ValidationException;
use Throwable;

/**
 * Adapts any callable that returns an access token string to {@see AccessTokenProvider}.
 *
 * Handy for bridging an existing token source, e.g. google/auth:
 *
 *     new CallbackAccessTokenProvider(
 *         fn () => $credentials->fetchAuthToken()['access_token']
 *     );
 */
final class CallbackAccessTokenProvider implements AccessTokenProvider
{
    /** @var Closure(): mixed */
    private readonly Closure $callback;

    /**
     * @param callable(): mixed $callback Returns the access token string.
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback(...);
    }

    public function getAccessToken(): string
    {
        try {
            $token = ($this->callback)();
        } catch (Throwable $e) {
            throw new ValidationException('Access token callback failed: ' . $e->getMessage(), 0, $e);
        }

        if (!is_string($token) || $token === '') {
            throw new ValidationException('Access token callback did not return a non-empty string.');
        }

        return $token;
    }
}
