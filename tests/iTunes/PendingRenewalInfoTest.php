<?php

namespace ReceiptValidator\Tests\iTunes;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\iTunes\PendingRenewalInfo;
use ReceiptValidator\RunTimeException;
use TypeError;

class PendingRenewalInfoTest extends TestCase
{
    public function testInvalidInputThrowsRuntime(): void
    {
        $this->expectException(RunTimeException::class);
        new PendingRenewalInfo(null);
    }

    public function testInvalidTypeThrowsTypeError(): void
    {
        $this->expectException(TypeError::class);
        new PendingRenewalInfo('invalid');
    }

    public function testBasicFieldsAreParsedCorrectly(): void
    {
        $raw = [
            'auto_renew_product_id' => 'sub_1',
            'product_id' => 'sub_2',
            'original_transaction_id' => 'txn_1234',
            'auto_renew_status' => '0',
            'is_in_billing_retry_period' => '1',
            'expiration_intent' => '2',
        ];

        $info = new PendingRenewalInfo($raw);

        $this->assertEquals('sub_1', $info->getAutoRenewProductId());
        $this->assertEquals('sub_2', $info->getProductId());
        $this->assertEquals('txn_1234', $info->getOriginalTransactionId());
        $this->assertFalse($info->getAutoRenewStatus());
        $this->assertEquals(1, $info->isInBillingRetryPeriod());
        $this->assertEquals(2, $info->getExpirationIntent());
    }

    public function testComputedStatuses(): void
    {
        $pending = new PendingRenewalInfo([
            'product_id' => 'sub',
            'original_transaction_id' => 'id',
            'auto_renew_product_id' => 'sub',
            'auto_renew_status' => '1',
            'expiration_intent' => '4',
            'is_in_billing_retry_period' => '1'
        ]);
        $this->assertEquals(PendingRenewalInfo::STATUS_PENDING, $pending->getStatus());

        $expired = new PendingRenewalInfo([
            'product_id' => 'sub',
            'original_transaction_id' => 'id',
            'auto_renew_product_id' => 'sub',
            'auto_renew_status' => '1',
            'expiration_intent' => '4',
            'is_in_billing_retry_period' => '0'
        ]);
        $this->assertEquals(PendingRenewalInfo::STATUS_EXPIRED, $expired->getStatus());

        $active = new PendingRenewalInfo([
            'product_id' => 'sub',
            'original_transaction_id' => 'id',
            'auto_renew_product_id' => 'sub',
            'auto_renew_status' => '1'
        ]);
        $this->assertEquals(PendingRenewalInfo::STATUS_ACTIVE, $active->getStatus());
    }

    public function testGracePeriodEvaluation(): void
    {
        $future = Carbon::tomorrow();

        $info = new PendingRenewalInfo([
            'product_id' => 'sub',
            'original_transaction_id' => 'id',
            'auto_renew_product_id' => 'sub',
            'auto_renew_status' => '1',
            'is_in_billing_retry_period' => '1',
            'grace_period_expires_date_ms' => $future->getTimestamp() * 1000,
        ]);

        $this->assertTrue($info->isInGracePeriod());
        $this->assertEquals($future->toIso8601String(), $info->getGracePeriodExpiresDate()->toIso8601String());
    }

    public function testGracePeriodExpired(): void
    {
        $past = Carbon::parse('2015-05-24T01:06:58+00:00');

        $info = new PendingRenewalInfo([
            'product_id' => 'sub',
            'original_transaction_id' => 'id',
            'auto_renew_product_id' => 'sub',
            'auto_renew_status' => '1',
            'is_in_billing_retry_period' => '0',
            'grace_period_expires_date_ms' => $past->getTimestamp() * 1000,
        ]);

        $this->assertFalse($info->isInGracePeriod());
        $this->assertEquals('2015-05-24T01:06:58+00:00', $info->getGracePeriodExpiresDate()->toIso8601String());
    }
}
