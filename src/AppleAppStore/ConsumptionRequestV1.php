<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

/**
 * Request body for the deprecated v1 Send Consumption Information endpoint.
 *
 * All fields are optional integer codes as defined by Apple. Prefer the v2
 * {@see ConsumptionRequest}; this class exists for callers that still need the
 * v1 request shape.
 *
 * @deprecated Use {@see ConsumptionRequest}, which targets the v2 endpoint.
 * @see https://developer.apple.com/documentation/appstoreserverapi/consumptionrequestv1
 */
final class ConsumptionRequestV1
{
    /** Whether the customer consented to provide consumption data to the App Store. */
    public ?bool $customerConsented = null;

    /**
     * The extent to which the customer consumed the in-app purchase.
     * 0 undeclared, 1 not consumed, 2 partially consumed, 3 fully consumed.
     */
    public ?int $consumptionStatus = null;

    /**
     * The platform on which the customer consumed the in-app purchase.
     * 0 undeclared, 1 Apple, 2 non-Apple.
     */
    public ?int $platform = null;

    /**
     * Whether you provided, prior to its purchase, a free sample or trial of the
     * content, or information about its functionality.
     */
    public ?bool $sampleContentProvided = null;

    /**
     * Whether the app successfully delivered an in-app purchase that works properly.
     * 0 delivered and working, 1 quality issue, 2 wrong item, 3 server outage,
     * 4 in-game currency change, 5 other reason.
     */
    public ?int $deliveryStatus = null;

    /** The UUID your app generated to map the purchase to the App Store transaction. */
    public ?string $appAccountToken = null;

    /**
     * The age of the customer's account.
     * 0 undeclared, 1 0-3 days, 2 3-10 days, 3 10-30 days, 4 30-90 days,
     * 5 90-180 days, 6 180-365 days, 7 over 365 days.
     */
    public ?int $accountTenure = null;

    /**
     * The amount of time the customer used the app.
     * 0 undeclared, 1 0-5 minutes, 2 5-60 minutes, 3 1-6 hours, 4 6-24 hours,
     * 5 1-4 days, 6 4-16 days, 7 over 16 days.
     */
    public ?int $playTime = null;

    /**
     * The total amount, in USD, of refunds the customer has received across all platforms.
     * 0 undeclared, 1 $0, 2 $0.01-$49.99, 3 $50-$99.99, 4 $100-$499.99,
     * 5 $500-$999.99, 6 $1000-$1999.99, 7 $2000 or more.
     */
    public ?int $lifetimeDollarsRefunded = null;

    /**
     * The total amount, in USD, of in-app purchases the customer has made across all platforms.
     * 0 undeclared, 1 $0, 2 $0.01-$49.99, 3 $50-$99.99, 4 $100-$499.99,
     * 5 $500-$999.99, 6 $1000-$1999.99, 7 $2000 or more.
     */
    public ?int $lifetimeDollarsPurchased = null;

    /**
     * The status of the customer's account.
     * 0 undeclared, 1 active, 2 suspended, 3 terminated, 4 limited access.
     */
    public ?int $userStatus = null;

    /**
     * Your preference as to whether Apple should grant the refund.
     * 0 undeclared, 1 prefer grant, 2 prefer decline, 3 no preference.
     */
    public ?int $refundPreference = null;

    public function __construct(?bool $customerConsented = null, ?bool $sampleContentProvided = null)
    {
        $this->customerConsented     = $customerConsented;
        $this->sampleContentProvided = $sampleContentProvided;
    }

    /**
     * Serialize to array for the JSON request body, omitting unset fields.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'customerConsented'        => $this->customerConsented,
            'consumptionStatus'        => $this->consumptionStatus,
            'platform'                 => $this->platform,
            'sampleContentProvided'    => $this->sampleContentProvided,
            'deliveryStatus'           => $this->deliveryStatus,
            'appAccountToken'          => $this->appAccountToken,
            'accountTenure'            => $this->accountTenure,
            'playTime'                 => $this->playTime,
            'lifetimeDollarsRefunded'  => $this->lifetimeDollarsRefunded,
            'lifetimeDollarsPurchased' => $this->lifetimeDollarsPurchased,
            'userStatus'               => $this->userStatus,
            'refundPreference'         => $this->refundPreference,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
