<?php

namespace ReceiptValidator\Tests;

use Google_Service_AndroidPublisher;
use Google_Service_AndroidPublisher_Resource_PurchasesProducts;
use Google_Service_AndroidPublisher_Resource_PurchasesSubscriptions;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\GooglePlay\Acknowledger;

/**
 * @group library
 */
class GooglePlayAcknowledgerTest extends TestCase
{
    public function testValidate(): void
    {
        $packageName = 'testPackage';
        $productId = '15';
        $purchaseToken = 'testPurchaseToken';

        // mock objects
        $googleServiceAndroidPublisherMock = $this->getMockBuilder(Google_Service_AndroidPublisher::class)
            ->disableOriginalConstructor()->getMock();
        $productPurchaseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_Resource_PurchasesProducts::class)
            ->disableOriginalConstructor()->getMock();
        $subscriptionPurchaseMock = $this->getMockBuilder(Google_Service_AndroidPublisher_Resource_PurchasesSubscriptions::class)
            ->disableOriginalConstructor()->getMock();

        // mock expectations
        $googleServiceAndroidPublisherMock->purchases_products = $productPurchaseMock;
        $googleServiceAndroidPublisherMock->purchases_subscriptions = $subscriptionPurchaseMock;

        $productPurchaseMock->expects($this->once())->method('acknowledge')
            ->with(
                $packageName,
                $productId,
                $purchaseToken,
                new \Google_Service_AndroidPublisher_ProductPurchasesAcknowledgeRequest(['developerPayload' => 'bar'])
            );

        $subscriptionPurchaseMock->expects($this->once())->method('acknowledge')
            ->with(
                $packageName,
                $productId,
                $purchaseToken,
                new \Google_Service_AndroidPublisher_SubscriptionPurchasesAcknowledgeRequest(['developerPayload' => 'foo'])
            );

        $googlePlayAcknowledger = new Acknowledger($googleServiceAndroidPublisherMock, $packageName, $productId, $purchaseToken);

        $googlePlayAcknowledger->acknowledge(Acknowledger::SUBSCRIPTION, 'foo');
        $googlePlayAcknowledger->acknowledge(Acknowledger::PRODUCT, 'bar');
    }
}
