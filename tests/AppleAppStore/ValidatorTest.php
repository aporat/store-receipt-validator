<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\Response;
use ReceiptValidator\AppleAppStore\Transaction;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;

class ValidatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testValidateReturnsResponse(): void
    {
        $json = file_get_contents(__DIR__ . '/fixtures/transactionHistoryResponse.json');

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], $json));

        $signingKey = file_get_contents(__DIR__ . '/certs/testSigningKey.p8');

        $validator = new Validator(
            signingKey: $signingKey,
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::SANDBOX
        );

        $validator->client = $mockClient;

        $response = $validator->validate('abc123');
        $this->assertInstanceOf(Response::class, $response);
    }

}
