<?php

namespace ReceiptValidator\Tests\GooglePlay;

use Google\Service\AndroidPublisher;
use Google\Service\AndroidPublisher\ProductPurchase;
use Google\Service\AndroidPublisher\Resource\PurchasesProducts;
use Google\Service\AndroidPublisher\Resource\PurchasesSubscriptions;
use Google\Service\AndroidPublisher\SubscriptionPurchase;
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
        $productResponseMock = $this->getMockBuilder(ProductPurchase::class)
            ->disableOriginalConstructor()->getMock();
        $subscriptionResponseMock = $this->getMockBuilder(SubscriptionPurchase::class)
            ->disableOriginalConstructor()->getMock();
        $googleServiceAndroidPublisherMock = $this->getMockBuilder(AndroidPublisher::class)
            ->disableOriginalConstructor()->getMock();
        $productPurchaseMock = $this->getMockBuilder(PurchasesProducts::class)
            ->disableOriginalConstructor()->getMock();
        $subscriptionPurchaseMock = $this->getMockBuilder(PurchasesSubscriptions::class)
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
