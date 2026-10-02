<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore\Signature;

use DateTimeImmutable;
use InvalidArgumentException;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator as JwtValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\Signature\AdvancedCommerceInAppSignatureCreator;
use ReceiptValidator\AppleAppStore\Signature\IntroductoryOfferEligibilitySignatureCreator;
use ReceiptValidator\AppleAppStore\Signature\JwsSignatureCreator;
use ReceiptValidator\AppleAppStore\Signature\PromotionalOfferSignatureCreator;
use ReceiptValidator\AppleAppStore\Signature\PromotionalOfferV2SignatureCreator;

/**
 * @group apple-app-store
 */
#[CoversClass(JwsSignatureCreator::class)]
#[CoversClass(PromotionalOfferSignatureCreator::class)]
#[CoversClass(PromotionalOfferV2SignatureCreator::class)]
#[CoversClass(IntroductoryOfferEligibilitySignatureCreator::class)]
#[CoversClass(AdvancedCommerceInAppSignatureCreator::class)]
final class SignatureCreatorTest extends TestCase
{
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    private string $signingKey;
    private string $publicKey;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signingKey = (string) file_get_contents(__DIR__ . '/../certs/testSigningKey.p8');
        $this->clock      = new FrozenClock(new DateTimeImmutable('2025-03-03T22:34:23Z'));

