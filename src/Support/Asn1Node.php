<?php

declare(strict_types=1);

namespace ReceiptValidator\Support;

use ValueError;

/**
 * Minimal BER/DER reader: enough of ITU-T X.690 to walk a PKCS #7 container
 * and the attribute sets inside an Apple app receipt.
 *
 * Supports definite (short and long form) and indefinite lengths, high tag
 * numbers, constructed strings (concatenated on read) and the universal types
 * the receipt uses. It performs no signature or PKI validation.
 */
final class Asn1Node
{
    public const int CLASS_UNIVERSAL = 0;
    public const int CLASS_APPLICATION = 1;
    public const int CLASS_CONTEXT_SPECIFIC = 2;
    public const int CLASS_PRIVATE = 3;

    public const int TAG_INTEGER = 2;
    public const int TAG_OCTET_STRING = 4;
    public const int TAG_OBJECT_IDENTIFIER = 6;
    public const int TAG_SEQUENCE = 16;
    public const int TAG_SET = 17;

    /** Longest INTEGER (in bytes) that fits a native PHP int. */
    private const int MAX_INTEGER_BYTES = 8;

    /**
     * @param list<self> $children Decoded children when constructed; empty for primitives.
     */
    private function __construct(
        public readonly int $class,
        public readonly int $tag,
        public readonly bool $constructed,
        public readonly string $content,
        public readonly array $children,
    ) {
    }

    /**
     * Decode the first BER element in the buffer. Trailing bytes are ignored.
     *
     * @throws ValueError on malformed or truncated input.
     */
    public static function decode(string $ber): self
    {
        if ($ber === '') {
            throw new ValueError('BER input is empty.');
        }

        $offset = 0;

        return self::read($ber, $offset, strlen($ber));
    }

    public function is(int $tag, int $class = self::CLASS_UNIVERSAL): bool
    {
        return $this->class === $class && $this->tag === $tag;
    }

    public function child(int $index): ?self
    {
        return $this->children[$index] ?? null;
    }

    /**
     * Content of an OCTET STRING (or any string type). Constructed strings are
     * flattened by concatenating their primitive segments in order.
     */
    public function octets(): string
    {
        if (!$this->constructed) {
            return $this->content;
        }

        $octets = '';
        foreach ($this->children as $child) {
            $octets .= $child->octets();
        }

        return $octets;
    }

    /**
     * Value of an INTEGER as a decimal string.
     *
     * @throws ValueError when the node is not a primitive INTEGER or is too wide for a native int.
     */
    public function integer(): string
    {
        if (!$this->is(self::TAG_INTEGER) || $this->constructed) {
            throw new ValueError('ASN.1 node is not an INTEGER.');
        }

        $length = strlen($this->content);
        if ($length === 0 || $length > self::MAX_INTEGER_BYTES) {
            throw new ValueError('ASN.1 INTEGER has an unsupported length.');
        }

        $first = ord($this->content[0]);
        $negative = ($first & 0x80) !== 0;

        $value = $negative ? -1 : 0;
        for ($i = 0; $i < $length; $i++) {
            $value = ($value << 8) | ord($this->content[$i]);
        }

        return (string) $value;
    }

    /**
     * Value of an OBJECT IDENTIFIER in dotted form, e.g. "1.2.840.113549.1.7.2".
     *
     * @throws ValueError when the node is not a primitive OBJECT IDENTIFIER or its encoding is invalid.
     */
    public function oid(): string
    {
        if (!$this->is(self::TAG_OBJECT_IDENTIFIER) || $this->constructed || $this->content === '') {
            throw new ValueError('ASN.1 node is not an OBJECT IDENTIFIER.');
        }

        $arcs = [];
        $subIdentifier = 0;
        $pending = false;

        foreach (str_split($this->content) as $char) {
            $byte = ord($char);

            if ($subIdentifier > (PHP_INT_MAX >> 7)) {
                throw new ValueError('ASN.1 OBJECT IDENTIFIER arc is too large.');
            }

            $subIdentifier = ($subIdentifier << 7) | ($byte & 0x7f);
            $pending = ($byte & 0x80) !== 0;

            if ($pending) {
                continue;
            }

            if ($arcs === []) {
                // The first sub-identifier packs the first two arcs: X*40 + Y,
                // where X is 0, 1, or 2 (and Y is unbounded only when X is 2).
                $first = min(intdiv($subIdentifier, 40), 2);
                $arcs[] = $first;
                $arcs[] = $subIdentifier - $first * 40;
            } else {
                $arcs[] = $subIdentifier;
            }

            $subIdentifier = 0;
        }

        if ($pending) {
            throw new ValueError('ASN.1 OBJECT IDENTIFIER is truncated.');
        }

        return implode('.', $arcs);
    }

