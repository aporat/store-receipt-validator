<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Support\Asn1Node;
use ValueError;

#[CoversClass(Asn1Node::class)]
final class Asn1NodeTest extends TestCase
{
    public function testDecodesDefiniteLengthSequence(): void
    {
        // SEQUENCE { INTEGER 5, OCTET STRING "ab" }
        $node = Asn1Node::decode("\x30\x07\x02\x01\x05\x04\x02ab");

        self::assertTrue($node->is(Asn1Node::TAG_SEQUENCE));
        self::assertTrue($node->constructed);
        self::assertCount(2, $node->children);
        self::assertSame('5', $node->child(0)?->integer());
        self::assertSame('ab', $node->child(1)?->octets());
        self::assertNull($node->child(2));
    }

    public function testDecodesLongFormLength(): void
    {
        $payload = str_repeat('x', 300);
        $node = Asn1Node::decode("\x04\x82\x01\x2c" . $payload . 'trailing bytes are ignored');

        self::assertTrue($node->is(Asn1Node::TAG_OCTET_STRING));
        self::assertSame($payload, $node->octets());
    }

    public function testDecodesIndefiniteLengthAndFlattensConstructedOctetString(): void
    {
        // SEQUENCE (indefinite) { [0] (indefinite) { OCTET STRING (constructed, indefinite) { "he", "llo" } } }
        $ber = "\x30\x80"
            . "\xa0\x80"
            . "\x24\x80" . "\x04\x02he" . "\x04\x03llo" . "\x00\x00"
            . "\x00\x00"
            . "\x00\x00";

        $node = Asn1Node::decode($ber);
        $explicit = $node->child(0);
        $octets = $explicit?->child(0);

        self::assertNotNull($explicit);
        self::assertTrue($explicit->is(0, Asn1Node::CLASS_CONTEXT_SPECIFIC));
        self::assertNotNull($octets);
        self::assertTrue($octets->is(Asn1Node::TAG_OCTET_STRING));
        self::assertTrue($octets->constructed);
        self::assertSame('hello', $octets->octets());
    }

    public function testDecodesHighTagNumbers(): void
    {
        // [APPLICATION 200] primitive, length 1
        $node = Asn1Node::decode("\x5f\x81\x48\x01\x00");

        self::assertTrue($node->is(200, Asn1Node::CLASS_APPLICATION));
        self::assertFalse($node->constructed);
    }

    #[DataProvider('integerProvider')]
    public function testDecodesIntegers(string $ber, string $expected): void
    {
        self::assertSame($expected, Asn1Node::decode($ber)->integer());
    }

    /** @return array<string, array{string, string}> */
    public static function integerProvider(): array
    {
        return [
            'zero' => ["\x02\x01\x00", '0'],
            'small' => ["\x02\x01\x11", '17'],
            'two bytes' => ["\x02\x02\x06\xa7", '1703'],
            'positive with leading zero' => ["\x02\x02\x00\xff", '255'],
            'negative' => ["\x02\x01\xff", '-1'],
            'negative two bytes' => ["\x02\x02\xfe\x00", '-512'],
            'max int64' => ["\x02\x08\x7f\xff\xff\xff\xff\xff\xff\xff", (string) PHP_INT_MAX],
            'min int64' => ["\x02\x08\x80\x00\x00\x00\x00\x00\x00\x00", (string) PHP_INT_MIN],
        ];
    }

    #[DataProvider('oidProvider')]
    public function testDecodesObjectIdentifiers(string $ber, string $expected): void
    {
        self::assertSame($expected, Asn1Node::decode($ber)->oid());
    }

    /** @return array<string, array{string, string}> */
    public static function oidProvider(): array
    {
        return [
            'pkcs7 signedData' => ["\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x07\x02", '1.2.840.113549.1.7.2'],
            'sha256' => ["\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x01", '2.16.840.1.101.3.4.2.1'],
            'joint-iso arc above 47' => ["\x06\x03\x88\x37\x03", '2.999.3'],
            'itu-t' => ["\x06\x01\x00", '0.0'],
        ];
    }

    #[DataProvider('malformedProvider')]
    public function testRejectsMalformedInput(string $ber): void
    {
        $this->expectException(ValueError::class);

        Asn1Node::decode($ber);
    }

    /** @return array<string, array{string}> */
    public static function malformedProvider(): array
    {
        return [
            'empty' => [''],
            'identifier only' => ["\x30"],
            'length exceeds data' => ["\x30\x82\xff\xff\x02\x01"],
            'long-form length truncated' => ["\x04\x82\x01"],
            'length too wide' => ["\x04\x88\x01\x01\x01\x01\x01\x01\x01\x01"],
            'indefinite length on primitive' => ["\x04\x80\x00\x00"],
            'indefinite length without end-of-contents' => ["\x30\x80\x02\x01\x05"],
            'child overruns parent' => ["\x30\x03\x02\x05\x05"],
            'high tag number truncated' => ["\x5f\x81"],
        ];
    }

    #[DataProvider('wrongTypeProvider')]
    public function testAccessorsRejectWrongTypes(string $ber, string $accessor): void
    {
        $node = Asn1Node::decode($ber);

        $this->expectException(ValueError::class);

        $node->{$accessor}();
    }

    /** @return array<string, array{string, string}> */
    public static function wrongTypeProvider(): array
    {
        return [
            'oid() on integer' => ["\x02\x01\x05", 'oid'],
            'oid() on empty oid' => ["\x06\x00", 'oid'],
            'oid() with truncated arc' => ["\x06\x02\x2a\x86", 'oid'],
            'integer() on octet string' => ["\x04\x01\x05", 'integer'],
            'integer() on empty integer' => ["\x02\x00", 'integer'],
            'integer() on constructed integer' => ["\x22\x03\x02\x01\x05", 'integer'],
            'integer() wider than 8 bytes' => ["\x02\x09\x00\xff\xff\xff\xff\xff\xff\xff\xff", 'integer'],
        ];
    }
}
