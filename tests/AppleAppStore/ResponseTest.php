<?php

namespace ReceiptValidator\AppleAppStore\Tests;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\Response;
use ReceiptValidator\Environment;

class ResponseTest extends TestCase
{
    public function testParseSetsPropertiesCorrectly()
    {
        $fixture = [
            'revision' => 'rev-123',
            'bundleId' => 'com.example.app',
            'appAppleId' => 123456789,
            'environment' => 'Production',
            'hasMore' => false,
            'signedTransactions' => []
        ];

        $response = new Response($fixture);
        $response->parse();

        $this->assertSame('rev-123', $response->getRevision());
        $this->assertSame('com.example.app', $response->getBundleId());
        $this->assertSame(123456789, $response->getAppAppleId());
        $this->assertSame(Environment::PRODUCTION, $response->getEnvironment());
        $this->assertFalse($response->hasMore());
        $this->assertIsArray($response->getSignedTransactions());
        $this->assertCount(0, $response->getSignedTransactions());
    }

    public function testOffsetAccess()
    {
        $fixture = ['revision' => 'rev-456'];
        $response = new Response($fixture);
        $response->parse();

        $this->assertTrue(isset($response['revision']));
        $this->assertEquals('rev-456', $response['revision']);

        $response['revision'] = 'rev-789';
        $this->assertEquals('rev-789', $response['revision']);

        unset($response['revision']);
        $this->assertFalse(isset($response['revision']));
    }
}
