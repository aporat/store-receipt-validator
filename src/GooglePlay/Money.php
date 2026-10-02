<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * An amount of money as Google's APIs represent it: whole units plus nanos.
 *
 * `units` is sent as a string (int64) and `nanos` as an integer in the range
 * -999,999,999 to 999,999,999 with the same sign as `units`, so 9.99 USD is
 * `{"currencyCode": "USD", "units": "9", "nanos": 990000000}`.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/Money
 */
final readonly class Money
{
    public function __construct(
        /** ISO 4217 currency code. */
        public string $currencyCode,
        /** Whole units of the currency. */
        public int $units = 0,
        /** Fraction of a unit, in billionths. */
        public int $nanos = 0,
    ) {
    }

    /**
     * Build from a decoded `Money` object, or null when it is absent or has no currency.
     */
    public static function fromArray(mixed $data): ?self
    {
        if (!is_array($data)) {
            return null;
        }

        $currency = $data['currencyCode'] ?? null;
        if (!is_string($currency) || $currency === '') {
            return null;
        }

        $units = isset($data['units']) && is_numeric($data['units']) ? (int) $data['units'] : 0;
        $nanos = isset($data['nanos']) && is_numeric($data['nanos']) ? (int) $data['nanos'] : 0;

        return new self($currency, $units, $nanos);
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getUnits(): int
    {
        return $this->units;
    }

    public function getNanos(): int
    {
        return $this->nanos;
    }

    /**
     * The amount as a float, e.g. 9.99. Fine for display; use {@see toDecimalString()}
     * or the raw units and nanos for arithmetic.
     */
    public function getAmount(): float
    {
        return $this->units + $this->nanos / 1_000_000_000;
    }

    /**
     * The amount in micros (millionths of a unit), the scale Google's older APIs use.
     */
    public function getAmountMicros(): int
    {
        return $this->units * 1_000_000 + intdiv($this->nanos, 1_000);
    }

    /**
     * The amount as an exact decimal string with trailing zeros trimmed, e.g. "9.99".
     */
    public function toDecimalString(): string
    {
        $negative = $this->units < 0 || $this->nanos < 0;
        $units    = abs($this->units);
        $fraction = rtrim(str_pad((string) abs($this->nanos), 9, '0', STR_PAD_LEFT), '0');
        $value    = $fraction === '' ? (string) $units : $units . '.' . $fraction;

        return $negative ? '-' . $value : $value;
    }

    public function isZero(): bool
    {
        return $this->units === 0 && $this->nanos === 0;
    }

    /**
     * @return array{currencyCode: string, units: string, nanos: int}
     */
    public function toArray(): array
    {
        return [
            'currencyCode' => $this->currencyCode,
            'units'        => (string) $this->units,
            'nanos'        => $this->nanos,
        ];
    }

    public function __toString(): string
    {
        return $this->toDecimalString() . ' ' . $this->currencyCode;
    }
}