    /**
     * Read one element starting at $offset, advancing $offset past it.
     *
     * @throws ValueError
     */
    private static function read(string $data, int &$offset, int $end): self
    {
        [$class, $constructed, $tag] = self::readIdentifier($data, $offset, $end);
        $length = self::readLength($data, $offset, $end);

        if ($length === null) {
            if (!$constructed) {
                throw new ValueError('ASN.1 primitive element uses an indefinite length.');
            }

            $start = $offset;
            $children = [];

            while (!self::atEndOfContents($data, $offset, $end)) {
                $children[] = self::read($data, $offset, $end);
            }

            $content = substr($data, $start, $offset - $start);
            $offset += 2;

            return new self($class, $tag, true, $content, $children);
        }

        if ($length > $end - $offset) {
            throw new ValueError('ASN.1 element length exceeds the available data.');
        }

        $contentEnd = $offset + $length;
        $content = substr($data, $offset, $length);
        $children = [];

        if ($constructed) {
            while ($offset < $contentEnd) {
                $children[] = self::read($data, $offset, $contentEnd);
            }
        }

        $offset = $contentEnd;

        return new self($class, $tag, $constructed, $content, $children);
    }

    /**
     * @return array{int, bool, int} [class, constructed, tag number]
     * @throws ValueError
     */
    private static function readIdentifier(string $data, int &$offset, int $end): array
    {
        $first = self::readByte($data, $offset, $end);

        $class = $first >> 6;
        $constructed = ($first & 0x20) !== 0;
        $tag = $first & 0x1f;

        if ($tag !== 0x1f) {
            return [$class, $constructed, $tag];
        }

        $tag = 0;
        do {
            if ($tag > (PHP_INT_MAX >> 7)) {
                throw new ValueError('ASN.1 tag number is too large.');
            }

            $byte = self::readByte($data, $offset, $end);
            $tag = ($tag << 7) | ($byte & 0x7f);
        } while (($byte & 0x80) !== 0);

        return [$class, $constructed, $tag];
    }

    /**
     * @return int|null Definite length in bytes, or null for the indefinite form.
     * @throws ValueError
     */
    private static function readLength(string $data, int &$offset, int $end): ?int
    {
        $first = self::readByte($data, $offset, $end);

        if ($first < 0x80) {
            return $first;
        }

        if ($first === 0x80) {
            return null;
        }

        $count = $first & 0x7f;
        if ($count > PHP_INT_SIZE - 1) {
            throw new ValueError('ASN.1 length is too large.');
        }

        $length = 0;
        for ($i = 0; $i < $count; $i++) {
            $length = ($length << 8) | self::readByte($data, $offset, $end);
        }

        return $length;
    }

    private static function atEndOfContents(string $data, int $offset, int $end): bool
    {
        if ($offset + 2 > $end) {
            throw new ValueError('ASN.1 indefinite-length element is missing its end-of-contents marker.');
        }

        return $data[$offset] === "\x00" && $data[$offset + 1] === "\x00";
    }

    /**
     * @throws ValueError
     */
    private static function readByte(string $data, int &$offset, int $end): int
    {
        if ($offset >= $end) {
            throw new ValueError('ASN.1 data is truncated.');
        }

        return ord($data[$offset++]);
    }
}
