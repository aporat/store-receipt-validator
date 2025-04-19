<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\Response;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

class ResponseTest extends TestCase
{
    public function testParseWithValidTransaction(): void
    {

        $data = [
            'revision' => 'rev-1',
            'bundleId' => 'com.example.app',
            'appAppleId' => 123456789,
            'environment' => 'Production',
            'hasMore' => true,
            'signedTransactions' => ['jws-token'],
        ];

        $response = new Response($data, Environment::PRODUCTION);

        $this->assertSame('rev-1', $response->getRevision());
        $this->assertSame('com.example.app', $response->getBundleId());
        $this->assertSame(123456789, $response->getAppAppleId());
        $this->assertTrue($response->hasMore());
        $this->assertSame(['jws-token'], $response->getSignedTransactions());
        $this->assertCount(0, $response->getTransactions());
    }

    public function testParseSkipsInvalidJws(): void
    {

        $data = [
            'environment' => 'Production',
            'signedTransactions' => ['bad-jws'],
        ];

        $response = new Response($data, Environment::PRODUCTION);
        $this->assertCount(0, $response->getTransactions());
    }

    public function testArrayAccessMethods(): void
    {
        $data = [
            'revision' => 'abc',
            'environment' => 'Sandbox',
        ];

        $response = new Response($data, Environment::SANDBOX);
        $this->assertSame('abc', $response['revision']);

        $response['foo'] = 'bar';
        $this->assertTrue(isset($response['foo']));
        $this->assertSame('bar', $response['foo']);

        unset($response['foo']);
        $this->assertFalse(isset($response['foo']));
    }

    public function testInvalidRawDataThrows(): void
    {
        $this->expectException(ValidationException::class);
        new Response(null, Environment::PRODUCTION);
    }
}
