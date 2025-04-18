<?php

namespace ReceiptValidator\Tests\iTunes;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\iTunes\Transaction;

class TransactionTest extends TestCase
{
    public function testThrowsWithInvalidInput(): void
    {
        $this->expectException(ValidationException::class);
        new Transaction(null);
    }

    public function testParsesCoreFields(): void
    {
        $data = [
            'product_id' => 'product.id',
            'transaction_id' => 'tx_123',
            'original_transaction_id' => 'otx_456',
            'purchase_date_ms' => 1600000000000,
            'original_purchase_date_ms' => 1600000000000,
            'expires_date_ms' => 1605000000000,
            'quantity' => 1
        ];

        $item = new Transaction($data);

        $this->assertSame('product.id', $item->getProductId());
        $this->assertSame('tx_123', $item->getTransactionId());
        $this->assertSame('otx_456', $item->getOriginalTransactionId());
        $this->assertSame(1, $item->getQuantity());
        $this->assertEquals(Carbon::createFromTimestampUTC(1600000000), $item->getPurchaseDate());
        $this->assertEquals(Carbon::createFromTimestampUTC(1605000000), $item->getExpiresDate());
    }

    public function testTrialAndIntroFlags(): void
    {
        $data = [
            'product_id' => 'p',
            'transaction_id' => 't',
            'original_transaction_id' => 'ot',
            'purchase_date_ms' => 1600000000000,
            'original_purchase_date_ms' => 1600000000000,
            'expires_date_ms' => 1605000000000,
            'quantity' => 1,
            'is_trial_period' => 'true',
            'is_in_intro_offer_period' => 'false'
        ];

        $item = new Transaction($data);
        $this->assertTrue($item->isTrialPeriod());
        $this->assertFalse($item->isInIntroOfferPeriod());
    }

    public function testCancellationDate(): void
    {
        $ts = 1606000000;
        $data = [
            'product_id' => 'p',
            'transaction_id' => 't',
            'original_transaction_id' => 'ot',
            'purchase_date_ms' => 1600000000000,
            'original_purchase_date_ms' => 1600000000000,
            'expires_date_ms' => 1605000000000,
            'cancellation_date_ms' => $ts * 1000,
            'quantity' => 1
        ];

        $item = new Transaction($data);
        $this->assertEquals(Carbon::createFromTimestampUTC($ts)->toIso8601String(), $item->getCancellationDate()->toIso8601String());
    }

    public function testPromotionalOfferId(): void
    {
        $item = new Transaction([
            'product_id' => 'p',
            'transaction_id' => 't',
            'original_transaction_id' => 'ot',
            'purchase_date_ms' => 1600000000000,
            'original_purchase_date_ms' => 1600000000000,
            'expires_date_ms' => 1605000000000,
            'promotional_offer_id' => 'promo',
            'quantity' => 1
        ]);

        $this->assertSame('promo', $item->getPromotionalOfferId());
    }

    public function testArrayAccess(): void
    {
        $data = [
            'product_id' => 'product.id',
            'transaction_id' => 'tx_123',
            'original_transaction_id' => 'otx_456',
            'purchase_date_ms' => 1600000000000,
            'original_purchase_date_ms' => 1600000000000,
            'expires_date_ms' => 1605000000000,
            'quantity' => 1
        ];

        $item = new Transaction($data);
        $this->assertTrue(isset($item['product_id']));
        $this->assertSame('product.id', $item['product_id']);

        $item['product_id'] = 'updated.id';
        $this->assertSame('updated.id', $item->getProductId());

        unset($item['product_id']);
        $this->assertNull($item['product_id']);
    }
}
