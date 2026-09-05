<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay\JWT;

use ReceiptValidator\Exceptions\ValidationException;

/**
 * Supplies OAuth 2.0 bearer tokens for the Android Publisher API.
 *
 * The default implementation is {@see ServiceAccountTokenProvider}. Implement this
 * interface (or use {@see CallbackAccessTokenProvider}) to plug in another token
 * source, such as google/auth or a shared cache.
 */
interface AccessTokenProvider
{
    /**
     * Return a currently valid access token.
     *
     * @throws ValidationException If a token cannot be obtained.
     */
    public function getAccessToken(): string;
}
