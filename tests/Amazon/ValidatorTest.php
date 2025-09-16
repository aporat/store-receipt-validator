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

        $validator = Mockery::mock(AmazonValidator::class, ['secret123', Environment::SANDBOX])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setUserId('user123')->setReceiptId('receipt123');

        $response = $validator->validate();

        $this->assertEquals('com.amazon.iapsamplev2.expansion_set_3', $response->getRawData()['productId']);
        $this->assertEquals(
            'q1YqVrJSSs7P1UvMTazKz9PLTCwoTswtyEktM9JLrShIzCvOzM-LL04tiTdW0lFKASo2NDEwMjCwMDM2MTC0AIqVAsUsLd1c4l18jIxdfTOK_N1d8kqLLHVLc8oK83OLgtPNCit9AoJdjJ3dXG2BGkqUrAxrAQ',
            $response->getRawData()['receiptId']
        );

        $tx = $response->getTransactions()[0];

        // Compare at second precision (fixtures carry ms, validator now preserves ms)
        $this->assertSame(1561104377, $tx->getFreeTrialEndDate()?->getTimestamp());
        $this->assertSame(1561104377, $tx->getGracePeriodEndDate()?->getTimestamp());
    }

    public function testValidateEntitledPurchaseFixture(): void
    {
        $json = file_get_contents(__DIR__ . '/fixtures/entitledPurchaseResponse.json');

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('GET', '/version/1.0/verifyReceiptId/developer/secret123/user/user123/receiptId/receipt123')
            ->andReturn(new GuzzleResponse(200, [], $json));

        $validator = Mockery::mock(AmazonValidator::class, ['secret123', Environment::SANDBOX])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setUserId('user123')->setReceiptId('receipt123');

        $response = $validator->validate();

        $tx = $response->getTransactions()[0];

        $this->assertEquals('com.amazon.iapsamplev2.expansion_set_3', $tx->getProductId());
        $this->assertEquals(
            'q1YqVrJSSs7P1UvMTazKz9PLTCwoTswtyEktM9JLrShIzCvOzM-LL04tiTdW0lFKASo2NDEwMjCwMDM2MTC0AIqVAsUsLd1c4l18jIxdfTOK_N1d8kqLLHVLc8oK83OLgtPNCit9AoJdjJ3dXG2BGkqUrAxrAQ',
            $tx->getTransactionId()
        );
        $this->assertEquals(1, $tx->getQuantity());

        // Compare at second precision
        $this->assertSame(1402008634, $tx->getPurchaseDate()?->getTimestamp());
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

        $validator = Mockery::mock(AmazonValidator::class, ['secret123', Environment::SANDBOX])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setUserId('user123')->setReceiptId('receipt123');

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

        $validator = Mockery::mock(AmazonValidator::class, ['secret123', Environment::SANDBOX])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setUserId('user123')->setReceiptId('receipt123');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Amazon API error [496]: Invalid developerSecret');

        $validator->validate();
    }
}
