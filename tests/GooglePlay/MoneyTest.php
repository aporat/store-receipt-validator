<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\GooglePlay\Money;

#[CoversClass(Money::class)]
final class MoneyTest extends TestCase
{
    public function testParsesGoogleRepresentation(): void
    {
        $money = Money::fromArray(['currencyCode' => 'USD', 'units' => '9', 'nanos' => 990000000]);

        self::assertInstanceOf(Money::class, $money);
        self::assertSame('USD', $money->getCurrencyCode());
        self::assertSame(9, $money->getUnits());
        self::assertSame(990000000, $money->getNanos());
        self::assertEqualsWithDelta(9.99, $money->getAmount(), 0.000001);
        self::assertSame(9990000, $money->getAmountMicros());
        self::assertSame('9.99', $money->toDecimalString());
        self::assertSame('9.99 USD', (string) $money);
        self::assertFalse($money->isZero());
        self::assertSame(['currencyCode' => 'USD', 'units' => '9', 'nanos' => 990000000], $money->toArray());
    }

    public function testDecimalStringEdgeCases(): void
    {
        self::assertSame('12', (new Money('EUR', 12))->toDecimalString());
        self::assertSame('0.5', (new Money('EUR', 0, 500000000))->toDecimalString());
        self::assertSame('0.000000001', (new Money('EUR', 0, 1))->toDecimalString());
        self::assertSame('-3.25', (new Money('EUR', -3, -250000000))->toDecimalString());
        self::assertSame('-0.1', (new Money('EUR', 0, -100000000))->toDecimalString());
        self::assertTrue((new Money('EUR'))->isZero());
        self::assertSame('0', (new Money('EUR'))->toDecimalString());
    }

    public function testMissingPartsDefaultToZero(): void
    {
        $money = Money::fromArray(['currencyCode' => 'JPY']);

        self::assertInstanceOf(Money::class, $money);
        self::assertSame(0, $money->getUnits());
        self::assertSame(0, $money->getNanos());
        self::assertTrue($money->isZero());
    }

    public function testRejectsMalformedInput(): void
    {
        self::assertNull(Money::fromArray(null));
        self::assertNull(Money::fromArray('9.99'));
        self::assertNull(Money::fromArray([]));
        self::assertNull(Money::fromArray(['units' => '9']));
        self::assertNull(Money::fromArray(['currencyCode' => '']));
    }
}
