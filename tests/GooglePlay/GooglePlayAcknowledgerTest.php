<?php

namespace ReceiptValidator\Tests\GooglePlay;

use Google\Service\AndroidPublisher;
use Google\Service\AndroidPublisher\ProductPurchase;
use Google\Service\AndroidPublisher\ProductPurchasesAcknowledgeRequest;
use Google\Service\AndroidPublisher\Resource\PurchasesProducts;
use Google\Service\AndroidPublisher\Resource\PurchasesSubscriptions;
use Google\Service\AndroidPublisher\SubscriptionPurchase;
use Google\Service\AndroidPublisher\SubscriptionPurchasesAcknowledgeRequest;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\GooglePlay\Acknowledger;

/**
 * @group library
 */
class GooglePlayAcknowledgerTest extends TestCase
{
    public function testValidateWithNonAcknowledgedPurchase(): void
    {
        $packageName = 'testPackage';
        $productId = '15';
        $purchaseToken = 'testPurchaseToken';

        // mock objects
        $googleServiceAndroidPublisherMock = $this->getMockBuilder(AndroidPublisher::class)
                                                  ->disableOriginalConstructor()->getMock();

        // products
        $purchasesProductsMock = $this->getMockBuilder(
            PurchasesProducts::class
        )
                                      ->disableOriginalConstructor()->getMock();
        $productPurchaseMock = $this->getMockBuilder(ProductPurchase::class)
                                    ->disableOriginalConstructor()->getMock();
        $productPurchaseMock->expects($this->any())->method('getAcknowledgementState')->willReturn(0);

        // subscriptions
        $purchasesSubscriptionsMock = $this->getMockBuilder(
            PurchasesSubscriptions::class
        )
                                           ->disableOriginalConstructor()->getMock();
        $subscriptionPurchaseMock = $this->getMockBuilder(SubscriptionPurchase::class)
                                         ->disableOriginalConstructor()->getMock();
        $subscriptionPurchaseMock->expects($this->any())->method('getAcknowledgementState')->willReturn(0);

        // mock expectations
        $googleServiceAndroidPublisherMock->purchases_products = $purchasesProductsMock;
        $googleServiceAndroidPublisherMock->purchases_subscriptions = $purchasesSubscriptionsMock;

        $purchasesProductsMock->expects($this->once())->method('acknowledge')
                              ->with(
                                  $packageName,
                                  $productId,
                                  $purchaseToken,
                                  new ProductPurchasesAcknowledgeRequest(
                                      ['developerPayload' => 'bar']
                                  )
                              );

        $purchasesSubscriptionsMock->expects($this->once())->method('acknowledge')
                                   ->with(
                                       $packageName,
                                       $productId,
                                       $purchaseToken,
                                       new SubscriptionPurchasesAcknowledgeRequest(
                                           ['developerPayload' => 'foo']
                                       )
                                   );

        $googlePlayAcknowledger = new Acknowledger(
            $googleServiceAndroidPublisherMock,
            $packageName,
            $productId,
            $purchaseToken
        );

        $googlePlayAcknowledger->acknowledge(Acknowledger::SUBSCRIPTION, 'foo');
        $googlePlayAcknowledger->acknowledge(Acknowledger::PRODUCT, 'bar');
    }

