<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore\Signature;

use InvalidArgumentException;
use Lcobucci\Clock\Clock;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Creates the JWS a StoreKit 2 app passes when redeeming a promotional offer.
 *
 * @see https://developer.apple.com/documentation/storekit/generating-jws-to-sign-app-store-requests
 */
final class PromotionalOfferV2SignatureCreator extends JwsSignatureCreator
{
    public const string AUDIENCE = 'promotional-offer';

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
     * Create a promotional offer V2 signature.
     *
     * @param string      $productId       The unique identifier of the product.
     * @param string      $offerIdentifier The promotional offer identifier you set up in App Store Connect.
     * @param string|null $transactionId   Any transaction ID belonging to the customer, including their
     *                                     appTransactionId. Optional, but recommended.
     *
     * @return string The signed JWS.
     *
     * @throws ValidationException
     */
    public function createSignature(string $productId, string $offerIdentifier, ?string $transactionId = null): string
    {
        if ($productId === '' || $offerIdentifier === '') {
            throw new InvalidArgumentException('Product ID and offer identifier must not be empty.');
        }

        $claims = [
            'productId'       => $productId,
            'offerIdentifier' => $offerIdentifier,
        ];

        if ($transactionId !== null) {
            $claims['transactionId'] = $transactionId;
        }

        return $this->createJws($claims);
    }
}
