<?php

namespace ReceiptValidator\Tests\GooglePlay;

use Google_Service_AndroidPublisher_ProductPurchase;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\GooglePlay\AbstractResponse;
use ReceiptValidator\GooglePlay\PurchaseResponse;

/**
 * @group library
 */
class GooglePlayPurchaseResponseTest extends TestCase
{
    /**
     * @link https://developers.google.com/android-publisher/api-ref/purchases/products
     */
    public function testParsedResponse(): void
    {
        $developerPayload = ['packageName' => 'testPackageName', 'etc' => 'testEtc'];
        $kind = 'testKind';
        $purchaseTimeMillis = '234346';

        // mock objects
        $productPurchaseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_ProductPurchase::class)
            ->disableOriginalConstructor()->getMock();

        $productPurchaseMock->consumptionState = PurchaseResponse::CONSUMPTION_STATE_YET_TO_BE_CONSUMED;
        $productPurchaseMock->developerPayload = json_encode($developerPayload);
        $productPurchaseMock->kind = $kind;
        $productPurchaseMock->purchaseState = PurchaseResponse::PURCHASE_STATE_CANCELED;
        $productPurchaseMock->purchaseTimeMillis = $purchaseTimeMillis;

        $productResponse = new PurchaseResponse($productPurchaseMock);

        // test abstract methods
        $this->assertInstanceOf(AbstractResponse::class, $productResponse);
        $this->assertEquals(PurchaseResponse::CONSUMPTION_STATE_YET_TO_BE_CONSUMED, $productResponse->getConsumptionState());
        $this->assertEquals($developerPayload, $productResponse->getDeveloperPayload());
        $this->assertEquals($kind, $productResponse->getKind());
        $this->assertEquals(PurchaseResponse::PURCHASE_STATE_CANCELED, $productResponse->getPurchaseState());
        $this->assertEquals($developerPayload['packageName'], $productResponse->getDeveloperPayloadElement('packageName'));
        $this->assertEquals($developerPayload['etc'], $productResponse->getDeveloperPayloadElement('etc'));
        $this->assertEquals('', $productResponse->getDeveloperPayloadElement('invalid'));
        // test own methods
        $this->assertEquals($purchaseTimeMillis, $productResponse->getPurchaseTimeMillis());
        $this->assertEquals($productPurchaseMock, $productResponse->getRawResponse());
    }
}
