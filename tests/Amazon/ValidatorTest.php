<?php

namespace ReceiptValidator\Tests\Amazon;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\Validator as AmazonValidator;
use ReceiptValidator\Amazon\Response;

class ValidatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSetEndpoint(): void
    {
        $validator = new AmazonValidator();
        $validator->setDeveloperSecret('SECRET');

        $this->assertEquals('SECRET', $validator->getDeveloperSecret());

        $validator->setEndpoint(AmazonValidator::ENDPOINT_PRODUCTION);
        $this->assertEquals(AmazonValidator::ENDPOINT_PRODUCTION, $validator->getEndpoint());
    }

    public function testValidateReturnsValidResponse(): void
    {
        $responseBody = '{
            "betaProduct": false,
            "cancelDate": null,
            "parentProductId": null,
            "productId": "pack_100",
            "productType": "CONSUMABLE",
            "purchaseDate": 1485359133060,
            "quantity": 1,
            "receiptId": "M3qQCAiytxUzm3G05OworddJDiSi6ijXQGRFSK#AD=:1:11",
            "renewalDate": null,
            "term": null,
            "termSku": null,
            "testTransaction": false
        }';

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('GET', 'developer/secret123/user/user123/receiptId/receipt123')
            ->andReturn(new GuzzleResponse(200, [], $responseBody));

        $validator = new AmazonValidator(AmazonValidator::ENDPOINT_SANDBOX);
        $validator->setDeveloperSecret('secret123')
            ->setUserId('user123')
            ->setReceiptId('receipt123');

        $reflection = new \ReflectionClass($validator);
        $clientProp = $reflection->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($validator, $mockClient);

        $response = $validator->validate();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isValid());
        $this->assertEquals(Response::RESULT_OK, $response->getResultCode());
        $this->assertEquals('pack_100', $response->getPurchases()[0]->getProductId());
    }
}
