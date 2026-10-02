<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore\Asn1;

use phpseclib3\File\ASN1;
use Throwable;
use ValueError;

/**
 * Receipt decoder for phpseclib 3, whose ASN1::decodeBER() returns nested arrays.
 */
final class Phpseclib3AttributeSetDecoder implements AttributeSetDecoder
{
    /** PKCS #7: signedData OID */
    private const string PKCS7_OID = '1.2.840.113549.1.7.2';

    public function decodePkcs7Receipt(string $der): array
    {
        $root = self::decodeBER($der);
        $sequence = $root[0]['content'] ?? null;

        $oid = $sequence[0]['content'] ?? null;
        if ($oid !== self::PKCS7_OID) {
            throw new ValueError('Receipt is not a valid PKCS #7 container.');
        }

        // ContentInfo.content[0] -> SignedData -> encapContentInfo[2] -> eContent[1] -> OCTET STRING[0]
        $data = $sequence[1]['content'][0]['content'][2]['content'][1]['content'][0]['content'] ?? null;
        if (!is_string($data)) {
            throw new ValueError('Could not find the receipt attribute set in the payload.');
        }

        try {
            return $this->decodeAttributeSet($data);
        } catch (ValueError $e) {
            throw new ValueError('Could not find the receipt attribute set in the payload.', 0, $e);
        }
    }

    public function decodeAttributeSet(string $ber): array
    {
        $decoded = self::decodeBER($ber);
        $set = $decoded[0]['content'] ?? null;

        if (!is_array($set)) {
            throw new ValueError('Unexpected ASN.1 structure: expected a SET of attributes.');
        }

        $pairs = [];

        foreach ($set as $attribute) {
            $type  = $attribute['content'][0]['content'] ?? null;
            $value = $attribute['content'][2]['content'] ?? null;

            if (!is_scalar($type) && !$type instanceof \Stringable) {
                continue;
            }

            if (!is_string($value)) {
                continue;
            }

            $pairs[] = [(string) $type, $value];
        }

        return $pairs;
    }

    public function decodeScalar(string $ber): ?string
    {
        $content = self::decodeBER($ber)[0]['content'] ?? null;

        if ($content instanceof \Stringable || is_scalar($content)) {
            return (string) $content;
        }

        return null;
    }

    /**
     * @return array<int, mixed>
     * @throws ValueError
     */
    private static function decodeBER(string $ber): array
    {
        try {
            $decoded = ASN1::decodeBER($ber);
        } catch (Throwable $e) {
            throw new ValueError('Malformed BER data.', 0, $e);
        }

        if (!is_array($decoded)) {
            throw new ValueError('Malformed BER data.');
        }

        return $decoded;
    }
}
