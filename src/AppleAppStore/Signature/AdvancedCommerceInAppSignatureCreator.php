<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore\Signature;

use JsonSerializable;
use Lcobucci\Clock\Clock;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Creates the JWS a StoreKit 2 app passes for an Advanced Commerce API in-app request.
 *
 * @see https://developer.apple.com/documentation/storekit/generating-jws-to-sign-app-store-requests
 */
final class AdvancedCommerceInAppSignatureCreator extends JwsSignatureCreator
{
    public const string AUDIENCE = 'advanced-commerce-api';

    /**
     * @param string $signingKey Your private key (.p8 contents) downloaded from App Store Connect.
     * @param string $keyId      Your private key ID from App Store Connect.
     * @param string $issuerId   Your issuer ID from the Keys page in App Store Connect.
     * @param string $bundleId   Your app's bundle ID.
     */
    public function __construct(string $signingKey, string $keyId, string $issuerId, string $bundleId, ?Clock $clock = null)
    {
        parent::__construct(self::AUDIENCE, $signingKey, $keyId, $issuerId, $bundleId, $clock);
    }

    /**
     * Create an Advanced Commerce in-app signed request.
     *
     * The request is JSON-encoded, Base64-encoded and placed in the `request` claim.
     *
     * @param array<string, mixed>|JsonSerializable $request The Advanced Commerce in-app request to sign.
     *
     * @return string The signed JWS.
     *
     * @throws ValidationException
     */
    public function createSignature(array|JsonSerializable $request): string
    {
        $json = json_encode($request, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $this->createJws([
            'request' => base64_encode($json),
        ]);
    }
}
