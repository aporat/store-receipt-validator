<?php

namespace ReceiptValidator\GooglePlay;

/**
 * @group library
 */
class GooglePlayPurchaseResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testParsedResponse()
    {
        $developerPayload = ['packageName' => 'testPackageName', 'etc' => 'testEtc'];
        $kind = 'testKind';
        $purchaseTimeMillis = '234346';

        // mock objects
        $productPurchaseMock = $this->getMockBuilder('\Google_Service_AndroidPublisher_ProductPurchase')
            ->disableOriginalConstructor()->getMock();

        $productPurchaseMock->consumptionState = PurchaseResponse::CONSUMPTION_STATE_YET_TO_BE_CONSUMED;
        $productPurchaseMock->developerPayload = json_encode($developerPayload);
        $productPurchaseMock->kind = $kind;
        $productPurchaseMock->purchaseState = PurchaseResponse::PURCHASE_STATE_CANCELED;
        $productPurchaseMock->purchaseTimeMillis = $purchaseTimeMillis;

        $productResponse = new PurchaseResponse($productPurchaseMock);

        // test abstract methods
        $this->assertInstanceOf('ReceiptValidator\GooglePlay\AbstractResponse', $productResponse);
        $this->assertEquals(PurchaseResponse::CONSUMPTION_STATE_YET_TO_BE_CONSUMED, $productResponse->getConsumptionState());
        $this->assertEquals($developerPayload, $productResponse->getDeveloperPayload());
        $this->assertEquals($kind, $productResponse->getKind());
        $this->assertEquals(PurchaseResponse::PURCHASE_STATE_CANCELED, $productResponse->getPurchaseState());
        $this->assertEquals($developerPayload['packageName'], $productResponse->getDeveloperPayloadElement('packageName'));
        $this->assertEquals($developerPayload['etc'], $productResponse->getDeveloperPayloadElement('etc'));
        $this->assertEquals('', $productResponse->getDeveloperPayloadElement('invalid'));
        // test own methods
        $this->assertEquals($purchaseTimeMillis, $productResponse->getPurchaseTimeMillis());
    }
}
