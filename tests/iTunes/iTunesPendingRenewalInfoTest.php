<?php

namespace ReceiptValidator\Tests\iTunes;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\iTunes\PendingRenewalInfo;
use ReceiptValidator\RunTimeException;
use TypeError;

/**
 * @group library
 */
class iTunesPendingRenewalInfoTest extends TestCase
{
    public function testInvalidOptionsToConstructor(): void
    {
        $this->expectException(RunTimeException::class);
        $this->expectExceptionMessage('Response must be a scalar value');

        new PendingRenewalInfo(null);
    }

    public function testInvalidTypeToConstructor(): void
    {
        $this->expectException(TypeError::class);

        new PendingRenewalInfo('invalid');
    }

    public function testData(): void
    {
        $raw = [
            'auto_renew_product_id'      => 'Test_Subscription_1',
            'product_id'                 => 'Test_Subscription_2',
            'original_transaction_id'    => '1000000',
            'auto_renew_status'          => '0',
            'is_in_billing_retry_period' => '1',
            'expiration_intent'          => '1',
        ];

        $info = new PendingRenewalInfo($raw);

        $this->assertEquals(
            $raw['auto_renew_product_id'],
            $info->getAutoRenewProductId()
        );

        $this->assertEquals(
            $raw['product_id'],
            $info->getProductId()
        );

        $this->assertEquals(
            $raw['original_transaction_id'],
            $info->getOriginalTransactionId()
        );

        $this->assertEquals(
            false,
            $info->getAutoRenewStatus()
        );

        $this->assertEquals(
            $raw['is_in_billing_retry_period'],
            $info->isInBillingRetryPeriod()
        );

        $this->assertEquals(
            $raw['expiration_intent'],
            $info->getExpirationIntent()
        );
    }

    public function testComputedActiveStatus(): void
    {
        $raw = [
            'auto_renew_product_id'   => 'Test_Subscription_1',
            'product_id'              => 'Test_Subscription_2',
            'original_transaction_id' => '1000000',
            'auto_renew_status'       => '1',
        ];

        $info = new PendingRenewalInfo($raw);

        $this->assertEquals(
            PendingRenewalInfo::STATUS_ACTIVE,
            $info->getStatus()
        );
    }

    public function testComputedPendingStatus(): void
    {
        $raw = [
            'auto_renew_product_id'      => 'Test_Subscription_1',
            'product_id'                 => 'Test_Subscription_2',
            'original_transaction_id'    => '1000000',
            'auto_renew_status'          => '1',
            'expiration_intent'          => '5',
            'is_in_billing_retry_period' => '1',
        ];

        $info = new PendingRenewalInfo($raw);

        $this->assertEquals(
            PendingRenewalInfo::STATUS_PENDING,
            $info->getStatus()
        );
    }

    public function testComputedExpiredStatus(): void
    {
        $raw = [
            'auto_renew_product_id'      => 'Test_Subscription_1',
            'product_id'                 => 'Test_Subscription_2',
            'original_transaction_id'    => '1000000',
            'auto_renew_status'          => '1',
            'expiration_intent'          => '5',
            'is_in_billing_retry_period' => '0',
        ];

        $info = new PendingRenewalInfo($raw);

        $this->assertEquals(
            PendingRenewalInfo::STATUS_EXPIRED,
            $info->getStatus()
        );
    }

    public function testComputedGracePeriod(): void
    {
        $grace_period_expires_date = Carbon::tomorrow();
        $raw = [
            'auto_renew_product_id'         => 'Test_Subscription_1',
            'product_id'                    => 'Test_Subscription_2',
            'original_transaction_id'       => '1000000',
            'auto_renew_status'             => '1',
            'is_in_billing_retry_period'    => '1',
            'grace_period_expires_date'     => $grace_period_expires_date->toIso8601String().' Etc\/GMT',
            'grace_period_expires_date_ms'  => $grace_period_expires_date->getTimestamp() * 1000,
            'grace_period_expires_date_pst' => $grace_period_expires_date->timezone('America/Los_Angeles')->toIso8601String().'  America\/Los_Angeles',
        ];

        $grace_period_expires_date->timezone('Etc/GMT');

        $info = new PendingRenewalInfo($raw);

        $this->assertEquals(
            $grace_period_expires_date->toIso8601String(),
            $info->getGracePeriodExpiresDate()->toIso8601String()
        );

        $this->assertTrue($info->isInGracePeriod());
    }

    public function testComputedIsNotInGracePeriod(): void
    {
        $raw = [
            'auto_renew_product_id'         => 'Test_Subscription_1',
            'product_id'                    => 'Test_Subscription_2',
            'original_transaction_id'       => '1000000',
            'auto_renew_status'             => '1',
            'is_in_billing_retry_period'    => '0',
            'grace_period_expires_date'     => '2019-05-24 01:06:58 Etc\/GMT',
            'grace_period_expires_date_ms'  => 1432429618000,
            'grace_period_expires_date_pst' => '2019-05-23 18:06:58 America\/Los_Angeles',
        ];

        $info = new PendingRenewalInfo($raw);

        $this->assertEquals(
            '2015-05-24T01:06:58+00:00',
            $info->getGracePeriodExpiresDate()->toIso8601String()
        );

        $this->assertFalse($info->isInGracePeriod());
    }
}
