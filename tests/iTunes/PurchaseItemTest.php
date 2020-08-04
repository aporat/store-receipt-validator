<?php

namespace ReceiptValidator\Tests\iTunes;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\iTunes\PurchaseItem;
use ReceiptValidator\RunTimeException;
use TypeError;

/**
 * @group library
 */
class PurchaseItemTest extends TestCase
{
    public function testInvalidOptionsToConstructor(): void
    {
        $this->expectException(RunTimeException::class);
        $this->expectExceptionMessage('Response must be an array');

        new PurchaseItem(null);
    }

    public function testInvalidTypeToConstructor(): void
    {
        $this->expectException(TypeError::class);

        new PurchaseItem('invalid');
    }

    public function testPurchaseData(): void
    {
        $raw_data = [
            'is_trial_period'            => 'false',
            'original_purchase_date'     => '2015-05-24 01:06:58 Etc\/GMT',
            'original_purchase_date_ms'  => 1432429618000,
            'original_purchase_date_pst' => '2015-05-23 18:06:58 America\/Los_Angeles',
            'original_transaction_id'    => 1000000156455961,
            'product_id'                 => 'myapp.1',
            'purchase_date'              => '2015-05-24 01:06:58 Etc\/GMT',
            'purchase_date_ms'           => 1432429618000,
            'purchase_date_pst'          => '2015-05-23 18:06:58 America\/Los_Angeles',
            'quantity'                   => 1,
            'transaction_id'             => 1000000156455961,
        ];

        $info = new PurchaseItem($raw_data);

        $this->assertEquals(
            $raw_data['quantity'],
            $info->getQuantity()
        );

        $this->assertEquals(
            $raw_data['transaction_id'],
            $info->getTransactionId()
        );

        $this->assertEquals(
            $raw_data['original_transaction_id'],
            $info->getOriginalTransactionId()
        );

        $this->assertEquals(
            $raw_data['product_id'],
            $info->getProductId()
        );

        $this->assertEquals(
            '2015-05-24T01:06:58+00:00',
            $info->getPurchaseDate()->toIso8601String()
        );

        $this->assertEquals(
            '2015-05-24T01:06:58+00:00',
            $info->getOriginalPurchaseDate()->toIso8601String()
        );
    }

    public function testSubscriptionPurchaseData(): void
    {
        $raw_data = [
            'quantity'                   => '1',
            'product_id'                 => 'product.subscription',
            'transaction_id'             => '720000261479083',
            'original_transaction_id'    => '720000261479083',
            'purchase_date'              => '2018-06-14 05:41:29 Etc/GMT',
            'purchase_date_ms'           => 1528954889000,
            'purchase_date_pst'          => '2018-06-13 22:41:29 America/Los_Angeles',
            'original_purchase_date'     => '2018-06-14 05:41:31 Etc/GMT',
            'original_purchase_date_ms'  => 1528954891000,
            'original_purchase_date_pst' => '2018-06-13 22:41:31 America/Los_Angeles',
            'expires_date'               => '2018-06-21 05:41:29 Etc/GMT',
            'expires_date_ms'            => 1529559689000,
            'expires_date_pst'           => '2018-06-20 22:41:29 America/Los_Angeles',
            'web_order_line_item_id'     => 720000062004133,
            'is_trial_period'            => true,
            'is_in_intro_offer_period'   => false,
        ];

        $info = new PurchaseItem($raw_data);

        $this->assertEquals(
            $raw_data['quantity'],
            $info->getQuantity()
        );

        $this->assertEquals(
            '2018-06-21T05:41:29+00:00',
            $info->getExpiresDate()->toIso8601String()
        );

        $this->assertTrue(
            $info->isTrialPeriod()
        );

        $this->assertFalse(
            $info->isInIntroOfferPeriod()
        );

        $this->assertEquals(
            $raw_data['web_order_line_item_id'],
            $info->getWebOrderLineItemId()
        );
    }

    public function testPurchaseDataWithoutWebOrderLineItemId(): void
    {
        $raw_data = [
            'quantity'                   => '1',
            'product_id'                 => 'product.subscription',
            'transaction_id'             => '720000261479083',
            'original_transaction_id'    => '720000261479083',
            'purchase_date'              => '2018-06-14 05:41:29 Etc/GMT',
            'purchase_date_ms'           => 1528954889000,
            'purchase_date_pst'          => '2018-06-13 22:41:29 America/Los_Angeles',
            'original_purchase_date'     => '2018-06-14 05:41:31 Etc/GMT',
            'original_purchase_date_ms'  => 1528954891000,
            'original_purchase_date_pst' => '2018-06-13 22:41:31 America/Los_Angeles',
            'expires_date'               => '2018-06-21 05:41:29 Etc/GMT',
            'expires_date_ms'            => 1529559689000,
            'expires_date_pst'           => '2018-06-20 22:41:29 America/Los_Angeles',
            'web_order_line_item_id'     => null,
            'is_trial_period'            => true,
            'is_in_intro_offer_period'   => false,
        ];

        $info = new PurchaseItem($raw_data);

        $this->assertEquals(
            $raw_data['quantity'],
            $info->getQuantity()
        );

        $this->assertEquals(
            '2018-06-21T05:41:29+00:00',
            $info->getExpiresDate()->toIso8601String()
        );

        $this->assertTrue(
            $info->isTrialPeriod()
        );

        $this->assertFalse(
            $info->isInIntroOfferPeriod()
        );

        $this->assertEquals(
            null,
            $info->getWebOrderLineItemId()
        );
    }

    public function testPurchaseDataWithPromotionalOfferId(): void
    {
        $raw_data = [
            'quantity'                   => '1',
            'product_id'                 => 'product.subscription',
            'transaction_id'             => '720000261479083',
            'original_transaction_id'    => '720000261479083',
            'purchase_date'              => '2018-06-14 05:41:29 Etc/GMT',
            'purchase_date_ms'           => 1528954889000,
            'purchase_date_pst'          => '2018-06-13 22:41:29 America/Los_Angeles',
            'original_purchase_date'     => '2018-06-14 05:41:31 Etc/GMT',
            'original_purchase_date_ms'  => 1528954891000,
            'original_purchase_date_pst' => '2018-06-13 22:41:31 America/Los_Angeles',
            'expires_date'               => '2018-06-21 05:41:29 Etc/GMT',
            'expires_date_ms'            => 1529559689000,
            'expires_date_pst'           => '2018-06-20 22:41:29 America/Los_Angeles',
            'web_order_line_item_id'     => null,
            'is_trial_period'            => true,
            'is_in_intro_offer_period'   => false,
            'promotional_offer_id'       => 'PROMOOFFER',
        ];

        $info = new PurchaseItem($raw_data);

        $this->assertNotNull($info->getPromotionalOfferId());
        $this->assertEquals($raw_data['promotional_offer_id'], $info->getPromotionalOfferId());
    }
}
