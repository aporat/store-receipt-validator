<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\iTunes;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\iTunes\RenewalInfo;

#[CoversClass(RenewalInfo::class)]
final class RenewalInfoTest extends TestCase
{
    public function testThrowsExceptionWithInvalidInput(): void
    {
        $this->expectException(ValidationException::class);
        new RenewalInfo(null);
    }

    #[DataProvider('basicFieldsProvider')]
    public function testParsesBasicFieldsCorrectly(array $input, array $expected): void
    {
        $info = new RenewalInfo($input);

        self::assertSame($expected['productId'], $info->getProductId());
        self::assertSame($expected['originalTransactionId'], $info->getOriginalTransactionId());
        self::assertSame($expected['autoRenewProductId'], $info->getAutoRenewProductId());
        self::assertSame($expected['autoRenewStatus'], $info->getAutoRenewStatus());

        // intent is now handled semantically
        self::assertSame($expected['hasIntent'], $info->hasExpirationIntent());

        // retry flag is now a strict bool
        self::assertSame($expected['retryActive'], $info->isInBillingRetryPeriod());
    }

    public static function basicFieldsProvider(): array
    {
        return [
            'stringy input' => [
                'input' => [
                    'product_id'                 => 'com.app.sub1',
                    'original_transaction_id'    => 'tx_001',
                    'auto_renew_product_id'      => 'com.app.sub1',
                    'auto_renew_status'          => '1',
                    'expiration_intent'          => '2', // present -> hasIntent=true
                    'is_in_billing_retry_period' => '1', // -> true
                ],
                'expected' => [
                    'productId'             => 'com.app.sub1',
                    'originalTransactionId' => 'tx_001',
                    'autoRenewProductId'    => 'com.app.sub1',
                    'autoRenewStatus'       => true,
                    'hasIntent'             => true,
                    'retryActive'           => true,
                ],
            ],
            'missing optional fields' => [
                'input' => [
                    'product_id'              => 'x',
                    'original_transaction_id' => 'y',
                    'auto_renew_product_id'   => 'z',
                    // missing auto_renew_status, intent, retry flag
                ],
                'expected' => [
                    'productId'             => 'x',
                    'originalTransactionId' => 'y',
                    'autoRenewProductId'    => 'z',
                    'autoRenewStatus'       => false, // default
                    'hasIntent'             => false,
                    'retryActive'           => false,
                ],
            ],
        ];
    }

    #[DataProvider('statusProvider')]
    public function testStatusReturnsExpectedValues(array $input, string $expectedStatus): void
    {
        $info = new RenewalInfo($input);
        self::assertSame($expectedStatus, $info->getStatus());
    }

    public static function statusProvider(): array
    {
        return [
            'active' => [
                'input' => [
                    'product_id'              => 'sub',
                    'original_transaction_id' => 'id',
                    'auto_renew_product_id'   => 'sub',
                    'auto_renew_status'       => '1',
                ],
                'expectedStatus' => RenewalInfo::STATUS_ACTIVE,
            ],
            'pending (retry active)' => [
                'input' => [
                    'product_id'                 => 'sub',
                    'original_transaction_id'    => 'id',
                    'auto_renew_product_id'      => 'sub',
                    'auto_renew_status'          => '1',
                    'expiration_intent'          => '1',
                    'is_in_billing_retry_period' => '1',
                ],
                'expectedStatus' => RenewalInfo::STATUS_PENDING,
            ],
            'expired (intent present, retry inactive)' => [
                'input' => [
                    'product_id'                 => 'sub',
                    'original_transaction_id'    => 'id',
                    'auto_renew_product_id'      => 'sub',
                    'auto_renew_status'          => '1',
                    'expiration_intent'          => '1',
                    'is_in_billing_retry_period' => '0',
                ],
                'expectedStatus' => RenewalInfo::STATUS_EXPIRED,
            ],
            'expired (auto-renew off)' => [
                'input' => [
                    'product_id'                 => 'sub',
                    'original_transaction_id'    => 'id',
                    'auto_renew_product_id'      => 'sub',
                    'auto_renew_status'          => '0',
                ],
                'expectedStatus' => RenewalInfo::STATUS_EXPIRED,
            ],
        ];
    }

    public function testGracePeriodEvaluation(): void
    {
        $future = Carbon::now()->addDays(2);

        $info = new RenewalInfo([
            'product_id'                   => 'id',
            'original_transaction_id'      => 'orig',
            'auto_renew_product_id'        => 'id',
            'auto_renew_status'            => '1',
            'is_in_billing_retry_period'   => '1',
            'grace_period_expires_date_ms' => $future->getTimestamp() * 1000,
        ]);

        self::assertTrue($info->isInGracePeriod());
        $this->assertCarbonSec($future->getTimestamp(), $info->getGracePeriodExpiresDate());
    }

    public function testGracePeriodExpired(): void
    {
        $past = Carbon::now()->subDays(2);

        $info = new RenewalInfo([
            'product_id'                   => 'id',
            'original_transaction_id'      => 'orig',
            'auto_renew_product_id'        => 'id',
            'auto_renew_status'            => '1',
            'is_in_billing_retry_period'   => '0',
            'grace_period_expires_date_ms' => $past->getTimestamp() * 1000,
        ]);

        self::assertFalse($info->isInGracePeriod());
    }

    /** Assert the Carbon date matches expected seconds since epoch. */
    private function assertCarbonSec(int $expectedSec, ?CarbonInterface $actual): void
    {
        self::assertInstanceOf(CarbonInterface::class, $actual);
        self::assertSame($expectedSec, $actual->getTimestamp());
    }
}
