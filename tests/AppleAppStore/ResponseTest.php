<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\Response;
use ReceiptValidator\Environment;

/**
 * @group      apple-app-store
 * @coversDefaultClass \ReceiptValidator\AppleAppStore\Response
 */
class ResponseTest extends TestCase
{
    /**
     * Verifies that the constructor correctly parses a valid data array and
     * that all getters return the expected values.
     *
     * @covers ::__construct
     * @covers ::parse
     * @covers ::getRevision
     * @covers ::getBundleId
     * @covers ::getAppAppleId
     * @covers ::hasMore
     * @covers ::getEnvironment
     * @covers ::getTransactions
     */
    public function testParseWithValidTransaction(): void
    {
        $data = [
            'revision' => 'rev-1',
            'bundleId' => 'com.example.app',
            'appAppleId' => 123456789,
            'environment' => 'Production',
            'hasMore' => true
        ];

        $response = new Response($data);

        $this->assertSame('rev-1', $response->getRevision());
        $this->assertSame('com.example.app', $response->getBundleId());
        $this->assertSame(123456789, $response->getAppAppleId());
        $this->assertTrue($response->hasMore());
        $this->assertSame(Environment::PRODUCTION, $response->getEnvironment());
        $this->assertCount(0, $response->getTransactions());
    }

    /**
     * Verifies that creating a response with an empty data array is a valid
     * state and populates properties with their expected default values.
     *
     * @covers ::__construct
     * @covers ::parse
     */
    public function testEmptyResponseIsValid(): void
    {
        // An empty array should create a valid, empty response object without throwing an exception.
        $response = new Response([]);
        $this->assertCount(0, $response->getTransactions());
        $this->assertNull($response->getBundleId());
        // The environment defaults to SANDBOX if not present in the payload.
        $this->assertSame(Environment::SANDBOX, $response->getEnvironment());
    }
}
