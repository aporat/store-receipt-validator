<?php

use PHPUnit\Framework\Error\Notice;
use ReceiptValidator\iTunes\PendingRenewalInfo;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Constraint\IsType;

/**
 * @group library
 */
class iTunesPendingRenewalInfoTest extends TestCase
{

  public function testEmptyConstructor()
  {
    $this->expectException('TypeError');

    new PendingRenewalInfo();
  }

  public function testInvalidTypeToContructor()
  {
    $this->expectException('TypeError');

    new PendingRenewalInfo('invalid');
  }

  public function testFullObjectHydration()
  {
    $raw = [
        'auto_renew_product_id' => 'Test_Subscription_1',
        'product_id' => 'Test_Subscription_2',
        'original_transaction_id' => 'Transaction_1',
        'auto_renew_status' => '0',
        'is_in_billing_retry_period' => '1',
        'expiration_intent' => '1'
    ];
    $info = new PendingRenewalInfo($raw);

    $this->assertInternalType(IsType::TYPE_STRING, $info->getAutoRenewProductId());
    $this->assertEquals($raw['auto_renew_product_id'], $info->getAutoRenewProductId());

    $this->assertInternalType(IsType::TYPE_STRING, $info->getProductId());
    $this->assertEquals($raw['product_id'], $info->getProductId());

    $this->assertInternalType(IsType::TYPE_STRING, $info->getOriginalTransactionId());
    $this->assertEquals($raw['original_transaction_id'], $info->getOriginalTransactionId());

    $this->assertInternalType(IsType::TYPE_INT, $info->getAutoRenewStatus());
    $this->assertEquals($raw['auto_renew_status'], $info->getAutoRenewStatus());

    $this->assertInternalType(IsType::TYPE_INT, $info->getIsInBillingRetryPeriod());
    $this->assertEquals($raw['is_in_billing_retry_period'], $info->getIsInBillingRetryPeriod());

    $this->assertInternalType(IsType::TYPE_INT, $info->getExpirationIntent());
    $this->assertEquals($raw['expiration_intent'], $info->getExpirationIntent());
  }

  public function testPartialObjectHydration()
  {
    $raw = [
        'auto_renew_product_id' => 'Test_Subscription_1',
        'product_id' => 'Test_Subscription_2',
        'auto_renew_status' => '1'
    ];
    $info = new PendingRenewalInfo($raw);

    $this->assertInternalType(IsType::TYPE_STRING, $info->getAutoRenewProductId());
    $this->assertEquals($raw['auto_renew_product_id'], $info->getAutoRenewProductId());

    $this->assertInternalType(IsType::TYPE_STRING, $info->getProductId());
    $this->assertEquals($raw['product_id'], $info->getProductId());

    $this->assertInternalType(IsType::TYPE_INT, $info->getAutoRenewStatus());
    $this->assertEquals($raw['auto_renew_status'], $info->getAutoRenewStatus());

    $this->assertNull($info->getOriginalTransactionId());
    $this->assertNull($info->getIsInBillingRetryPeriod());
    $this->assertNull($info->getExpirationIntent());
  }

  public function testComputedUnknownStatus()
  {
    $raw = [
        'auto_renew_product_id' => 'Test_Subscription_1',
        'product_id' => 'Test_Subscription_2',
        'original_transaction_id' => 'Transaction_1',
        'auto_renew_status' => '0',
        'expiration_intent' => '1'
    ];
    $info = new PendingRenewalInfo($raw);
    $this->assertNull($info->getStatus());
  }

  public function testComputedActiveStatus()
  {
    $raw = [
        'auto_renew_product_id' => 'Test_Subscription_1',
        'product_id' => 'Test_Subscription_2',
        'original_transaction_id' => 'Transaction_1',
        'auto_renew_status' => '1'
    ];
    $info = new PendingRenewalInfo($raw);
    $this->assertEquals(PendingRenewalInfo::STATUS_ACTIVE, $info->getStatus());
  }

  public function testComputedPendingStatus()
  {
    $raw = [
        'auto_renew_product_id' => 'Test_Subscription_1',
        'product_id' => 'Test_Subscription_2',
        'original_transaction_id' => 'Transaction_1',
        'auto_renew_status' => '1',
        'expiration_intent' => '5',
        'is_in_billing_retry_period' => '1'
    ];
    $info = new PendingRenewalInfo($raw);
    $this->assertEquals(PendingRenewalInfo::STATUS_PENDING, $info->getStatus());
  }

  public function testComputedExpiredStatus()
  {
    $raw = [
        'auto_renew_product_id' => 'Test_Subscription_1',
        'product_id' => 'Test_Subscription_2',
        'original_transaction_id' => 'Transaction_1',
        'auto_renew_status' => '1',
        'expiration_intent' => '5',
        'is_in_billing_retry_period' => '0'
    ];
    $info = new PendingRenewalInfo($raw);
    $this->assertEquals(PendingRenewalInfo::STATUS_EXPIRED, $info->getStatus());
  }

  public function testBehavesLikeArray()
  {
    $raw = [
        'auto_renew_product_id' => 'Test_Subscription_1',
        'product_id' => 'Test_Subscription_2',
        'original_transaction_id' => 'Transaction_1',
        'auto_renew_status' => '0',
        'is_in_billing_retry_period' => '1',
        'expiration_intent' => '1'
    ];
    $info = new PendingRenewalInfo($raw);

    // Get existing
    $this->assertEquals('Test_Subscription_1', $info['auto_renew_product_id']);

    // Get non-existing
    $this->expectException(Notice::class);
    $info['undefined_value'];

    // Set existing
    $info['product_id'] = 'new_product_id';
    $this->assertEquals('new_product_id', $info['product_id']);
    $this->assertEquals('new_product_id', $info->getProductId());

    // Set new
    $info['undefined_value'] = 'test';
    $this->assertEquals('test', $info['undefined_value']);

    // Exists
    $this->assertEquals(true, $info['product_id']);
    $this->assertEquals(false, $info['another_undefined_value']);

    // Unset
    $info['unset_test'] = 'tmp';
    $this->assertEquals('tmp', $info['unset_test']);
    unset($info['unset_test']);
    $this->expectException(Notice::class);
    $info['unset_test'];
  }
}
