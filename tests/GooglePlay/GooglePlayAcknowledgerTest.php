<?php

namespace ReceiptValidator\Tests\GooglePlay;

use Google_Service_AndroidPublisher;
use Google_Service_AndroidPublisher_ProductPurchase;
use Google_Service_AndroidPublisher_Resource_PurchasesProducts;
use Google_Service_AndroidPublisher_Resource_PurchasesSubscriptions;
use Google_Service_AndroidPublisher_SubscriptionPurchase;
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
        $googleServiceAndroidPublisherMock = $this->getMockBuilder(Google_Service_AndroidPublisher::class)
                                                  ->disableOriginalConstructor()->getMock();

        // products
        $purchasesProductsMock = $this->getMockBuilder(
            Google_Service_AndroidPublisher_Resource_PurchasesProducts::class
        )
                                      ->disableOriginalConstructor()->getMock();
        $productPurchaseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_ProductPurchase::class)
                                    ->disableOriginalConstructor()->getMock();
        $productPurchaseMock->expects($this->any())->method('getAcknowledgementState')->willReturn(0);

        // subscriptions
        $purchasesSubscriptionsMock = $this->getMockBuilder(
            Google_Service_AndroidPublisher_Resource_PurchasesSubscriptions::class
        )
                                           ->disableOriginalConstructor()->getMock();
        $subscriptionPurchaseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_SubscriptionPurchase::class)
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
                                  new \Google_Service_AndroidPublisher_ProductPurchasesAcknowledgeRequest(
                                      ['developerPayload' => 'bar']
                                  )
                              );

        $purchasesSubscriptionsMock->expects($this->once())->method('acknowledge')
                                   ->with(
                                       $packageName,
                                       $productId,
                                       $purchaseToken,
                                       new \Google_Service_AndroidPublisher_SubscriptionPurchasesAcknowledgeRequest(
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
        $googleServiceAndroidPublisherMock = $this->getMockBuilder(Google_Service_AndroidPublisher::class)
                                                  ->disableOriginalConstructor()->getMock();

        // products
        $purchasesProductsMock = $this->getMockBuilder(
            Google_Service_AndroidPublisher_Resource_PurchasesProducts::class
        )
                                      ->disableOriginalConstructor()->getMock();
        $productPurchaseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_ProductPurchase::class)
                                    ->disableOriginalConstructor()->getMock();
        $productPurchaseMock->expects($this->any())->method('getAcknowledgementState')->willReturn(1);

        // subscriptions
        $purchasesSubscriptionsMock = $this->getMockBuilder(
            Google_Service_AndroidPublisher_Resource_PurchasesSubscriptions::class
        )
                                           ->disableOriginalConstructor()->getMock();
        $subscriptionPurchaseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_SubscriptionPurchase::class)
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
                                  new \Google_Service_AndroidPublisher_ProductPurchasesAcknowledgeRequest(
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
                                       new \Google_Service_AndroidPublisher_SubscriptionPurchasesAcknowledgeRequest(
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
        $googleServiceAndroidPublisherMock = $this->getMockBuilder(Google_Service_AndroidPublisher::class)
                                                  ->disableOriginalConstructor()
                                                  ->getMock();

        // subscriptions
        $purchasesSubscriptionsMock = $this->getMockBuilder(
            Google_Service_AndroidPublisher_Resource_PurchasesSubscriptions::class
        )
                                           ->disableOriginalConstructor()
                                           ->getMock();
        $subscriptionPurchaseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_SubscriptionPurchase::class)
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
                                       new \Google_Service_AndroidPublisher_SubscriptionPurchasesAcknowledgeRequest(
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
        $googleServiceAndroidPublisherMock = $this->getMockBuilder(Google_Service_AndroidPublisher::class)
                                                  ->disableOriginalConstructor()->getMock();

        // products
        $purchasesProductsMock = $this->getMockBuilder(
            Google_Service_AndroidPublisher_Resource_PurchasesProducts::class
        )
                                      ->disableOriginalConstructor()->getMock();
        $productPurchaseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_ProductPurchase::class)
                                    ->disableOriginalConstructor()->getMock();
        $productPurchaseMock->expects($this->any())->method('getAcknowledgementState')->willReturn(1);

        // mock expectations
        $googleServiceAndroidPublisherMock->purchases_products = $purchasesProductsMock;

        $purchasesProductsMock->expects($this->once())->method('acknowledge')
                              ->with(
                                  $packageName,
                                  $productId,
                                  $purchaseToken,
                                  new \Google_Service_AndroidPublisher_ProductPurchasesAcknowledgeRequest(
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
