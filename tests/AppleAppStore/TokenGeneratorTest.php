<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use DateTimeImmutable;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenGeneratorConfig;
use ReceiptValidator\AppleAppStore\JWT\TokenIssuer;
use ReceiptValidator\AppleAppStore\JWT\TokenKey;

/**
 * @group      apple-app-store
 * @coversDefaultClass \ReceiptValidator\AppleAppStore\JWT\TokenGenerator
 */
class TokenGeneratorTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::generate
     */
    public function testTokenGeneration(): void
    {
        $keyPath = __DIR__ . '/certs/testSigningKey.p8';

        if (!is_readable($keyPath)) {
            $this->markTestSkipped('Test signing key is not available at ' . $keyPath);
        }

        $signingKey = file_get_contents($keyPath);

        $issuerId = 'issuer-id';
        $bundleId = 'com.example.app';
        $keyId    = 'ABC123DEFG';

        $issuer = new TokenIssuer(
            $issuerId,
            $bundleId,
            new TokenKey($keyId, InMemory::plainText($signingKey)),
            new Sha256()
        );

        $fixedNow = new DateTimeImmutable('2025-01-01T00:00:00Z');
        $clock    = new FrozenClock($fixedNow);

        $config    = TokenGeneratorConfig::forAppStore($issuer, $clock);
        $generator = new TokenGenerator($config);
        $token     = $generator->generate();

        $this->assertNotNull($token);

        $this->assertSame($issuerId, $token->claims()->get('iss'));
        $this->assertSame($bundleId, $token->claims()->get('bid'));

        $aud = $token->claims()->get('aud');
        $this->assertIsArray($aud);
        $this->assertSame(['appstoreconnect-v1'], $aud);

        $iat = $token->claims()->get('iat');
        $exp = $token->claims()->get('exp');

        $this->assertInstanceOf(\DateTimeImmutable::class, $iat);
        $this->assertInstanceOf(\DateTimeImmutable::class, $exp);

        $this->assertSame($fixedNow->getTimestamp(), $iat->getTimestamp(), 'iat should equal frozen clock');

        $ttlMinutes = \ReceiptValidator\AppleAppStore\JWT\TokenGenerator::EXPIRATION_MINUTES;
        $expectedExpTs = $fixedNow->modify(sprintf('+%d minutes', $ttlMinutes))->getTimestamp();
        $this->assertSame($expectedExpTs, $exp->getTimestamp(), sprintf('exp should be iat + %d minutes', $ttlMinutes));

        $this->assertSame($keyId, $token->headers()->get('kid'));
    }
}
