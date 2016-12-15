<?php

namespace ReceiptValidator\GooglePlay;

/**
 * @group library
 */
class GooglePlaySubscriptionResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testParsedResponse()
    {
        $autoRenewing = 'testAutoRenewing';
        $cancelReason = 'testCancelReason';
        $countryCode = 'testCountryCode';
        $priceAmountMicros = 'testPriceAmountMicros';
        $priceCurrencyCode = 'testPriceCurrencyCode';
        $startTimeMillis = 'testStartTimeMillis';

        // mock objects
        $subscriptionPurchaseMock = $this->getMockBuilder('\Google_Service_AndroidPublisher_SubscriptionPurchase')
            ->disableOriginalConstructor()->getMock();

        $subscriptionPurchaseMock->autoRenewing = $autoRenewing;
        $subscriptionPurchaseMock->cancelReason = $cancelReason;
        $subscriptionPurchaseMock->countryCode = $countryCode;
        $subscriptionPurchaseMock->priceAmountMicros = $priceAmountMicros;
        $subscriptionPurchaseMock->priceCurrencyCode = $priceCurrencyCode;
        $subscriptionPurchaseMock->startTimeMillis = $startTimeMillis;

        $subscriptionResponse = new SubscriptionResponse($subscriptionPurchaseMock);

        $this->assertInstanceOf('ReceiptValidator\GooglePlay\AbstractResponse', $subscriptionResponse);
        $this->assertEquals($autoRenewing, $subscriptionResponse->getAutoRenewing());
        $this->assertEquals($cancelReason, $subscriptionResponse->getCancelReason());
        $this->assertEquals($countryCode, $subscriptionResponse->getCountryCode());
        $this->assertEquals($priceAmountMicros, $subscriptionResponse->getPriceAmountMicros());
        $this->assertEquals($priceCurrencyCode, $subscriptionResponse->getPriceCurrencyCode());
        $this->assertEquals($startTimeMillis, $subscriptionResponse->getStartTimeMillis());
        $this->assertEquals($subscriptionPurchaseMock, $subscriptionResponse->getRawResponse());
    }
}
