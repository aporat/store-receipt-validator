<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
final class FinishTransactionTest extends TestCase
{
    private Validator $validator;
    private ClientInterface $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->createStub(ClientInterface::class);
        $signingKey       = (string) file_get_contents(__DIR__ . '/certs/testSigningKey.p8');

        $this->validator = new Validator(
            signingKey: $signingKey,
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::SANDBOX,
        );
        $this->validator->setHttpClient($this->mockClient);
    }

    public function testFinishTransactionCallsCorrectEndpoint(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getMethod() === 'POST'
                    && str_contains((string) $request->getUri(), '/inApps/v1/transactions/txn-abc123/finish');
            }))
            ->willReturn(new GuzzleResponse(200, [], ''));

        $this->validator->finishTransaction('txn-abc123');

        $this->addToAssertionCount(1);
    }

    public function testFinishTransactionThrowsOnApiError(): void
    {
        $this->mockClient
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(401, [], ''));

        $this->expectException(ValidationException::class);
        $this->validator->finishTransaction('txn-abc123');
    }
}
