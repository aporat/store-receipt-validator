<?php

namespace ReceiptValidator\Tests\iTunes;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\iTunes\RenewalInfo;

class RenewalInfoTest extends TestCase
{
    public function testThrowsExceptionWithInvalidInput(): void
    {
        $this->expectException(ValidationException::class);
        new RenewalInfo(null);
    }

    public function testParsesBasicFieldsCorrectly(): void
    {
        $data = [
            'product_id' => 'com.app.sub1',
            'original_transaction_id' => 'tx_001',
            'auto_renew_product_id' => 'com.app.sub1',
            'auto_renew_status' => '1',
            'expiration_intent' => '2',
            'is_in_billing_retry_period' => '1',
        ];

        $info = new RenewalInfo($data);

        $this->assertSame('com.app.sub1', $info->getProductId());
        $this->assertSame('tx_001', $info->getOriginalTransactionId());
        $this->assertSame('com.app.sub1', $info->getAutoRenewProductId());
        $this->assertTrue($info->getAutoRenewStatus());
        $this->assertSame(2, $info->getExpirationIntent());
        $this->assertSame(1, $info->isInBillingRetryPeriod());
    }

    public function testStatusReturnsExpectedValues(): void
    {
        $active = new RenewalInfo([
            'product_id' => 'sub',
            'original_transaction_id' => 'id',
            'auto_renew_product_id' => 'sub',
            'auto_renew_status' => '1'
        ]);
        $this->assertSame(RenewalInfo::STATUS_ACTIVE, $active->getStatus());

        $pending = new RenewalInfo([
            'product_id' => 'sub',
            'original_transaction_id' => 'id',
            'auto_renew_product_id' => 'sub',
            'auto_renew_status' => '1',
            'expiration_intent' => '1',
            'is_in_billing_retry_period' => '1'
        ]);
        $this->assertSame(RenewalInfo::STATUS_PENDING, $pending->getStatus());

        $expired = new RenewalInfo([
            'product_id' => 'sub',
            'original_transaction_id' => 'id',
            'auto_renew_product_id' => 'sub',
            'auto_renew_status' => '1',
            'expiration_intent' => '1',
            'is_in_billing_retry_period' => '0'
        ]);
        $this->assertSame(RenewalInfo::STATUS_EXPIRED, $expired->getStatus());
    }

    public function testGracePeriodEvaluation(): void
    {
        $future = Carbon::now()->addDays(2);

        $info = new RenewalInfo([
            'product_id' => 'id',
            'original_transaction_id' => 'orig',
            'auto_renew_product_id' => 'id',
            'auto_renew_status' => '1',
            'is_in_billing_retry_period' => '1',
            'grace_period_expires_date_ms' => $future->getTimestamp() * 1000,
        ]);

        $this->assertTrue($info->isInGracePeriod());
        $this->assertSame($future->toIso8601String(), $info->getGracePeriodExpiresDate()->toIso8601String());
    }

    public function testGracePeriodExpired(): void
    {
        $past = Carbon::now()->subDays(2);

        $info = new RenewalInfo([
            'product_id' => 'id',
            'original_transaction_id' => 'orig',
            'auto_renew_product_id' => 'id',
            'auto_renew_status' => '1',
            'is_in_billing_retry_period' => '0',
            'grace_period_expires_date_ms' => $past->getTimestamp() * 1000,
        ]);

        $this->assertFalse($info->isInGracePeriod());
    }

    public function testArrayAccessWorks(): void
    {
        $data = [
            'product_id' => 'product.test',
            'original_transaction_id' => 'tx.test',
            'auto_renew_product_id' => 'product.test',
            'auto_renew_status' => '1',
            'expiration_intent' => '3',
            'is_in_billing_retry_period' => '1',
        ];

        $info = new RenewalInfo($data);

        $this->assertTrue(isset($info['product_id']));
        $this->assertSame('product.test', $info['product_id']);

        $info['expiration_intent'] = 4;
        $this->assertSame(4, $info->getExpirationIntent());

        unset($info['expiration_intent']);
        $this->assertNull($info['expiration_intent']);
    }
}
