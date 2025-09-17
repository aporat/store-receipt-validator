<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\Amazon;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\Validator as AmazonValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

#[CoversClass(AmazonValidator::class)]
final class ValidatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSetAndGetDeveloperSecretAndEndpoint(): void
    {
        $validator = new AmazonValidator('SECRET', Environment::SANDBOX);
        $validator->setUserId('user1');
        $validator->setReceiptId('receipt1');

        self::assertSame('SECRET', $validator->getDeveloperSecret());
        self::assertSame(Environment::SANDBOX, $validator->getEnvironment());
    }

    public function testValidateWithFixtureReturnsValidResponse(): void
    {
        $json = file_get_contents(__DIR__ . '/fixtures/validSubscriptionResponse.json');

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('GET', '/version/1.0/verifyReceiptId/developer/secret123/user/user123/receiptId/receipt123')
            ->andReturn(new GuzzleResponse(200, [], $json));

        $validator = new AmazonValidator('secret123', Environment::SANDBOX);
        $validator->setHttpClient($mockClient, AmazonValidator::ENDPOINT_SANDBOX);
        $validator->setUserId('user123')->setReceiptId('receipt123');

        $response = $validator->validate();

        self::assertSame('com.amazon.iapsamplev2.expansion_set_3', $response->getRawData()['productId']);
        self::assertSame(
            'q1YqVrJSSs7P1UvMTazKz9PLTCwoTswtyEktM9JLrShIzCvOzM-LL04tiTdW0lFKASo2NDEwMjCwMDM2MTC0AIqVAsUsLd1c4l18jIxdfTOK_N1d8kqLLHVLc8oK83OLgtPNCit9AoJdjJ3dXG2BGkqUrAxrAQ',
            $response->getRawData()['receiptId']
        );

        $tx = $response->getTransactions()[0];
        self::assertSame(1561104377, $tx->getFreeTrialEndDate()?->getTimestamp());
        self::assertSame(1561104377, $tx->getGracePeriodEndDate()?->getTimestamp());
    }

    public function testValidateEntitledPurchaseFixture(): void
    {
        $json = file_get_contents(__DIR__ . '/fixtures/entitledPurchaseResponse.json');

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('GET', '/version/1.0/verifyReceiptId/developer/secret123/user/user123/receiptId/receipt123')
            ->andReturn(new GuzzleResponse(200, [], $json));

        $validator = new AmazonValidator('secret123', Environment::SANDBOX);
        $validator->setHttpClient($mockClient, AmazonValidator::ENDPOINT_SANDBOX);
        $validator->setUserId('user123')->setReceiptId('receipt123');

        $response = $validator->validate();
        $tx = $response->getTransactions()[0];

        self::assertSame('com.amazon.iapsamplev2.expansion_set_3', $tx->getProductId());
        self::assertSame(
            'q1YqVrJSSs7P1UvMTazKz9PLTCwoTswtyEktM9JLrShIzCvOzM-LL04tiTdW0lFKASo2NDEwMjCwMDM2MTC0AIqVAsUsLd1c4l18jIxdfTOK_N1d8kqLLHVLc8oK83OLgtPNCit9AoJdjJ3dXG2BGkqUrAxrAQ',
            $tx->getTransactionId()
        );
        self::assertSame(1, $tx->getQuantity());
        self::assertSame(1402008634, $tx->getPurchaseDate()?->getTimestamp());
    }

    public function testValidateReturnsValidResponse(): void
    {
        $responseBody = json_encode([
            'productId'    => 'pack_100',
            'receiptId'    => 'txn_abc',
            'purchaseDate' => 1713350400000,
            'quantity'     => 1,
        ], JSON_THROW_ON_ERROR);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('GET', '/version/1.0/verifyReceiptId/developer/secret123/user/user123/receiptId/receipt123')
            ->andReturn(new GuzzleResponse(200, [], $responseBody));

        $validator = new AmazonValidator('secret123', Environment::SANDBOX);
        $validator->setHttpClient($mockClient, AmazonValidator::ENDPOINT_SANDBOX);
        $validator->setUserId('user123')->setReceiptId('receipt123');

        $response = $validator->validate();
        $tx = $response->getTransactions()[0];

        self::assertSame('pack_100', $tx->getProductId());
        self::assertSame('txn_abc', $tx->getTransactionId());
        self::assertSame(1, $tx->getQuantity());
    }

    public function testSetAndGetEnvironment(): void
    {
        $validator = new AmazonValidator('topsecret', Environment::PRODUCTION);
        self::assertSame(Environment::PRODUCTION, $validator->getEnvironment());

        $validator->setEnvironment(Environment::SANDBOX);
        self::assertSame(Environment::SANDBOX, $validator->getEnvironment());
    }

    public function testThrowsValidationExceptionOnNon200Response(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('GET', '/version/1.0/verifyReceiptId/developer/secret123/user/user123/receiptId/receipt123')
            ->andReturn(new GuzzleResponse(496, [], json_encode([
                'message' => 'Invalid developerSecret',
            ], JSON_THROW_ON_ERROR)));

        $validator = new AmazonValidator('secret123', Environment::SANDBOX);
        $validator->setHttpClient($mockClient, AmazonValidator::ENDPOINT_SANDBOX);
        $validator->setUserId('user123')->setReceiptId('receipt123');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Amazon API error [496]: Invalid developerSecret');

        $validator->validate();
    }
}
