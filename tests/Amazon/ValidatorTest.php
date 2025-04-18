<?php

namespace ReceiptValidator\Tests\Amazon;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\Validator as AmazonValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

class ValidatorTest extends TestCase
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

        $this->assertEquals('SECRET', $validator->getDeveloperSecret());
        $this->assertEquals(Environment::SANDBOX, $validator->getEnvironment());
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
        $validator->setUserId('user123')->setReceiptId('receipt123');
        $validator->client = $mockClient;

        $response = $validator->validate();

        $this->assertEquals('com.amazon.iapsamplev2.expansion_set_3', $response->getRawData()['productId']);
        $this->assertEquals(
            'q1YqVrJSSs7P1UvMTazKz9PLTCwoTswtyEktM9JLrShIzCvOzM-LL04tiTdW0lFKASo2NDEwMjCwMDM2MTC0AIqVAsUsLd1c4l18jIxdfTOK_N1d8kqLLHVLc8oK83OLgtPNCit9AoJdjJ3dXG2BGkqUrAxrAQ',
            $response->getRawData()['receiptId']
        );

        $this->assertEquals(Carbon::createFromTimestampUTC(1561104377), $response->getTransactions()[0]->getFreeTrialEndDate());
        $this->assertEquals(Carbon::createFromTimestampUTC(1561104377), $response->getTransactions()[0]->getGracePeriodEndDate());
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
        $validator->setUserId('user123')->setReceiptId('receipt123');
        $validator->client = $mockClient;

        $response = $validator->validate();

        $this->assertEquals('com.amazon.iapsamplev2.expansion_set_3', $response->getTransactions()[0]->getProductId());
        $this->assertEquals(
            'q1YqVrJSSs7P1UvMTazKz9PLTCwoTswtyEktM9JLrShIzCvOzM-LL04tiTdW0lFKASo2NDEwMjCwMDM2MTC0AIqVAsUsLd1c4l18jIxdfTOK_N1d8kqLLHVLc8oK83OLgtPNCit9AoJdjJ3dXG2BGkqUrAxrAQ',
            $response->getTransactions()[0]->getTransactionId()
        );
        $this->assertEquals(1, $response->getTransactions()[0]->getQuantity());
        $this->assertEquals(Carbon::createFromTimestampUTC(1402008634), $response->getTransactions()[0]->getPurchaseDate());
    }

    public function testValidateReturnsValidResponse(): void
    {
        $responseBody = json_encode([
            'productId' => 'pack_100',
            'receiptId' => 'txn_abc',
            'purchaseDate' => 1713350400000,
            'quantity' => 1,
        ]);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('GET', '/version/1.0/verifyReceiptId/developer/secret123/user/user123/receiptId/receipt123')
            ->andReturn(new GuzzleResponse(200, [], $responseBody));

        $validator = new AmazonValidator('secret123', Environment::SANDBOX);
        $validator->setUserId('user123')->setReceiptId('receipt123');
        $validator->client = $mockClient;

        $response = $validator->validate();

        $this->assertEquals('pack_100', $response->getTransactions()[0]->getProductId());
        $this->assertEquals('txn_abc', $response->getTransactions()[0]->getTransactionId());
        $this->assertEquals(1, $response->getTransactions()[0]->getQuantity());
    }

    public function testSetAndGetEnvironment(): void
    {
        $validator = new AmazonValidator('topsecret', Environment::PRODUCTION);
        $this->assertEquals(Environment::PRODUCTION, $validator->getEnvironment());

        $validator->setEnvironment(Environment::SANDBOX);
        $this->assertEquals(Environment::SANDBOX, $validator->getEnvironment());
    }

    public function testThrowsValidationExceptionOnNon200Response(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('GET', '/version/1.0/verifyReceiptId/developer/secret123/user/user123/receiptId/receipt123')
            ->andReturn(new GuzzleResponse(496, [], json_encode([
                'message' => 'Invalid developerSecret'
            ])));

        $validator = new AmazonValidator('secret123', Environment::SANDBOX);
        $validator->setUserId('user123')->setReceiptId('receipt123');
        $validator->client = $mockClient;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid developer secret.');

        $validator->validate();
    }
}
