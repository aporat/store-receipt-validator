<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\RenewalInfo;

/**
 * @coversDefaultClass \ReceiptValidator\AppleAppStore\RenewalInfo
 */
class RenewalInfoTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::parseData
     * @covers ::getAutoRenewProductId
     * @covers ::getAutoRenewStatus
     * @covers ::getOriginalTransactionId
     * @covers ::getExpirationIntentDate
     * @covers ::isInBillingRetryPeriod
     * @covers ::isUpgraded
     * @covers ::getPriceConsentStatus
     * @covers ::getGracePeriodExpiresDate
     * @covers ::getRenewalPrice
     * @covers ::getCurrency
     * @covers ::getOfferIdentifier
     * @covers ::getOfferType
     * @covers ::getOfferDiscountType
     * @covers ::getOfferPeriod
     * @covers ::getAppTransactionId
     * @covers ::getAppAccountToken
     * @covers ::getEligibleWinBackOfferIds
     * @covers ::getSignedDate
     * @covers ::getRecentSubscriptionStartDate
     * @covers ::getRenewalDate
     */
    public function testAllGettersWithFullData(): void
    {
        $data = [
            "expirationIntentDate" => 1698148900000,
            "originalTransactionId" => "12345",
            "autoRenewProductId" => "com.example.product.2",
            "autoRenewStatus" => 1,
            "isInBillingRetryPeriod" => true,
            "isUpgraded" => true,
            "priceConsentStatus" => 0,
            "gracePeriodExpiresDate" => 1698149000000,
            "renewalPrice" => 9990,
            "currency" => "USD",
            "offerIdentifier" => "abc.123",
            "offerType" => 2,
            "offerDiscountType" => "PAY_AS_YOU_GO",
            "offerPeriod" => "P1Y",
            "appTransactionId" => "71134",
            "appAccountToken" => "7e3fb20b-4cdb-47cc-936d-99d65f608138",
            "eligibleWinBackOfferIds" => ["eligible1", "eligible2"],
            "signedDate" => 1698148800000,
            "recentSubscriptionStartDate" => 1698148700000,
            "renewalDate" => 1698148850000,
        ];

        $info = new RenewalInfo($data);

        // Assertions for string values
        $this->assertSame("com.example.product.2", $info->getAutoRenewProductId());
        $this->assertSame("12345", $info->getOriginalTransactionId());
        $this->assertSame("USD", $info->getCurrency());
        $this->assertSame("abc.123", $info->getOfferIdentifier());
        $this->assertSame("PAY_AS_YOU_GO", $info->getOfferDiscountType());
        $this->assertSame("P1Y", $info->getOfferPeriod());
        $this->assertSame("71134", $info->getAppTransactionId());
        $this->assertSame("7e3fb20b-4cdb-47cc-936d-99d65f608138", $info->getAppAccountToken());

        // Assertions for boolean and integer values
        $this->assertTrue($info->getAutoRenewStatus());
        $this->assertTrue($info->isInBillingRetryPeriod());
        $this->assertTrue($info->isUpgraded());
        $this->assertSame(0, $info->getPriceConsentStatus());
        $this->assertSame(9990, $info->getRenewalPrice());
        $this->assertSame(2, $info->getOfferType());

        // Assertions for array values
        $this->assertSame(["eligible1", "eligible2"], $info->getEligibleWinBackOfferIds());

        // Assertions for Carbon dates
        $this->assertInstanceOf(Carbon::class, $info->getExpirationIntentDate());
        $this->assertSame(1698148900, $info->getExpirationIntentDate()->getTimestamp());

        $this->assertInstanceOf(Carbon::class, $info->getGracePeriodExpiresDate());
        $this->assertSame(1698149000, $info->getGracePeriodExpiresDate()->getTimestamp());

        $this->assertInstanceOf(Carbon::class, $info->getRenewalDate());
        $this->assertSame(1698148850, $info->getRenewalDate()->getTimestamp());

        $this->assertInstanceOf(Carbon::class, $info->getRecentSubscriptionStartDate());
        $this->assertSame(1698148700, $info->getRecentSubscriptionStartDate()->getTimestamp());

        $this->assertInstanceOf(Carbon::class, $info->getSignedDate());
        $this->assertSame(1698148800, $info->getSignedDate()->getTimestamp());
    }

    /**
     * @covers ::__construct
     * @covers ::parseData
     * @covers ::getAutoRenewProductId
     * @covers ::getAutoRenewStatus
     * @covers ::getOriginalTransactionId
     * @covers ::getExpirationIntentDate
     * @covers ::isInBillingRetryPeriod
     * @covers ::isUpgraded
     * @covers ::getPriceConsentStatus
     * @covers ::getGracePeriodExpiresDate
     * @covers ::getRenewalPrice
     * @covers ::getCurrency
     * @covers ::getOfferIdentifier
     * @covers ::getOfferType
     * @covers ::getOfferDiscountType
     * @covers ::getOfferPeriod
     * @covers ::getAppTransactionId
     * @covers ::getAppAccountToken
     * @covers ::getEligibleWinBackOfferIds
     * @covers ::getSignedDate
     * @covers ::getRecentSubscriptionStartDate
     * @covers ::getRenewalDate
     */
    public function testGettersWithMissingDataReturnsNull(): void
    {
        $data = [
            "originalTransactionId" => "123",
        ];

        $info = new RenewalInfo($data);

        $this->assertNull($info->getAutoRenewProductId());
        $this->assertNull($info->getAutoRenewStatus());
        $this->assertNull($info->getExpirationIntentDate());
        $this->assertNull($info->isInBillingRetryPeriod());
        $this->assertNull($info->isUpgraded());
        $this->assertNull($info->getPriceConsentStatus());
        $this->assertNull($info->getGracePeriodExpiresDate());
        $this->assertNull($info->getRenewalPrice());
        $this->assertNull($info->getCurrency());
        $this->assertNull($info->getOfferIdentifier());
        $this->assertNull($info->getOfferType());
        $this->assertNull($info->getOfferDiscountType());
        $this->assertNull($info->getOfferPeriod());
        $this->assertNull($info->getAppTransactionId());
        $this->assertNull($info->getAppAccountToken());
        $this->assertNull($info->getEligibleWinBackOfferIds());
        $this->assertNull($info->getSignedDate());
        $this->assertNull($info->getRecentSubscriptionStartDate());
        $this->assertNull($info->getRenewalDate());
    }
}
