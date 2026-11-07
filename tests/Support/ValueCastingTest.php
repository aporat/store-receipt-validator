<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\Support;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\Support\ValueCasting;

#[CoversTrait(ValueCasting::class)]
final class ValueCastingTest extends TestCase
{
    /** @return object{env: callable, str: callable, int: callable, bool: callable, ms: callable, rfc: callable} */
    private static function caster(): object
    {
        return new class {
            use ValueCasting;

            /** @param array<string, mixed> $d */
            public function env(array $d, string $k, ?Environment $default = null): Environment
            {
                return $this->toEnvironment($d, $k, $default);
            }

            /** @param array<string, mixed> $d */
            public function str(array $d, string $k, ?string $default = null): ?string
            {
                return $this->toString($d, $k, $default);
            }

            /** @param array<string, mixed> $d */
            public function int(array $d, string $k, ?int $default = null): ?int
            {
                return $this->toInt($d, $k, $default);
            }

            /** @param array<string, mixed> $d */
            public function bool(array $d, string $k, bool $default = false): bool
            {
                return $this->toBool($d, $k, $default);
            }

            /** @param array<string, mixed> $d */
            public function ms(array $d, string $k): ?\Carbon\CarbonImmutable
            {
                return $this->toDateFromMs($d, $k);
            }

            /** @param array<string, mixed> $d */
            public function rfc(array $d, string $k): ?\Carbon\CarbonImmutable
            {
                return $this->toDateFromRfc3339($d, $k);
            }
        };
    }

    public function testToEnvironmentPassesThroughAnEnumInstance(): void
    {
        self::assertSame(Environment::SANDBOX, self::caster()->env(['e' => Environment::SANDBOX], 'e'));
    }

    public function testToEnvironmentParsesStringsCaseInsensitively(): void
    {
        $caster = self::caster();

        self::assertSame(Environment::SANDBOX, $caster->env(['e' => 'Sandbox'], 'e'));
        self::assertSame(Environment::PRODUCTION, $caster->env(['e' => ' PROD '], 'e'));
        self::assertSame(Environment::PRODUCTION, $caster->env(['e' => 'production'], 'e'));
    }

    public function testToEnvironmentFallsBackOnUnknownStrings(): void
    {
        $caster = self::caster();

        self::assertSame(Environment::PRODUCTION, $caster->env(['e' => 'staging'], 'e'));
        self::assertSame(Environment::SANDBOX, $caster->env(['e' => 'staging'], 'e', Environment::SANDBOX));
    }

    public function testToEnvironmentFallsBackWhenMissingOrNotAString(): void
    {
        $caster = self::caster();

        self::assertSame(Environment::PRODUCTION, $caster->env([], 'e'));
        self::assertSame(Environment::SANDBOX, $caster->env([], 'e', Environment::SANDBOX));
        self::assertSame(Environment::SANDBOX, $caster->env(['e' => 42], 'e', Environment::SANDBOX));
    }

    public function testScalarCasts(): void
    {
        $caster = self::caster();

        self::assertSame('5', $caster->str(['k' => 5], 'k'));
        self::assertSame('dflt', $caster->str(['k' => ''], 'k', 'dflt'));
        self::assertNull($caster->str([], 'k'));

        self::assertSame(12, $caster->int(['k' => '12'], 'k'));
        self::assertSame(7, $caster->int(['k' => 'abc'], 'k', 7));
        self::assertNull($caster->int([], 'k'));

        self::assertTrue($caster->bool(['k' => 'yes'], 'k'));
        self::assertFalse($caster->bool(['k' => 'off'], 'k', true));
        self::assertTrue($caster->bool(['k' => 'maybe'], 'k', true));
        self::assertTrue($caster->bool([], 'k', true));
    }

    public function testDateCasts(): void
    {
        $caster = self::caster();

        self::assertSame(1_700_000_000, $caster->ms(['k' => '1700000000000'], 'k')?->getTimestamp());
        self::assertNull($caster->ms(['k' => ''], 'k'));
        self::assertNull($caster->ms(['k' => 'soon'], 'k'));
        self::assertNull($caster->ms([], 'k'));

        self::assertSame('2026-09-01T12:34:56+00:00', $caster->rfc(['k' => '2026-09-01T12:34:56.789Z'], 'k')?->toIso8601String());
        self::assertNull($caster->rfc(['k' => '   '], 'k'));
        self::assertNull($caster->rfc(['k' => 'not a date at all'], 'k'));
        self::assertNull($caster->rfc(['k' => 123], 'k'));
    }
}
