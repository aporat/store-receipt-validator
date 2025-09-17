<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use Carbon\CarbonInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\RenewalInfo;

/**
 * @group apple-app-store
 */
#[CoversClass(RenewalInfo::class)]
final class RenewalInfoTest extends TestCase
{
    #[DataProvider('cases')]
    public function testRenewalInfoParsing(array $raw, array $exp): void
    {
        $info = new RenewalInfo($raw);

        // Scalars / strings
        self::assertSame($exp['autoRenewProductId'], $info->getAutoRenewProductId());
        self::assertSame($exp['originalTransactionId'], $info->getOriginalTransactionId());
        self::assertSame($exp['currency'], $info->getCurrency());
        self::assertSame($exp['offerIdentifier'], $info->getOfferIdentifier());
        self::assertSame($exp['offerDiscountType'], $info->getOfferDiscountType());
        self::assertSame($exp['offerPeriod'], $info->getOfferPeriod());
        self::assertSame($exp['appTransactionId'], $info->getAppTransactionId());
        self::assertSame($exp['appAccountToken'], $info->getAppAccountToken());

        // Booleans (non-nullable, default false)
        self::assertSame($exp['autoRenewStatus'], $info->getAutoRenewStatus());
        self::assertSame($exp['isInBillingRetryPeriod'], $info->isInBillingRetryPeriod());
        self::assertSame($exp['isUpgraded'], $info->isUpgraded());

        // Ints
        self::assertSame($exp['priceConsentStatus'], $info->getPriceConsentStatus());
        self::assertSame($exp['renewalPrice'], $info->getRenewalPrice());
        self::assertSame($exp['offerType'], $info->getOfferType());

        // Arrays
        self::assertSame($exp['eligibleWinBackOfferIds'], $info->getEligibleWinBackOfferIds());

        // Dates — compare by millisecond epoch (exact), or null
        $this->assertCarbonMsOrNull($exp['expirationIntentDateMs'], $info->getExpirationIntentDate());
        $this->assertCarbonMsOrNull($exp['gracePeriodExpiresDateMs'], $info->getGracePeriodExpiresDate());
        $this->assertCarbonMsOrNull($exp['renewalDateMs'], $info->getRenewalDate());
        $this->assertCarbonMsOrNull($exp['recentSubscriptionStartDateMs'], $info->getRecentSubscriptionStartDate());
        $this->assertCarbonMsOrNull($exp['signedDateMs'], $info->getSignedDate());
    }

    /**
     * Assert a Carbon date has the exact expected millisecond epoch, or both are null.
     */
    private function assertCarbonMsOrNull(?int $expectedMs, ?CarbonInterface $actual): void
    {
        if ($expectedMs === null) {
            self::assertNull($actual);
            return;
        }

        self::assertInstanceOf(CarbonInterface::class, $actual);
        // valueOf() returns ms as float; normalize to int
        $actualMs = (int) round($actual->valueOf());
        self::assertSame($expectedMs, $actualMs);
    }

    public static function cases(): array
    {
        $expMs  = 1698148900000;
        $grace  = 1698149000000;
        $renew  = 1698148850000;
        $recent = 1698148700000;
        $signed = 1698148800000;

        return [
            'full' => [
                'raw' => [
                    'expirationIntentDate'        => $expMs,
                    'originalTransactionId'       => '12345',
                    'autoRenewProductId'          => 'com.example.product.2',
                    'autoRenewStatus'             => 1,
                    'isInBillingRetryPeriod'      => true,
                    'isUpgraded'                  => true,
                    'priceConsentStatus'          => 0,
                    'gracePeriodExpiresDate'      => $grace,
                    'renewalPrice'                => 9990,
                    'currency'                    => 'USD',
                    'offerIdentifier'             => 'abc.123',
                    'offerType'                   => 2,
                    'offerDiscountType'           => 'PAY_AS_YOU_GO',
                    'offerPeriod'                 => 'P1Y',
                    'appTransactionId'            => '71134',
                    'appAccountToken'             => '7e3fb20b-4cdb-47cc-936d-99d65f608138',
                    'eligibleWinBackOfferIds'     => ['eligible1', 'eligible2'],
                    'signedDate'                  => $signed,
                    'recentSubscriptionStartDate' => $recent,
                    'renewalDate'                 => $renew,
                ],
                'exp' => [
                    'autoRenewProductId'           => 'com.example.product.2',
                    'originalTransactionId'        => '12345',
                    'currency'                     => 'USD',
                    'offerIdentifier'              => 'abc.123',
                    'offerDiscountType'            => 'PAY_AS_YOU_GO',
                    'offerPeriod'                  => 'P1Y',
                    'appTransactionId'             => '71134',
                    'appAccountToken'              => '7e3fb20b-4cdb-47cc-936d-99d65f608138',
                    'autoRenewStatus'              => true,
                    'isInBillingRetryPeriod'       => true,
                    'isUpgraded'                   => true,
                    'priceConsentStatus'           => 0,
                    'renewalPrice'                 => 9990,
                    'offerType'                    => 2,
                    'eligibleWinBackOfferIds'      => ['eligible1', 'eligible2'],
                    'expirationIntentDateMs'       => $expMs,
                    'gracePeriodExpiresDateMs'     => $grace,
                    'renewalDateMs'                => $renew,
                    'recentSubscriptionStartDateMs'=> $recent,
                    'signedDateMs'                 => $signed,
                ],
            ],
            'minimal' => [
                'raw' => [
                    'originalTransactionId' => 'xyz',
                ],
                'exp' => [
                    'autoRenewProductId'            => null,
                    'originalTransactionId'         => 'xyz',
                    'currency'                      => null,
                    'offerIdentifier'               => null,
                    'offerDiscountType'             => null,
                    'offerPeriod'                   => null,
                    'appTransactionId'              => null,
                    'appAccountToken'               => null,
                    'autoRenewStatus'               => false,
                    'isInBillingRetryPeriod'        => false,
                    'isUpgraded'                    => false,
                    'priceConsentStatus'            => null,
                    'renewalPrice'                  => null,
                    'offerType'                     => null,
                    'eligibleWinBackOfferIds'       => null,
                    'expirationIntentDateMs'        => null,
                    'gracePeriodExpiresDateMs'      => null,
                    'renewalDateMs'                 => null,
                    'recentSubscriptionStartDateMs' => null,
                    'signedDateMs'                  => null,
                ],
            ],
        ];
    }
}
