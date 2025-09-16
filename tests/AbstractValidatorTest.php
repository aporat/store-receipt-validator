<?php
declare(strict_types=1);

namespace ReceiptValidator\Tests;

use GuzzleHttp\Client as HttpClient;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\Environment;

final class AbstractValidatorTest extends TestCase
{
    private const string PROD = 'https://api.example.com';
    private const string SANDBOX = 'https://sandbox.example.com';

    private function newValidator(): TestableValidator
    {
        return new TestableValidator();
    }

    public function testDefaultEnvironmentIsProduction(): void
    {
        $v = $this->newValidator();
        $this->assertSame(Environment::PRODUCTION, $v->getEnvironment());
    }

    public function testSetEnvironment(): void
    {
        $v = $this->newValidator();
        $v->setEnvironment(Environment::SANDBOX);
        $this->assertSame(Environment::SANDBOX, $v->getEnvironment());
    }

    public function testGetClientCreatesAndCachesClientForSameBaseUri(): void
    {
        $v = $this->newValidator();

        $c1 = $v->clientFor(self::PROD);
        $c2 = $v->clientFor(self::PROD);

        // Same instance when base URI is unchanged
        $this->assertSame($c1, $c2);

        // Class should remember the latest base URI we asked for
        $this->assertSame(self::PROD, $v->getBaseUri());
    }

    public function testGetClientRebuildsWhenBaseUriChanges(): void
    {
        $v = $this->newValidator();

        $c1 = $v->clientFor(self::PROD);
        $c2 = $v->clientFor(self::SANDBOX);

        // Different instance when base URI changes
        $this->assertNotSame($c1, $c2);

        // Base URI tracked on the validator should reflect the last call
        $this->assertSame(self::SANDBOX, $v->getBaseUri());
    }

    public function testSetHttpClientInjectionIsUsedUntilBaseUriChanges(): void
    {
        $v = $this->newValidator();

        $injected = new HttpClient(['base_uri' => self::PROD]);
        $v->setHttpClient($injected, self::PROD);

        // Uses the injected client while base URI matches
        $c1 = $v->clientFor(self::PROD);
        $this->assertSame($injected, $c1);

        // Switching base URI should drop the injected client and build a new one
        $c2 = $v->clientFor(self::SANDBOX);
        $this->assertNotSame($injected, $c2);
        $this->assertSame(self::SANDBOX, $v->getBaseUri());
    }
}

/**
 * Minimal concrete subclass to expose getClient() for testing.
 */
final class TestableValidator extends AbstractValidator
{
    public function validate(): string
    {
        return 'ok';
    }

    public function clientFor(string $baseUri): HttpClient
    {
        return $this->getClient($baseUri);
    }
}
