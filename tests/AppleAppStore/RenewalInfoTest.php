<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\RenewalInfo;

class RenewalInfoTest extends TestCase
{
    public function testAllGetters(): void
    {
        $data = [
            "expirationIntent" => 1,
            "originalTransactionId" => "12345",
            "autoRenewProductId" => "com.example.product.2",
            "productId" => "com.example.product",
            "autoRenewStatus" => 1,
            "isInBillingRetryPeriod" => true,
            "priceConsentStatus" => 0,
            "gracePeriodExpiresDate" => 1698148900000,
            "offerType" => 2,
            "offerIdentifier" => "abc.123",
            "signedDate" => 1698148800000,
            "environment" => "LocalTesting",
            "recentSubscriptionStartDate" => 1698148800000,
            "renewalDate" => 1698148850000,
            "renewalPrice" => 9990,
            "currency" => "USD",
            "offerDiscountType" => "PAY_AS_YOU_GO",
            "eligibleWinBackOfferIds" => ["eligible1", "eligible2"],
            "appTransactionId" => "71134",
            "offerPeriod" => "P1Y",
            "appAccountToken" => "7e3fb20b-4cdb-47cc-936d-99d65f608138",
        ];

        $info = new RenewalInfo($data);

        $this->assertSame("com.example.product.2", $info->getAutoRenewProductId());
        $this->assertTrue($info->getAutoRenewStatus());
        $this->assertSame("12345", $info->getOriginalTransactionId());
        $this->assertTrue($info->isInBillingRetryPeriod());
        $this->assertSame(0, $info->getPriceConsentStatus());
        $this->assertInstanceOf(Carbon::class, $info->getGracePeriodExpiresDate());
        $this->assertSame(9990, $info->getRenewalPrice());
        $this->assertSame("USD", $info->getCurrency());
        $this->assertSame("abc.123", $info->getOfferIdentifier());
        $this->assertSame(2, $info->getOfferType());
        $this->assertSame("PAY_AS_YOU_GO", $info->getOfferDiscountType());
        $this->assertSame("P1Y", $info->getOfferPeriod());
        $this->assertSame("71134", $info->getAppTransactionId());
        $this->assertSame("7e3fb20b-4cdb-47cc-936d-99d65f608138", $info->getAppAccountToken());
        $this->assertSame(["eligible1", "eligible2"], $info->getEligibleWinBackOfferIds());
        $this->assertInstanceOf(Carbon::class, $info->getRenewalDate());
        $this->assertInstanceOf(Carbon::class, $info->getRecentSubscriptionStartDate());
        $this->assertInstanceOf(Carbon::class, $info->getSignedDate());
    }
}
