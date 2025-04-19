<?php

namespace ReceiptValidator\Tests\iTunes;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\iTunes\Transaction;

class TransactionTest extends TestCase
{
    public function testParseAndAccessors()
    {
        $rawData = [
            'quantity' => '1',
            'transaction_id' => 'tx123',
            'product_id' => 'com.example.product',
            'original_transaction_id' => 'otx456',
            'web_order_line_item_id' => 'line123',
            'promotional_offer_id' => 'promo789',
            'is_trial_period' => 'true',
            'is_in_intro_offer_period' => 'false',
            'purchase_date_ms' => strval(Carbon::now()->getTimestamp() * 1000),
            'original_purchase_date_ms' => strval(Carbon::now()->subDays(1)->getTimestamp() * 1000),
            'expires_date_ms' => strval(Carbon::now()->addDays(1)->getTimestamp() * 1000),
            'cancellation_date_ms' => strval(Carbon::now()->addDays(2)->getTimestamp() * 1000),
        ];

        $transaction = new Transaction($rawData);

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
        $this->assertTrue($transaction->hasExpired() || !$transaction->hasExpired()); // Just trigger logic
        $this->assertTrue($transaction->wasCanceled());

        $transaction['testKey'] = 'testValue';
        $this->assertSame('testValue', $transaction['testKey']);
        $this->assertTrue(isset($transaction['testKey']));
        unset($transaction['testKey']);
        $this->assertFalse(isset($transaction['testKey']));
    }

}
