<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\AppTransaction;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;
use ReceiptValidator\AppleAppStore\RenewalInfo;
use ReceiptValidator\AppleAppStore\Transaction;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Offline verification of StoreKit 2 JWS payloads.
 *
 * Payloads are signed with the test chain in certs/storekit, which mirrors
 * Apple's layout (root → WWDR-style intermediate → App Store signing leaf).
 *
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
#[CoversClass(TokenVerifier::class)]
final class VerifySignedPayloadTest extends TestCase
{
    private const string CERTS = __DIR__ . '/certs/storekit';

    private Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new Validator(
            signingKey: (string) file_get_contents(__DIR__ . '/certs/testSigningKey.p8'),
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::SANDBOX,
        );
        $this->validator->setTokenVerifier(self::testVerifier());
    }

    public function testVerifySignedTransactionReturnsTransaction(): void
    {
        $jws = self::sign(self::transactionClaims());

        $transaction = $this->validator->verifySignedTransaction($jws);

        self::assertInstanceOf(Transaction::class, $transaction);
        self::assertSame('2000000123456789', $transaction->getTransactionId());
        self::assertSame('com.example.premium', $transaction->getProductId());
        self::assertSame('com.example', $transaction->getBundleId());
        self::assertSame(Environment::SANDBOX, $transaction->getEnvironment());
    }

    public function testVerifySignedTransactionRejectsOtherBundle(): void
    {
        $jws = self::sign(['bundleId' => 'com.someone.else'] + self::transactionClaims());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('bundle ID does not match');
        $this->validator->verifySignedTransaction($jws);
    }

    public function testVerifySignedTransactionRejectsOtherEnvironment(): void
    {
        $jws = self::sign(['environment' => 'Production'] + self::transactionClaims());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('environment does not match');
        $this->validator->verifySignedTransaction($jws);
    }

    public function testVerifySignedTransactionRejectsMissingEnvironment(): void
    {
        $claims = self::transactionClaims();
        unset($claims['environment']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('environment does not match');
        $this->validator->verifySignedTransaction(self::sign($claims));
    }

    public function testVerifySignedTransactionRejectsTamperedPayload(): void
    {
        [$header, , $signature] = explode('.', self::sign(self::transactionClaims()));
        $forged = rtrim(strtr(base64_encode((string) json_encode(
            ['productId' => 'com.example.lifetime'] + self::transactionClaims()
        )), '+/', '-_'), '=');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('JWS signature verification failed');
        $this->validator->verifySignedTransaction("$header.$forged.$signature");
    }

    public function testVerifySignedTransactionRejectsDefaultAppleTrustForTestChain(): void
    {
        $this->validator->setTokenVerifier(new TokenVerifier());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not rooted to a trusted Apple certificate');
        $this->validator->verifySignedTransaction(self::sign(self::transactionClaims()));
    }

    public function testRejectsLeafWithoutAppStoreMarker(): void
    {
        $jws = self::sign(self::transactionClaims(), leaf: 'leaf-no-marker.pem');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Leaf certificate is not an App Store signing certificate');
        $this->validator->verifySignedTransaction($jws);
    }

    public function testRejectsIntermediateWithoutWwdrMarker(): void
    {
        // Same key and subject as intermediate.pem, so the chain's signatures
        // verify and only the missing marker extension is at fault.
        $this->validator->setTokenVerifier(new TokenVerifier([
            self::fingerprint('intermediate-no-marker.pem'),
            self::fingerprint('root.pem'),
        ]));

        $jws = self::signWithChain(
            self::transactionClaims(),
            [self::der('leaf.pem'), self::der('intermediate-no-marker.pem'), self::der('root.pem')]
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Intermediate certificate is not an Apple WWDR certificate');
        $this->validator->verifySignedTransaction($jws);
    }

    public function testRejectsPayloadSignedOutsideCertificateValidity(): void
    {
        $jws = self::sign(['signedDate' => 946684800000] + self::transactionClaims()); // 2000-01-01

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('was not valid at the time the token was signed');
        $this->validator->verifySignedTransaction($jws);
    }

    public function testRejectsChainWithExtraCertificates(): void
    {
        $jws = self::signWithChain(
            self::transactionClaims(),
            [self::der('leaf.pem'), self::der('intermediate.pem'), self::der('root.pem'), self::der('root.pem')]
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('valid x5c certificate chain');
        $this->validator->verifySignedTransaction($jws);
    }

    public function testVerifySignedRenewalInfoReturnsRenewalInfo(): void
    {
        $jws = self::sign([
            'originalTransactionId'  => '2000000000000001',
            'autoRenewProductId'     => 'com.example.premium',
            'productId'              => 'com.example.premium',
            'autoRenewStatus'        => 1,
            'environment'            => 'Sandbox',
            'signedDate'             => self::nowMs(),
            'recentSubscriptionStartDate' => self::nowMs(),
        ]);

        $renewal = $this->validator->verifySignedRenewalInfo($jws);

        self::assertInstanceOf(RenewalInfo::class, $renewal);
        self::assertSame('2000000000000001', $renewal->getOriginalTransactionId());
    }

    public function testVerifySignedRenewalInfoRejectsOtherEnvironment(): void
    {
        $jws = self::sign([
            'originalTransactionId' => '2000000000000001',
            'environment'           => 'Production',
            'signedDate'            => self::nowMs(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('environment does not match');
        $this->validator->verifySignedRenewalInfo($jws);
    }

    public function testVerifySignedAppTransactionReturnsAppTransaction(): void
    {
        $jws = self::sign([
            'bundleId'                   => 'com.example',
            'receiptType'                => 'Sandbox',
            'appAppleId'                 => 1234567890,
            'applicationVersion'         => '2.0',
            'originalApplicationVersion' => '1.0',
            'originalPurchaseDate'       => self::nowMs(),
            'receiptCreationDate'        => self::nowMs(),
            'signedDate'                 => self::nowMs(),
        ]);

        $appTransaction = $this->validator->verifySignedAppTransaction($jws);

        self::assertInstanceOf(AppTransaction::class, $appTransaction);
        self::assertSame('com.example', $appTransaction->getBundleId());
    }

    public function testVerifySignedAppTransactionRejectsOtherBundle(): void
    {
        $jws = self::sign([
            'bundleId'    => 'com.someone.else',
            'receiptType' => 'Sandbox',
            'signedDate'  => self::nowMs(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('bundle ID does not match');
        $this->validator->verifySignedAppTransaction($jws);
    }

    public function testVerifySignedAppTransactionRejectsOtherReceiptType(): void
    {
        $jws = self::sign([
            'bundleId'    => 'com.example',
            'receiptType' => 'Production',
            'signedDate'  => self::nowMs(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('environment does not match');
        $this->validator->verifySignedAppTransaction($jws);
    }

    public function testRejectsGarbage(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->verifySignedTransaction('not-a-jws');
    }

    /**
     * @return array<string, mixed>
     */
    private static function transactionClaims(): array
    {
        return [
            'transactionId'         => '2000000123456789',
            'originalTransactionId' => '2000000000000001',
            'bundleId'              => 'com.example',
            'productId'             => 'com.example.premium',
            'purchaseDate'          => self::nowMs(),
            'originalPurchaseDate'  => self::nowMs(),
            'quantity'              => 1,
            'type'                  => 'Auto-Renewable Subscription',
            'inAppOwnershipType'    => 'PURCHASED',
            'signedDate'            => self::nowMs(),
            'environment'           => 'Sandbox',
        ];
    }

    private static function nowMs(): int
    {
        return time() * 1000;
    }

    private static function testVerifier(): TokenVerifier
    {
        return new TokenVerifier([self::fingerprint('intermediate.pem'), self::fingerprint('root.pem')]);
    }

    /**
     * @param array<string, mixed> $claims
     */
    private static function sign(array $claims, string $leaf = 'leaf.pem'): string
    {
        return self::signWithChain($claims, [self::der($leaf), self::der('intermediate.pem'), self::der('root.pem')]);
    }

    /**
     * @param array<string, mixed> $claims
     * @param list<string> $x5c
     */
    private static function signWithChain(array $claims, array $x5c): string
    {
        $builder = (new Builder(new JoseEncoder(), ChainedFormatter::default()))->withHeader('x5c', $x5c);

        foreach ($claims as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }

        return $builder
            ->getToken(new Sha256(), InMemory::file(self::CERTS . '/leaf.p8'))
            ->toString();
    }

    /** Base64 DER body of a PEM certificate, as it appears in an x5c header. */
    private static function der(string $file): string
    {
        $pem = (string) file_get_contents(self::CERTS . '/' . $file);

        return (string) preg_replace('/-----[^-]+-----|\s+/', '', $pem);
    }

    private static function fingerprint(string $file): string
    {
        return (string) openssl_x509_fingerprint((string) file_get_contents(self::CERTS . '/' . $file));
    }
}
