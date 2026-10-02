<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore\Asn1;

use ValueError;

/**
 * Decodes the ASN.1 structures inside an Apple app receipt.
 *
 * Implementations wrap a specific phpseclib major version so that
 * ReceiptUtility can work with whichever one is installed.
 */
interface AttributeSetDecoder
{
    /**
     * Decode the outer PKCS #7 container and return the receipt attribute set as [type, value] pairs.
     *
     * ContentInfo ::= SEQUENCE { contentType OID, content [0] EXPLICIT SignedData }
     * SignedData  ::= SEQUENCE { version, digestAlgorithms, encapContentInfo, ... }
     * EncapsulatedContentInfo ::= SEQUENCE { eContentType OID, eContent [0] EXPLICIT OCTET STRING }
     *
     * @return list<array{string, string}>
     * @throws ValueError when the input is not a PKCS #7 container or the attribute set cannot be found.
     */
    public function decodePkcs7Receipt(string $der): array;

    /**
     * Decode a BER-encoded attribute set into [type, value] pairs:
     * SET OF SEQUENCE { type INTEGER, version INTEGER, value OCTET STRING }.
     *
     * @return list<array{string, string}>
     * @throws ValueError on malformed input.
     */
    public function decodeAttributeSet(string $ber): array;

    /**
     * Decode a single BER-encoded primitive value to its string form.
     * Returns null when the element is constructed or has no scalar content.
     *
     * @throws ValueError on malformed input.
     */
    public function decodeScalar(string $ber): ?string;
}
