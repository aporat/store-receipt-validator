<?php

declare(strict_types=1);

namespace ReceiptValidator\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use ReceiptValidator\Environment;

trait ValueCasting
{
    /** @param array<string,mixed> $data */
    protected function toString(array $data, string $key, ?string $default = null): ?string
    {
        return isset($data[$key]) && $data[$key] !== '' ? (string) $data[$key] : $default;
    }
    /** @param array<string,mixed> $data */
    protected function toInt(array $data, string $key, ?int $default = null): ?int
    {
        return isset($data[$key]) && is_numeric($data[$key]) ? (int) $data[$key] : $default;
    }
    /** @param array<string,mixed> $data */
    protected function toBool(array $data, string $key, bool $default = false): bool
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }
        $v = filter_var($data[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $v ?? $default;
    }
    /** @param array<string,mixed> $data */
    protected function toDateFromMs(array $data, string $key): ?CarbonImmutable
    {
        $v = $data[$key] ?? null;
        if ($v === '' || !is_numeric($v)) {
            return null;
        }
        return CarbonImmutable::createFromTimestampMs((int)$v)->utc();
    }


    /**
     * Helper to convert a mixed value into an Environment enum.
     *
     * Accepts "Production", "Sandbox", lowercase variants, or Environment directly.
     * Falls back to PRODUCTION if invalid or missing.
     *
     * @param array<string, mixed> $data
     */
    final protected function toEnvironment(array $data, string $key, ?Environment $default = null): Environment
    {
        $v = $data[$key] ?? null;

        if ($v instanceof Environment) {
            return $v;
        }

        if (is_string($v)) {
            try {
                return Environment::fromString($v);
            } catch (InvalidArgumentException) {
                return $default ?? Environment::PRODUCTION;
            }
        }

        return $default ?? Environment::PRODUCTION;
    }
}
