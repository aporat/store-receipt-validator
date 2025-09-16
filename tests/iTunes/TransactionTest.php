<?php

namespace ReceiptValidator\Tests\iTunes;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\iTunes\Transaction;

/**
 * @group      iTunes
 * @coversDefaultClass \ReceiptValidator\iTunes\Transaction
 */
class TransactionTest extends TestCase
{
    /**
     * Verifies that all getters return the correct data from a valid, fully populated
     * transaction payload.
     *
     * @covers ::__construct
     * @covers ::getQuantity
     * @covers ::getTransactionId
     * @covers ::getProductId
     * @covers ::getOriginalTransactionId
     * @covers ::getWebOrderLineItemId
     * @covers ::getPromotionalOfferId
     * @covers ::isTrialPeriod
     * @covers ::isInIntroOfferPeriod
     * @covers ::getPurchaseDate
     * @covers ::getOriginalPurchaseDate
     * @covers ::getExpiresDate
     * @covers ::getCancellationDate
     * @covers ::hasExpired
     * @covers ::wasCanceled
     */
    public function testParseAndGetters(): void
    {
        $now = Carbon::now();
        $rawData = [
            'quantity' => '1',
            'transaction_id' => 'tx123',
            'product_id' => 'com.example.product',
            'original_transaction_id' => 'otx456',
            'web_order_line_item_id' => 'line123',
            'promotional_offer_id' => 'promo789',
            'is_trial_period' => 'true',
            'is_in_intro_offer_period' => 'false',
            'purchase_date_ms' => $now->getTimestamp() * 1000,
            'original_purchase_date_ms' => $now->subDays(1)->getTimestamp() * 1000,
            'expires_date_ms' => $now->subSecond()->getTimestamp() * 1000, // Expired
            'cancellation_date_ms' => $now->addDays(2)->getTimestamp() * 1000,
        ];

        $transaction = new Transaction($rawData);

        $this->assertEquals(1, $transaction->getQuantity());
        $this->assertEquals('tx123', $transaction->getTransactionId());
        $this->assertEquals('com.example.product', $transaction->getProductId());
        $this->assertEquals('otx456', $transaction->getOriginalTransactionId());
        $this->assertEquals('line123', $transaction->getWebOrderLineItemId());
        $this->assertEquals('promo789', $transaction->getPromotionalOfferId());
        $this->assertTrue($transaction->isTrialPeriod());
        $this->assertFalse($transaction->isInIntroOfferPeriod());
        $this->assertInstanceOf(Carbon::class, $transaction->getPurchaseDate());
        $this->assertInstanceOf(Carbon::class, $transaction->getOriginalPurchaseDate());
        $this->assertInstanceOf(Carbon::class, $transaction->getExpiresDate());
        $this->assertInstanceOf(Carbon::class, $transaction->getCancellationDate());
        $this->assertTrue($transaction->hasExpired());
        $this->assertTrue($transaction->wasCanceled());
    }

    /**
     * Verifies that creating a Transaction with an empty data array is a valid
     * state and populates properties with their expected default values.
     *
     * @covers ::__construct
     */
    public function testEmptyDataIsValid(): void
    {
        $transaction = new Transaction([]);

        $this->assertNull($transaction->getTransactionId());
        $this->assertEquals(0, $transaction->getQuantity());
        $this->assertFalse($transaction->hasExpired());
    }
}
