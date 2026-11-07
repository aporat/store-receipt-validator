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

    public function testDecodeScalarRejectsMalformedInput(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Malformed BER data');
        $this->decoder->decodeScalar("\x30\x82\xff\xff");
    }

    public function testDecodeScalarRejectsEmptyInput(): void
    {
        $this->expectException(ValueError::class);
        $this->decoder->decodeScalar('');
    }

    public function testDecodePkcs7ReceiptRejectsOtherContentTypes(): void
    {
        // ContentInfo { id-data OID, [0] { OCTET STRING "" } }
        $ber = self::sequence(self::ID_DATA . "\xa0\x02\x04\x00");

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('not a valid PKCS #7 container');
        $this->decoder->decodePkcs7Receipt($ber);
    }

    public function testDecodePkcs7ReceiptRejectsContainerWithoutContent(): void
    {
        // ContentInfo { signedData OID } and nothing else
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Could not find the receipt attribute set');
        $this->decoder->decodePkcs7Receipt(self::sequence(self::ID_SIGNED_DATA));
    }

    public function testDecodePkcs7ReceiptRejectsSignedDataWithoutEncapsulatedContent(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Could not find the receipt attribute set');
        $this->decoder->decodePkcs7Receipt(self::signedDataContainer(null));
    }

    public function testDecodePkcs7ReceiptReturnsTheEmbeddedAttributeSet(): void
    {
        // SET { SEQUENCE { INTEGER 2, INTEGER 1, OCTET STRING "ab" } }
        $set = "\x31\x0c\x30\x0a\x02\x01\x02\x02\x01\x01\x04\x02ab";

        self::assertSame([['2', 'ab']], $this->decoder->decodePkcs7Receipt(self::signedDataContainer($set)));
    }

    public function testDecodePkcs7ReceiptRejectsEncapsulatedContentThatIsNotAnAttributeSet(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Could not find the receipt attribute set');
        $this->decoder->decodePkcs7Receipt(self::signedDataContainer("\x02\x01\x05")); // INTEGER 5
    }

    private const string ID_DATA        = "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x07\x01";
    private const string ID_SIGNED_DATA = "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x07\x02";

    /**
     * ContentInfo { signedData, [0] SignedData { version, digestAlgorithms,
     * encapContentInfo { id-data [, [0] OCTET STRING eContent] }, signerInfos } }
     */
    private static function signedDataContainer(?string $eContent): string
    {
        $encap = self::ID_DATA . ($eContent === null ? '' : self::tagged(0xA0, self::tagged(0x04, $eContent)));

        $signedData = self::sequence(
            "\x02\x01\x01"                 // version 1
            . "\x31\x00"                    // digestAlgorithms: empty SET
            . self::sequence($encap)        // encapContentInfo
            . "\x31\x00"                    // signerInfos: empty SET
        );

        return self::sequence(self::ID_SIGNED_DATA . self::tagged(0xA0, $signedData));
    }

    private static function sequence(string $content): string
    {
        return self::tagged(0x30, $content);
    }

    private static function tagged(int $tag, string $content): string
    {
        $length = strlen($content);
        if ($length < 0x80) {
            return chr($tag) . chr($length) . $content;
        }

        $lengthBytes = ltrim(pack('N', $length), "\x00");

        return chr($tag) . chr(0x80 | strlen($lengthBytes)) . $lengthBytes . $content;
    }
}
