<?php
declare(strict_types=1);

namespace ReceiptValidator\Tests;

use GuzzleHttp\Client as GuzzleClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\Environment;

final class AbstractValidatorTest extends TestCase
{
    private const PROD    = 'https://api.example.com';
    private const SANDBOX = 'https://sandbox.example.com';

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

    public function testGetClientReturnsDefaultGuzzleInstance(): void
    {
        $v = $this->newValidator();
        $c = $v->getClientInstance();
        $this->assertInstanceOf(ClientInterface::class, $c);
        $this->assertInstanceOf(GuzzleClient::class, $c);
    }

    public function testGetClientReturnsSameInstanceOnSubsequentCalls(): void
    {
        $v = $this->newValidator();
        $c1 = $v->getClientInstance();
        $c2 = $v->getClientInstance();
        $this->assertSame($c1, $c2);
    }

    public function testSetHttpClientIsReturnedByGetClient(): void
    {
        $v = $this->newValidator();

        $injected = $this->createMock(ClientInterface::class);
        $v->setHttpClient($injected);

        $this->assertSame($injected, $v->getClientInstance());
    }

    public function testEndpointForEnvironmentResolvesFromMap(): void
    {
        $v = $this->newValidator();

        $this->assertSame(self::PROD, $v->exposeEndpointForEnvironment());

        $v->setEnvironment(Environment::SANDBOX);
        $this->assertSame(self::SANDBOX, $v->exposeEndpointForEnvironment());
    }
}

/**
 * Minimal concrete subclass to expose protected methods for testing.
 */
final class TestableValidator extends AbstractValidator
{
    private const string PROD    = 'https://api.example.com';
    private const string SANDBOX = 'https://sandbox.example.com';

    public function validate(): string
    {
        return 'ok';
    }

    public function getClientInstance(): ClientInterface
    {
        return $this->getClient();
    }

    public function exposeEndpointForEnvironment(): string
    {
        return $this->endpointForEnvironment();
    }

    /** @return array{production:string,sandbox:string} */
    protected function endpointMap(): array
    {
        return [
            Environment::PRODUCTION->value => self::PROD,
            Environment::SANDBOX->value    => self::SANDBOX,
        ];
    }
}
