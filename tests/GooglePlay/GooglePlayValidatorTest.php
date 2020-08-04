<?php

namespace ReceiptValidator\Tests\GooglePlay;

use Google_Service_AndroidPublisher;
use Google_Service_AndroidPublisher_ProductPurchase;
use Google_Service_AndroidPublisher_Resource_PurchasesProducts;
use Google_Service_AndroidPublisher_Resource_PurchasesSubscriptions;
use Google_Service_AndroidPublisher_SubscriptionPurchase;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\GooglePlay\PurchaseResponse;
use ReceiptValidator\GooglePlay\SubscriptionResponse;
use ReceiptValidator\GooglePlay\Validator;

/**
 * @group library
 */
class GooglePlayValidatorTest extends TestCase
{
    public function testValidate(): void
    {
        $package = 'testPackage';
        $productId = '15';
        $purchaseToken = 'testPurchaseToken';

        // mock objects
        $productResponseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_ProductPurchase::class)
            ->disableOriginalConstructor()->getMock();
        $subscriptionResponseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_SubscriptionPurchase::class)
            ->disableOriginalConstructor()->getMock();
        $googleServiceAndroidPublisherMock = $this->getMockBuilder(Google_Service_AndroidPublisher::class)
            ->disableOriginalConstructor()->getMock();
        $productPurchaseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_Resource_PurchasesProducts::class)
            ->disableOriginalConstructor()->getMock();
        $subscriptionPurchaseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_Resource_PurchasesSubscriptions::class)
            ->disableOriginalConstructor()->getMock();

        // mock expectations
        $googleServiceAndroidPublisherMock->purchases_products = $productPurchaseMock;
        $googleServiceAndroidPublisherMock->purchases_subscriptions = $subscriptionPurchaseMock;

        $productPurchaseMock->expects($this->once())->method('get')
            ->with($package, $productId, $purchaseToken)->willReturn($productResponseMock);

        $subscriptionPurchaseMock->expects($this->once())->method('get')
            ->with($package, $productId, $purchaseToken)->willReturn($subscriptionResponseMock);

        $googlePlayValidator = (new Validator($googleServiceAndroidPublisherMock))
            ->setPackageName($package)
            ->setProductId($productId)
            ->setPurchaseToken($purchaseToken);

        $this->assertEquals(new PurchaseResponse($productResponseMock), $googlePlayValidator->validatePurchase());
        $this->assertEquals(
            new SubscriptionResponse($subscriptionResponseMock),
            $googlePlayValidator
            ->setValidationModePurchase(false)->validateSubscription()
        );
    }
}
