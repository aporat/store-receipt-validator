<?php

namespace ReceiptValidator\Tests\GooglePlay;

use Google_Service_AndroidPublisher_SubscriptionPurchase;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\GooglePlay\AbstractResponse;
use ReceiptValidator\GooglePlay\SubscriptionResponse;

/**
 * @group library
 *
 * @link https://developers.google.com/android-publisher/api-ref/purchases/subscriptions
 */
class GooglePlaySubscriptionResponseTest extends TestCase
{
    public function testParsedResponse(): void
    {
        $autoRenewing = true;
        $cancelReason = 0;
        $countryCode = 'testCountryCode';
        $priceAmountMicros = 'testPriceAmountMicros';
        $priceCurrencyCode = 'testPriceCurrencyCode';
        $startTimeMillis = time() * 1000;
        $expiryTimeMillis = $startTimeMillis + 3600 * 24 * 7 * 1000;
        $userCancellationTimeMillis = $startTimeMillis + 3600 * 24 * 1000;
        $developerPayload = 'subs:developerPayload';
        $paymentState = 1;

        $data = [
            'autoRenewing'               => $autoRenewing,
            'cancelReason'               => $cancelReason,
            'countryCode'                => $countryCode,
            'priceAmountMicros'          => $priceAmountMicros,
            'priceCurrencyCode'          => $priceCurrencyCode,
            'startTimeMillis'            => $startTimeMillis,
            'expiryTimeMillis'           => $expiryTimeMillis,
            'userCancellationTimeMillis' => $userCancellationTimeMillis,
            'developerPayload'           => $developerPayload,
            'paymentState'               => $paymentState,
        ];

        $subscriptionPurchase = new Google_Service_AndroidPublisher_SubscriptionPurchase($data);
        $subscriptionResponse = new SubscriptionResponse($subscriptionPurchase);

        $this->assertInstanceOf(AbstractResponse::class, $subscriptionResponse);
        $this->assertEquals($autoRenewing, $subscriptionResponse->getAutoRenewing());
        $this->assertEquals($cancelReason, $subscriptionResponse->getCancelReason());
        $this->assertEquals($countryCode, $subscriptionResponse->getCountryCode());
        $this->assertEquals($priceAmountMicros, $subscriptionResponse->getPriceAmountMicros());
        $this->assertEquals($priceCurrencyCode, $subscriptionResponse->getPriceCurrencyCode());
        $this->assertEquals($startTimeMillis, $subscriptionResponse->getStartTimeMillis());
        $this->assertEquals($expiryTimeMillis, $subscriptionResponse->getExpiryTimeMillis());
        $this->assertEquals($userCancellationTimeMillis, $subscriptionResponse->getUserCancellationTimeMillis());
        $this->assertEquals($developerPayload, $subscriptionResponse->getDeveloperPayload());
        $this->assertEquals($paymentState, $subscriptionResponse->getPaymentState());
    }
}
