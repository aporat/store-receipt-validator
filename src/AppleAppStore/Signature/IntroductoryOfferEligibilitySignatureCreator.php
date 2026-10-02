<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore\Signature;

use InvalidArgumentException;
use Lcobucci\Clock\Clock;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Creates the JWS that tells StoreKit whether a customer is eligible for an introductory offer.
 *
 * @see https://developer.apple.com/documentation/storekit/generating-jws-to-sign-app-store-requests
 */
final class IntroductoryOfferEligibilitySignatureCreator extends JwsSignatureCreator
{
    public const string AUDIENCE = 'introductory-offer-eligibility';

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
     * Create an introductory offer eligibility signature.
     *
     * @param string $productId              The unique identifier of the product.
     * @param bool   $allowIntroductoryOffer Whether the customer is eligible for an introductory offer.
     * @param string $transactionId          Any transaction ID belonging to the customer, including their
     *                                       appTransactionId.
     *
     * @return string The signed JWS.
     *
     * @throws ValidationException
     */
    public function createSignature(string $productId, bool $allowIntroductoryOffer, string $transactionId): string
    {
        if ($productId === '' || $transactionId === '') {
            throw new InvalidArgumentException('Product ID and transaction ID must not be empty.');
        }

        return $this->createJws([
            'productId'              => $productId,
            'allowIntroductoryOffer' => $allowIntroductoryOffer,
            'transactionId'          => $transactionId,
        ]);
    }
}