        $private = openssl_pkey_get_private($this->signingKey);
        self::assertNotFalse($private);
        $details = openssl_pkey_get_details($private);
        self::assertIsArray($details);
        $this->publicKey = $details['key'];
    }

    private function parseAndVerify(string $jws): Plain
    {
        $token = (new Parser(new JoseEncoder()))->parse($jws);
        self::assertInstanceOf(Plain::class, $token);

        $valid = (new JwtValidator())->validate(
            $token,
            new SignedWith(new Sha256(), InMemory::plainText($this->publicKey)),
        );
        self::assertTrue($valid, 'JWS signature does not verify against the public key');

        return $token;
    }

    private function assertBaseClaims(Plain $token, string $audience): void
    {
        self::assertSame('JWT', $token->headers()->get('typ'));
        self::assertSame('ES256', $token->headers()->get('alg'));
        self::assertSame('keyId', $token->headers()->get('kid'));

        $claims = $token->claims();
        self::assertSame('issuerId', $claims->get('iss'));
        self::assertSame([$audience], $claims->get('aud'));
        self::assertSame('bundleId', $claims->get('bid'));
        self::assertEquals($this->clock->now(), $claims->get('iat'));
        self::assertFalse($claims->has('exp'), 'Apple rejects a JWS that carries an exp claim');
        self::assertFalse($claims->has('nbf'));
        self::assertMatchesRegularExpression(self::UUID_PATTERN, (string) $claims->get('nonce'));
    }

    // -------------------------------------------------------------------------
    // Promotional offer V2
    // -------------------------------------------------------------------------

    public function testPromotionalOfferV2Signature(): void
    {
        $creator = new PromotionalOfferV2SignatureCreator($this->signingKey, 'keyId', 'issuerId', 'bundleId', $this->clock);
        $token   = $this->parseAndVerify($creator->createSignature('productId', 'offerIdentifier', 'transactionId'));

        $this->assertBaseClaims($token, 'promotional-offer');
        self::assertSame('productId', $token->claims()->get('productId'));
        self::assertSame('offerIdentifier', $token->claims()->get('offerIdentifier'));
        self::assertSame('transactionId', $token->claims()->get('transactionId'));
    }

    public function testPromotionalOfferV2SignatureWithoutTransactionId(): void
    {
        $creator = new PromotionalOfferV2SignatureCreator($this->signingKey, 'keyId', 'issuerId', 'bundleId', $this->clock);
        $token   = $this->parseAndVerify($creator->createSignature('productId', 'offerIdentifier'));

        self::assertFalse($token->claims()->has('transactionId'));
    }

    public function testPromotionalOfferV2RejectsEmptyProductId(): void
    {
        $creator = new PromotionalOfferV2SignatureCreator($this->signingKey, 'keyId', 'issuerId', 'bundleId');

        $this->expectException(InvalidArgumentException::class);
        $creator->createSignature('', 'offerIdentifier');
    }

    public function testEachSignatureUsesAFreshNonce(): void
    {
        $creator = new PromotionalOfferV2SignatureCreator($this->signingKey, 'keyId', 'issuerId', 'bundleId', $this->clock);

        $first  = $this->parseAndVerify($creator->createSignature('productId', 'offerIdentifier'));
        $second = $this->parseAndVerify($creator->createSignature('productId', 'offerIdentifier'));

        self::assertNotSame($first->claims()->get('nonce'), $second->claims()->get('nonce'));
    }

    // -------------------------------------------------------------------------
    // Introductory offer eligibility
    // -------------------------------------------------------------------------

    public function testIntroductoryOfferEligibilitySignature(): void
    {
        $creator = new IntroductoryOfferEligibilitySignatureCreator($this->signingKey, 'keyId', 'issuerId', 'bundleId', $this->clock);
        $token   = $this->parseAndVerify($creator->createSignature('productId', true, 'transactionId'));

        $this->assertBaseClaims($token, 'introductory-offer-eligibility');
        self::assertSame('productId', $token->claims()->get('productId'));
        self::assertTrue($token->claims()->get('allowIntroductoryOffer'));
        self::assertSame('transactionId', $token->claims()->get('transactionId'));
    }

    public function testIntroductoryOfferEligibilityRejectsEmptyTransactionId(): void
    {
        $creator = new IntroductoryOfferEligibilitySignatureCreator($this->signingKey, 'keyId', 'issuerId', 'bundleId');

        $this->expectException(InvalidArgumentException::class);
        $creator->createSignature('productId', false, '');
    }

    // -------------------------------------------------------------------------
    // Advanced Commerce in-app request
    // -------------------------------------------------------------------------

    public function testAdvancedCommerceInAppSignature(): void
    {
        $creator = new AdvancedCommerceInAppSignatureCreator($this->signingKey, 'keyId', 'issuerId', 'bundleId', $this->clock);
        $token   = $this->parseAndVerify($creator->createSignature(['testValue' => 'testValue', 'nested' => ['a' => 1]]));

        $this->assertBaseClaims($token, 'advanced-commerce-api');

        $decoded = base64_decode((string) $token->claims()->get('request'), true);
        self::assertNotFalse($decoded);
        self::assertSame(['testValue' => 'testValue', 'nested' => ['a' => 1]], json_decode($decoded, true));
    }

    // -------------------------------------------------------------------------
    // Promotional offer (StoreKit 1)
    // -------------------------------------------------------------------------

    public function testPromotionalOfferSignatureVerifiesAgainstPublicKey(): void
    {
        $creator   = new PromotionalOfferSignatureCreator($this->signingKey, 'keyId', 'bundleId');
        $signature = $creator->createSignature(
            'productId',
            'offerId',
            'AppAccountToken',
            '20FBA8A0-2B80-4A7D-A17F-85C1854727F8',
            1698148900000,
        );

        $raw = base64_decode($signature, true);
        self::assertNotFalse($raw);

        // Apple joins the fields with U+2063 and lowercases the account token and nonce.
        $payload = implode("\u{2063}", [
            'bundleId',
            'keyId',
            'productId',
            'offerId',
            'appaccounttoken',
            '20fba8a0-2b80-4a7d-a17f-85c1854727f8',
            '1698148900000',
        ]);

        self::assertSame(1, openssl_verify($payload, $raw, $this->publicKey, OPENSSL_ALGO_SHA256));
    }

    public function testPromotionalOfferSignatureRejectsInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PromotionalOfferSignatureCreator('not a key', 'keyId', 'bundleId');
    }

    public function testJwsCreatorRejectsEmptyIssuerId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PromotionalOfferV2SignatureCreator($this->signingKey, 'keyId', '', 'bundleId');
    }
}
