<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore\Asn1;

use phpseclib4\File\ASN1;
use phpseclib4\File\ASN1\Constructed;
use phpseclib4\File\ASN1\Maps\ContentInfo;
use phpseclib4\File\ASN1\Maps\SignedData;
use Stringable;
use Throwable;
use ValueError;

/**
 * Receipt decoder for phpseclib 4, whose ASN1::decodeBER() returns lazy Constructed
 * objects that must be bound to an ASN.1 map before they can be read.
 */
final class Phpseclib4AttributeSetDecoder implements AttributeSetDecoder
{
    /** PKCS #7: signedData OID (dotted form and phpseclib's symbolic name) */
    private const string PKCS7_OID = '1.2.840.113549.1.7.2';
    private const string PKCS7_OID_NAME = 'id-signedData';

    /**
     * SET OF SEQUENCE { type INTEGER, version INTEGER, value OCTET STRING }
     *
     * @var array<string, mixed>
     */
    private const array ATTRIBUTE_SET_MAP = [
        'type' => ASN1::TYPE_SET,
        'min' => 0,
        'max' => -1,
        'children' => [
            'type' => ASN1::TYPE_SEQUENCE,
            'children' => [
                'type'    => ['type' => ASN1::TYPE_INTEGER],
                'version' => ['type' => ASN1::TYPE_INTEGER],
                'value'   => ['type' => ASN1::TYPE_OCTET_STRING],
            ],
        ],
    ];

    public function decodePkcs7Receipt(string $der): array
    {
        try {
            $contentInfo = self::map($der, ContentInfo::MAP);
            $oid = self::toString($contentInfo['contentType']);
        } catch (Throwable $e) {
            throw new ValueError('Receipt is not a valid PKCS #7 container.', 0, $e);
        }

        if ($oid !== self::PKCS7_OID && $oid !== self::PKCS7_OID_NAME) {
            throw new ValueError('Receipt is not a valid PKCS #7 container.');
        }

        try {
            // ContentInfo.content is an EXPLICIT [0] wrapper around SignedData; re-decode its raw bytes.
            $content = $contentInfo['content'];
            if (!$content instanceof Stringable) {
                throw new ValueError('Could not find the receipt attribute set in the payload.');
            }

            $signedData = self::map((string) $content, SignedData::MAP);
            $encap = $signedData['encapContentInfo'];
            $eContent = $encap instanceof Constructed ? $encap['eContent'] : null;

            if (!$eContent instanceof Stringable) {
                throw new ValueError('Could not find the receipt attribute set in the payload.');
            }

            return $this->decodeAttributeSet((string) $eContent);
        } catch (Throwable $e) {
            throw new ValueError('Could not find the receipt attribute set in the payload.', 0, $e);
        }
    }

    public function decodeAttributeSet(string $ber): array
    {
        $pairs = [];

        try {
            foreach (self::map($ber, self::ATTRIBUTE_SET_MAP) as $attribute) {
                if (!$attribute instanceof Constructed) {
                    continue;
                }

                $pairs[] = [self::toString($attribute['type']), self::toString($attribute['value'])];
            }
        } catch (ValueError $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ValueError('Malformed BER data.', 0, $e);
        }

        return $pairs;
    }

    public function decodeScalar(string $ber): ?string
    {
        try {
            $content = ASN1::decodeBER($ber)['content'] ?? null;
        } catch (Throwable $e) {
            throw new ValueError('Malformed BER data.', 0, $e);
        }

        if ($content instanceof Constructed) {
            return null;
        }

        return $content instanceof Stringable || is_scalar($content) ? (string) $content : null;
    }

    /**
     * Decode BER and bind it to an ASN.1 map, requiring a constructed (SEQUENCE/SET) result.
     *
     * @param array<string, mixed> $mapping
     * @throws Throwable phpseclib exceptions on malformed input
     */
    private static function map(string $ber, array $mapping): Constructed
    {
        $mapped = ASN1::map(ASN1::decodeBER($ber), $mapping);

        if (!$mapped instanceof Constructed) {
            throw new ValueError('Unexpected ASN.1 structure: expected a constructed type.');
        }

        return $mapped;
    }

    private static function toString(mixed $value): string
    {
        if ($value instanceof Stringable || is_scalar($value)) {
            return (string) $value;
        }

        throw new ValueError('Unexpected ASN.1 structure: expected a scalar value.');
    }
}