    public function testValidateWithAcknowledgedPurchaseAndImplicitStrategy(): void
    {
        $packageName = 'testPackage';
        $productId = '15';
        $purchaseToken = 'testPurchaseToken';

        // mock objects
        $googleServiceAndroidPublisherMock = $this->getMockBuilder(AndroidPublisher::class)
                                                  ->disableOriginalConstructor()->getMock();

        // products
        $purchasesProductsMock = $this->getMockBuilder(
            PurchasesProducts::class
        )
                                      ->disableOriginalConstructor()->getMock();
        $productPurchaseMock = $this->getMockBuilder(ProductPurchase::class)
                                    ->disableOriginalConstructor()->getMock();
        $productPurchaseMock->expects($this->any())->method('getAcknowledgementState')->willReturn(1);

        // subscriptions
        $purchasesSubscriptionsMock = $this->getMockBuilder(
            PurchasesSubscriptions::class
        )
                                           ->disableOriginalConstructor()->getMock();
        $subscriptionPurchaseMock = $this->getMockBuilder(SubscriptionPurchase::class)
                                         ->disableOriginalConstructor()->getMock();
        $subscriptionPurchaseMock->expects($this->any())->method('getAcknowledgementState')->willReturn(1);

        // mock expectations
        $googleServiceAndroidPublisherMock->purchases_products = $purchasesProductsMock;
        $googleServiceAndroidPublisherMock->purchases_subscriptions = $purchasesSubscriptionsMock;

        $purchasesProductsMock->expects($this->once())->method('get')
                              ->with(
                                  $packageName,
                                  $productId,
                                  $purchaseToken
                              )->willReturn($productPurchaseMock);
        $purchasesProductsMock->expects($this->never())->method('acknowledge')
                              ->with(
                                  $packageName,
                                  $productId,
                                  $purchaseToken,
                                  new ProductPurchasesAcknowledgeRequest(
                                      ['developerPayload' => 'bar']
                                  )
                              );

        $purchasesSubscriptionsMock->expects($this->once())->method('get')
                                   ->with(
                                       $packageName,
                                       $productId,
                                       $purchaseToken
                                   )->willReturn($subscriptionPurchaseMock);
        $purchasesSubscriptionsMock->expects($this->never())->method('acknowledge')
                                   ->with(
                                       $packageName,
                                       $productId,
                                       $purchaseToken,
                                       new SubscriptionPurchasesAcknowledgeRequest(
                                           ['developerPayload' => 'foo']
                                       )
                                   );

        $googlePlayAcknowledger = new Acknowledger(
            $googleServiceAndroidPublisherMock,
            $packageName,
            $productId,
            $purchaseToken,
            Acknowledger::ACKNOWLEDGE_STRATEGY_IMPLICIT
        );

        $googlePlayAcknowledger->acknowledge(Acknowledger::SUBSCRIPTION, 'foo');
        $googlePlayAcknowledger->acknowledge(Acknowledger::PRODUCT, 'bar');
    }

    public function testValidateWithAcknowledgedPurchaseAndExplicitStrategyForSubscription(): void
    {
        $packageName = 'testPackage';
        $productId = '15';
        $purchaseToken = 'testPurchaseToken';

        // mock objects
        $googleServiceAndroidPublisherMock = $this->getMockBuilder(AndroidPublisher::class)
                                                  ->disableOriginalConstructor()
                                                  ->getMock();

        // subscriptions
        $purchasesSubscriptionsMock = $this->getMockBuilder(
            PurchasesSubscriptions::class
        )
                                           ->disableOriginalConstructor()
                                           ->getMock();
        $subscriptionPurchaseMock = $this->getMockBuilder(SubscriptionPurchase::class)
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $subscriptionPurchaseMock->expects($this->any())->method('getAcknowledgementState')->willReturn(1);

        // mock expectations
        $googleServiceAndroidPublisherMock->purchases_subscriptions = $purchasesSubscriptionsMock;

        $purchasesSubscriptionsMock->expects($this->once())->method('acknowledge')
                                   ->with(
                                       $packageName,
                                       $productId,
                                       $purchaseToken,
                                       new SubscriptionPurchasesAcknowledgeRequest(
                                           ['developerPayload' => 'foo']
                                       )
                                   );

        $googlePlayAcknowledger = new Acknowledger(
            $googleServiceAndroidPublisherMock,
            $packageName,
            $productId,
            $purchaseToken
        );

        $googlePlayAcknowledger->acknowledge(Acknowledger::SUBSCRIPTION, 'foo');
    }

    public function testValidateWithAcknowledgedPurchaseAndExplicitStrategyForProduct(): void
    {
        $packageName = 'testPackage';
        $productId = '15';
        $purchaseToken = 'testPurchaseToken';

        // mock objects
        $googleServiceAndroidPublisherMock = $this->getMockBuilder(AndroidPublisher::class)
                                                  ->disableOriginalConstructor()->getMock();

        // products
        $purchasesProductsMock = $this->getMockBuilder(
            PurchasesProducts::class
        )
                                      ->disableOriginalConstructor()->getMock();
        $productPurchaseMock = $this->getMockBuilder(ProductPurchase::class)
                                    ->disableOriginalConstructor()->getMock();
        $productPurchaseMock->expects($this->any())->method('getAcknowledgementState')->willReturn(1);

        // mock expectations
        $googleServiceAndroidPublisherMock->purchases_products = $purchasesProductsMock;

        $purchasesProductsMock->expects($this->once())->method('acknowledge')
                              ->with(
                                  $packageName,
                                  $productId,
                                  $purchaseToken,
                                  new ProductPurchasesAcknowledgeRequest(
                                      ['developerPayload' => 'bar']
                                  )
                              );

        $googlePlayAcknowledger = new Acknowledger(
            $googleServiceAndroidPublisherMock,
            $packageName,
            $productId,
            $purchaseToken
        );

        $googlePlayAcknowledger->acknowledge(Acknowledger::PRODUCT, 'bar');
    }
}
