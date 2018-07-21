<?php

use ReceiptValidator\iTunes\PendingRenewalInfo;
use PHPUnit\Framework\TestCase;

/**
 * @group library
 */
class iTunesPendingRenewalInfoTest extends TestCase
{

  public function testInvalidOptionsToConstructor(): void
  {
    $this->expectException(\ReceiptValidator\RunTimeException::class);

    new PendingRenewalInfo(null);
  }

  public function testInvalidTypeToConstructor(): void
  {
    $this->expectException('TypeError');

    new PendingRenewalInfo('invalid');
  }

  public function testData(): void
  {
    $raw = [
        'auto_renew_product_id' => 'Test_Subscription_1',
        'product_id' => 'Test_Subscription_2',
        'original_transaction_id' => '1000000',
        'auto_renew_status' => '0',
        'is_in_billing_retry_period' => '1',
        'expiration_intent' => '1'
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
        'auto_renew_product_id' => 'Test_Subscription_1',
        'product_id' => 'Test_Subscription_2',
        'original_transaction_id' => '1000000',
        'auto_renew_status' => '1'
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
        'auto_renew_product_id' => 'Test_Subscription_1',
        'product_id' => 'Test_Subscription_2',
        'original_transaction_id' => '1000000',
        'auto_renew_status' => '1',
        'expiration_intent' => '5',
        'is_in_billing_retry_period' => '1'
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
        'auto_renew_product_id' => 'Test_Subscription_1',
        'product_id' => 'Test_Subscription_2',
        'original_transaction_id' => '1000000',
        'auto_renew_status' => '1',
        'expiration_intent' => '5',
        'is_in_billing_retry_period' => '0'
    ];

    $info = new PendingRenewalInfo($raw);

    $this->assertEquals(
      PendingRenewalInfo::STATUS_EXPIRED,
      $info->getStatus()
    );
  }
}
