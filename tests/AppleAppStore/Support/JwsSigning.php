<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore\Support;

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;

/**
 * Signs StoreKit 2 style JWS payloads with the test chain in
 * tests/AppleAppStore/certs/storekit (root → WWDR-style intermediate → App Store
 * signing leaf) and builds a {@see TokenVerifier} that trusts that chain.
 */
trait JwsSigning
{
    private static string $storeKitCerts = __DIR__ . '/../certs/storekit';

    /** The real Apple-signed test notification, verifiable with the default trust. */
    protected static function appleSignedPayload(): string
    {
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../fixtures/test-notification-signed-payload.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return (string) $fixture['signedPayload'];
    }

    protected static function storeKitVerifier(): TokenVerifier
    {
        return new TokenVerifier([
            self::storeKitFingerprint('intermediate.pem'),
            self::storeKitFingerprint('root.pem'),
        ]);
    }

    /**
     * @param array<string, mixed> $claims
     */
    protected static function signWithStoreKitChain(array $claims, string $leaf = 'leaf.pem'): string
    {
        $builder = (new Builder(new JoseEncoder(), ChainedFormatter::default()))->withHeader('x5c', [
            self::storeKitDer($leaf),
            self::storeKitDer('intermediate.pem'),
            self::storeKitDer('root.pem'),
        ]);

        foreach ($claims as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }

        return $builder
            ->getToken(new Sha256(), InMemory::file(self::$storeKitCerts . '/leaf.p8'))
            ->toString();
    }

    /**
     * @return array<string, mixed>
     */
    protected static function storeKitTransactionClaims(): array
    {
        return [
            'transactionId'         => '2000000123456789',
            'originalTransactionId' => '2000000000000001',
            'bundleId'              => 'com.example',
            'productId'             => 'com.example.premium',
            'purchaseDate'          => time() * 1000,
            'originalPurchaseDate'  => time() * 1000,
            'quantity'              => 1,
            'type'                  => 'Auto-Renewable Subscription',
            'inAppOwnershipType'    => 'PURCHASED',
            'signedDate'            => time() * 1000,
            'environment'           => 'Sandbox',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function storeKitRenewalClaims(): array
    {
        return [
            'originalTransactionId'       => '2000000000000001',
            'autoRenewProductId'          => 'com.example.premium',
            'productId'                   => 'com.example.premium',
            'autoRenewStatus'             => 1,
            'environment'                 => 'Sandbox',
            'signedDate'                  => time() * 1000,
            'recentSubscriptionStartDate' => time() * 1000,
        ];
    }

    /** Base64 DER body of a PEM certificate, as it appears in an x5c header. */
    private static function storeKitDer(string $file): string
    {
        $pem = (string) file_get_contents(self::$storeKitCerts . '/' . $file);

        return (string) preg_replace('/-----[^-]+-----|\s+/', '', $pem);
    }

    private static function storeKitFingerprint(string $file): string
    {
        return (string) openssl_x509_fingerprint((string) file_get_contents(self::$storeKitCerts . '/' . $file));
    }
}
