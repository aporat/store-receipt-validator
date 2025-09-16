<?php

namespace ReceiptValidator\Tests\iTunes;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\iTunes\Validator;

class ValidatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSetAndGetEnvironment(): void
    {
        $validator = new Validator('secret', Environment::SANDBOX);
        $this->assertEquals(Environment::SANDBOX, $validator->getEnvironment());

        $validator->setEnvironment(Environment::PRODUCTION);
        $this->assertEquals(Environment::PRODUCTION, $validator->getEnvironment());
    }

    public function testSetReceiptData(): void
    {
        $validator = new Validator('secret');
        $base64 = base64_encode('{"example":"json"}');

        $validator->setReceiptData('{"example":"json"}');
        $this->assertEquals($base64, $validator->getReceiptData());

        $validator->setReceiptData($base64);
        $this->assertEquals($base64, $validator->getReceiptData());
    }

    public function testSetAndGetSharedSecret(): void
    {
        $validator = new Validator('secret');
        $this->assertEquals('secret', $validator->getSharedSecret());
    }

    public function testValidateReturnsResponse(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status' => 0,
                'receipt' => ['app_item_id' => 123, 'in_app' => []]
            ])));

        $validator = Mockery::mock(Validator::class, ['secret'])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('abc');

        $response = $validator->validate();
        $this->assertIsArray($response->getRawData());
    }

    public function testRetryOnSandboxError(): void
    {
        $mockClient = Mockery::mock(Client::class);
        // Expect the first request (to production)
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode(['status' => 21007])));

        // Expect the second request (to sandbox)
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status' => 0,
                'receipt' => ['app_item_id' => 123, 'in_app' => []]
            ])));

        $validator = Mockery::mock(Validator::class, ['secret', Environment::PRODUCTION])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('xyz');

        $response = $validator->validate();
        $this->assertIsArray($response->getRawData());
    }

    public function testThrowsOnInvalidHttpStatus(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(500, [], 'Server error'));

        $validator = Mockery::mock(Validator::class, ['secret', Environment::PRODUCTION])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('test');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unable to get response from iTunes server');

        $validator->validate();
    }

    public function testInAppPurchaseResponseFromFixture(): void
    {
        $json = file_get_contents(__DIR__ . '/fixtures/inAppPurchaseResponse.json');
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], $json));

        $validator = Mockery::mock(Validator::class, ['secret', Environment::SANDBOX])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('dummy-data');

        $response = $validator->validate();

        $this->assertEquals('com.myapp', $response->getBundleId());
        $this->assertCount(2, $response->getTransactions());
        $this->assertEquals('myapp.1', $response->getTransactions()[0]->getProductId());
    }

    public function testInAppPurchaseInvalidReceiptResponseFromFixture(): void
    {
        $json = file_get_contents(__DIR__ . '/fixtures/inAppPurchaseInvalidReceiptResponse.json');
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], $json));

        $validator = Mockery::mock(Validator::class, ['secret', Environment::SANDBOX])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('dummy-data');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The data in the receipt-data property was malformed.');

        $validator->validate();
    }

    public function testThrowsValidationExceptionWithFormattedMessage(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status' => 21004,
            ])));

        $validator = Mockery::mock(Validator::class, ['invalid-shared-secret', Environment::PRODUCTION])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('dummy');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('iTunes API error [21004]: The shared secret you provided does not match the shared secret on file for your account.');

        $validator->validate();
    }
}
