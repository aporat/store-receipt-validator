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
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;
use ReceiptValidator\AppleAppStore\ServerNotification;
use ReceiptValidator\AppleAppStore\ServerNotificationType;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Notifications and app transactions signed with the test certificate chain in
 * tests/AppleAppStore/certs/storekit, verified through a Validator that trusts it.
 *
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
#[CoversClass(ServerNotification::class)]
final class VerifyNotificationTest extends TestCase
{
    private const string CERTS      = __DIR__ . '/certs/storekit';
    private const int    APP_APPLE_ID = 1234567890;

    private function validator(Environment $environment, ?int $appAppleId = self::APP_APPLE_ID): Validator
    {
        $validator = new Validator(
            signingKey: (string) file_get_contents(__DIR__ . '/certs/testSigningKey.p8'),
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: $environment,
            appAppleId: $appAppleId,
        );

        return $validator->setTokenVerifier(
            new TokenVerifier([self::fingerprint('intermediate.pem'), self::fingerprint('root.pem')])
        );
    }

    // -------------------------------------------------------------------------
    // verifyNotification(): bundle ID and environment
    // -------------------------------------------------------------------------

    public function testVerifyNotificationAcceptsMatchingNotification(): void
    {
        $jws = self::signNotification(self::dataClaims());

        $notification = $this->validator(Environment::SANDBOX, null)->verifyNotification(['signedPayload' => $jws]);

        self::assertSame(ServerNotificationType::SUBSCRIBED, $notification->getNotificationType());
        self::assertSame('com.example', $notification->getBundleId());
        self::assertSame(Environment::SANDBOX, $notification->getEnvironment());
        self::assertNull($notification->getAppAppleId());
    }

    public function testVerifyNotificationAcceptsRawSignedPayloadString(): void
    {
        $jws = self::signNotification(self::dataClaims());

        $notification = $this->validator(Environment::SANDBOX, null)->verifyNotification($jws);

        self::assertSame('com.example', $notification->getBundleId());
    }

    public function testVerifyNotificationRejectsOtherBundle(): void
    {
        $jws = self::signNotification(self::dataClaims(['bundleId' => 'com.other']));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('bundle ID does not match');
        $this->validator(Environment::SANDBOX, null)->verifyNotification($jws);
    }

    public function testVerifyNotificationRejectsOtherEnvironment(): void
    {
        $jws = self::signNotification(self::dataClaims(['environment' => 'Production', 'appAppleId' => self::APP_APPLE_ID]));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('environment does not match');
        $this->validator(Environment::SANDBOX, null)->verifyNotification($jws);
    }

