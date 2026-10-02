<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore\Asn1;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\Asn1\AttributeSetDecoder;
use ReceiptValidator\AppleAppStore\Asn1\Phpseclib3AttributeSetDecoder;
use ReceiptValidator\AppleAppStore\Asn1\Phpseclib4AttributeSetDecoder;
use ValueError;

/**
 * Exercises whichever decoder matches the installed phpseclib major.
 * CI runs this suite once per supported major.
 */
#[CoversClass(Phpseclib3AttributeSetDecoder::class)]
#[CoversClass(Phpseclib4AttributeSetDecoder::class)]
final class AttributeSetDecoderTest extends TestCase
{
    private AttributeSetDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = class_exists('phpseclib4\File\ASN1')
            ? new Phpseclib4AttributeSetDecoder()
            : new Phpseclib3AttributeSetDecoder();
    }

    public function testDecodesAttributeSetIntoTypeValuePairs(): void
    {
        // SET { SEQUENCE { INTEGER 2, INTEGER 1, OCTET STRING "ab" }, SEQUENCE { INTEGER 1703, INTEGER 1, OCTET STRING "" } }
        $ber = "\x31\x17"
            . "\x30\x0a\x02\x01\x02\x02\x01\x01\x04\x02ab"
            . "\x30\x09\x02\x02\x06\xa7\x02\x01\x01\x04\x00";

        self::assertSame([['2', 'ab'], ['1703', '']], $this->decoder->decodeAttributeSet($ber));
    }

    public function testDecodeAttributeSetRejectsNonSet(): void
    {
        $this->expectException(ValueError::class);
        $this->decoder->decodeAttributeSet("\x02\x01\x05"); // INTEGER 5
    }

    public function testDecodeAttributeSetRejectsTruncatedInput(): void
    {
        $this->expectException(ValueError::class);
        $this->decoder->decodeAttributeSet("\x31\x82\xff\xff\x30");
    }

    public function testDecodeScalarReturnsPrimitiveContent(): void
    {
        self::assertSame('100000123', $this->decoder->decodeScalar("\x0c\x09100000123")); // UTF8String
        self::assertSame('7', $this->decoder->decodeScalar("\x02\x01\x07")); // INTEGER 7
    }

    public function testDecodeScalarReturnsNullForConstructed(): void
    {
        self::assertNull($this->decoder->decodeScalar("\x30\x03\x02\x01\x05")); // SEQUENCE { INTEGER 5 }
    }

    public function testDecodePkcs7ReceiptRejectsNonPkcs7(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('not a valid PKCS #7 container');
        $this->decoder->decodePkcs7Receipt("\x30\x03\x02\x01\x05");
    }

    public function testDecodePkcs7ReceiptRejectsContainerWithoutAttributeSet(): void
    {
        // ContentInfo { signedData OID, [0] { SEQUENCE {} } } — valid OID, nothing underneath
        $oid = "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x07\x02";
        $ber = "\x30" . chr(strlen($oid) + 4) . $oid . "\xa0\x02\x30\x00";

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Could not find the receipt attribute set');
        $this->decoder->decodePkcs7Receipt($ber);
    }
}
