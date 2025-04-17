<?php

namespace ReceiptValidator\Tests\Amazon;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\Response;
use ReceiptValidator\Amazon\PurchaseItem;
use ReceiptValidator\RunTimeException;

class ResponseTest extends TestCase
{
    public function testValidReceipt(): void
    {
        $receipt = json_decode(
            '{
                "betaProduct": false,
                "cancelDate": null,
                "parentProductId": null,
                "productId": "pack_100",
                "productType": "CONSUMABLE",
                "purchaseDate": 1485359133060,
                "quantity": 1,
                "receiptId": "M3qQCAiytxUzm3G05OworddJDiSi6ijXQGRFSK#AD=:1:11",
                "renewalDate": null,
                "term": null,
                "termSku": null,
                "testTransaction": false
            }',
            true
        );

        $response = new Response(Response::RESULT_OK, $receipt);

        $this->assertTrue($response->isValid());
        $this->assertEquals(Response::RESULT_OK, $response->getResultCode());
        $this->assertEquals($receipt, $response->getReceipt());

        $purchases = $response->getPurchases();
        $this->assertCount(1, $purchases);

        $purchase = $purchases[0];
        $this->assertInstanceOf(PurchaseItem::class, $purchase);
        $this->assertEquals('pack_100', $purchase->getProductId());
        $this->assertEquals('M3qQCAiytxUzm3G05OworddJDiSi6ijXQGRFSK#AD=:1:11', $purchase->getTransactionId());
        $this->assertEquals(1, $purchase->getQuantity());
    }

    public function testInvalidReceipt(): void
    {
        $response = new Response(Response::RESULT_INTERNAL_ERROR, []);
        $this->assertFalse($response->isValid());
        $this->assertEquals(Response::RESULT_INTERNAL_ERROR, $response->getResultCode());
        $this->assertIsArray($response->getReceipt());
        $this->assertCount(1, $response->getPurchases());
    }

    public function testJsonParsingThrowsOnInvalidData(): void
    {
        $this->expectException(\TypeError::class);
        $response = new Response();
        $response->parseJsonResponse('not-an-array');
    }

    public function testEmptyJsonStillInitializes(): void
    {
        $response = new Response(Response::RESULT_OK, []);
        $this->assertTrue($response->isValid());
        $this->assertIsArray($response->getReceipt());
        $this->assertCount(1, $response->getPurchases());
    }
}