    public function testVerifyNotificationRejectsMissingEnvironment(): void
    {
        $claims = self::dataClaims();
        unset($claims['data']['environment']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('missing its environment');
        $this->validator(Environment::SANDBOX, null)->verifyNotification(self::signNotification($claims));
    }

    public function testVerifyNotificationRejectsPayloadSignedByUntrustedChain(): void
    {
        $jws = self::signNotification(self::dataClaims());

        $validator = $this->validator(Environment::SANDBOX, null)->setTokenVerifier(new TokenVerifier());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not rooted to a trusted Apple certificate');
        $validator->verifyNotification($jws);
    }

    // -------------------------------------------------------------------------
    // verifyNotification(): app Apple ID
    // -------------------------------------------------------------------------

    public function testProductionAcceptsMatchingAppAppleId(): void
    {
        $jws = self::signNotification(self::dataClaims(['environment' => 'Production', 'appAppleId' => self::APP_APPLE_ID]));

        $notification = $this->validator(Environment::PRODUCTION)->verifyNotification($jws);

        self::assertSame(self::APP_APPLE_ID, $notification->getAppAppleId());
    }

    public function testProductionRejectsOtherAppAppleId(): void
    {
        $jws = self::signNotification(self::dataClaims(['environment' => 'Production', 'appAppleId' => 42]));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('app Apple ID does not match');
        $this->validator(Environment::PRODUCTION)->verifyNotification($jws);
    }

    public function testProductionRejectsMissingAppAppleIdWhenConfigured(): void
    {
        $jws = self::signNotification(self::dataClaims(['environment' => 'Production']));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('app Apple ID does not match');
        $this->validator(Environment::PRODUCTION)->verifyNotification($jws);
    }

    public function testProductionSkipsAppAppleIdWhenNotConfigured(): void
    {
        $jws = self::signNotification(self::dataClaims(['environment' => 'Production', 'appAppleId' => 42]));

        $notification = $this->validator(Environment::PRODUCTION, null)->verifyNotification($jws);

        self::assertSame(42, $notification->getAppAppleId());
    }

    public function testSandboxIgnoresAppAppleIdMismatch(): void
    {
        $jws = self::signNotification(self::dataClaims(['appAppleId' => 42]));

        $notification = $this->validator(Environment::SANDBOX)->verifyNotification($jws);

        self::assertSame(42, $notification->getAppAppleId());
    }

    public function testSetAppAppleIdEnablesTheCheck(): void
    {
        $jws       = self::signNotification(self::dataClaims(['environment' => 'Production', 'appAppleId' => 42]));
        $validator = $this->validator(Environment::PRODUCTION, null);

        self::assertNull($validator->getAppAppleId());
        $validator->setAppAppleId(self::APP_APPLE_ID);
        self::assertSame(self::APP_APPLE_ID, $validator->getAppAppleId());

        $this->expectException(ValidationException::class);
        $validator->verifyNotification($jws);
    }

    // -------------------------------------------------------------------------
    // Identity from other payload sections
    // -------------------------------------------------------------------------

    public function testSummaryNotificationUsesSummarySection(): void
    {
        $jws = self::signNotification([
            'notificationType' => 'RENEWAL_EXTENSION',
            'subtype'          => 'SUMMARY',
            'notificationUUID' => 'f6e8c7b2-1a2b-4c3d-8e9f-0a1b2c3d4e5f',
            'signedDate'       => time() * 1000,
            'version'          => '2.0',
            'summary'          => [
                'bundleId'          => 'com.example',
                'environment'       => 'Production',
                'appAppleId'        => self::APP_APPLE_ID,
                'requestIdentifier' => 'req-1',
                'productId'         => 'com.example.premium',
                'succeededCount'    => 10,
                'failedCount'       => 0,
            ],
        ]);

        $notification = $this->validator(Environment::PRODUCTION)->verifyNotification($jws);

        self::assertSame('com.example', $notification->getBundleId());
        self::assertSame(Environment::PRODUCTION, $notification->getEnvironment());
        self::assertNull($notification->getTransaction());
    }

    public function testExternalPurchaseTokenDerivesEnvironmentFromPrefix(): void
    {
        $base = [
            'notificationType' => 'EXTERNAL_PURCHASE_TOKEN',
            'subtype'          => 'UNREPORTED',
            'notificationUUID' => 'f6e8c7b2-1a2b-4c3d-8e9f-0a1b2c3d4e5f',
            'signedDate'       => time() * 1000,
            'version'          => '2.0',
        ];

        $sandbox = self::signNotification($base + ['externalPurchaseToken' => [
            'externalPurchaseId' => 'SANDBOX_abc123',
            'tokenCreationDate'  => time() * 1000,
            'appAppleId'         => self::APP_APPLE_ID,
            'bundleId'           => 'com.example',
        ]]);
        self::assertSame(Environment::SANDBOX, $this->validator(Environment::SANDBOX)->verifyNotification($sandbox)->getEnvironment());

        $production = self::signNotification($base + ['externalPurchaseToken' => [
            'externalPurchaseId' => 'abc123',
            'tokenCreationDate'  => time() * 1000,
            'appAppleId'         => self::APP_APPLE_ID,
            'bundleId'           => 'com.example',
        ]]);
        self::assertSame(Environment::PRODUCTION, $this->validator(Environment::PRODUCTION)->verifyNotification($production)->getEnvironment());
    }

    // -------------------------------------------------------------------------
    // verifySignedAppTransaction(): app Apple ID
    // -------------------------------------------------------------------------

    public function testAppTransactionProductionRejectsOtherAppAppleId(): void
    {
        $jws = self::sign(self::appTransactionClaims(['appAppleId' => 42]));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('app Apple ID does not match');
        $this->validator(Environment::PRODUCTION)->verifySignedAppTransaction($jws);
    }

    public function testAppTransactionProductionAcceptsMatchingAppAppleId(): void
    {
        $jws = self::sign(self::appTransactionClaims());

        $appTransaction = $this->validator(Environment::PRODUCTION)->verifySignedAppTransaction($jws);

        self::assertSame('com.example', $appTransaction->getBundleId());
    }

    public function testAppTransactionSandboxIgnoresAppAppleId(): void
    {
        $jws = self::sign(self::appTransactionClaims(['receiptType' => 'Sandbox', 'appAppleId' => 42]));

        $appTransaction = $this->validator(Environment::SANDBOX)->verifySignedAppTransaction($jws);

        self::assertSame('com.example', $appTransaction->getBundleId());
    }

    // -------------------------------------------------------------------------
    // Direct construction keeps its previous behavior
    // -------------------------------------------------------------------------

    public function testDirectConstructionDoesNotBindToAnApp(): void
    {
        $jws      = self::signNotification(self::dataClaims(['bundleId' => 'com.other']));
        $verifier = new TokenVerifier([self::fingerprint('intermediate.pem'), self::fingerprint('root.pem')]);

        $notification = new ServerNotification(['signedPayload' => $jws], $verifier);

        self::assertSame('com.other', $notification->getBundleId());
    }

    // -------------------------------------------------------------------------
    // Fixtures
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $overrides Merged into the `data` section.
     * @return array<string, mixed>
     */
    private static function dataClaims(array $overrides = []): array
    {
        return [
            'notificationType' => 'SUBSCRIBED',
            'subtype'          => 'INITIAL_BUY',
            'notificationUUID' => 'f6e8c7b2-1a2b-4c3d-8e9f-0a1b2c3d4e5f',
            'signedDate'       => time() * 1000,
            'version'          => '2.0',
            'data'             => $overrides + [
                'bundleId'    => 'com.example',
                'environment' => 'Sandbox',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private static function appTransactionClaims(array $overrides = []): array
    {
        return $overrides + [
            'bundleId'                   => 'com.example',
            'receiptType'                => 'Production',
            'appAppleId'                 => self::APP_APPLE_ID,
            'applicationVersion'         => '1.0',
            'originalApplicationVersion' => '1.0',
            'originalPurchaseDate'       => time() * 1000,
            'receiptCreationDate'        => time() * 1000,
            'signedDate'                 => time() * 1000,
        ];
    }

    /**
     * @param array<string, mixed> $claims
     */
    private static function signNotification(array $claims): string
    {
        return self::sign($claims);
    }

    /**
     * @param array<string, mixed> $claims
     */
    private static function sign(array $claims): string
    {
        $x5c     = [self::der('leaf.pem'), self::der('intermediate.pem'), self::der('root.pem')];
        $builder = (new Builder(new JoseEncoder(), ChainedFormatter::default()))->withHeader('x5c', $x5c);

        foreach ($claims as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }

        return $builder
            ->getToken(new Sha256(), InMemory::file(self::CERTS . '/leaf.p8'))
            ->toString();
    }

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
