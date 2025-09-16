<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenGeneratorConfig;
use ReceiptValidator\AppleAppStore\JWT\TokenIssuer;
use ReceiptValidator\AppleAppStore\JWT\TokenKey;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

/**
 * @group      apple-app-store
 * @coversDefaultClass \ReceiptValidator\AppleAppStore\JWT\TokenGenerator
 */
class TokenGeneratorTest extends TestCase
{
    /**
     * Verifies that a token is generated successfully with a valid configuration
     * and that its essential claims are set correctly.
     *
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
        $keyId = 'ABC123DEFG';

        $issuer = new TokenIssuer(
            $issuerId,
            $bundleId,
            new TokenKey($keyId, InMemory::plainText($signingKey)),
            new Sha256()
        );

        $config = TokenGeneratorConfig::forAppStore($issuer);
        $generator = new TokenGenerator($config);
        $token = $generator->generate();

        $this->assertNotNull($token);
        $this->assertEquals($issuerId, $token->claims()->get('iss'));
        $this->assertEquals($bundleId, $token->claims()->get('bid'));
    }
}
