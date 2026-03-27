<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

/**
 * Request body for the Extend Subscription Renewal Dates for All Active Subscribers endpoint.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/extend-subscription-renewal-dates-for-all-active-subscribers
 */
final class MassExtendRenewalDateRequest
{
    /** The number of days to extend the subscription renewal date (max 90). */
    public ?int $extendByDays = null;

    /**
     * The reason code for the extension.
     *
     * Apple-defined values:
     *   0 - Other
     *   1 - Customer Satisfaction
     *   2 - Other
     *   3 - Service Issue or Outage
     */
    public ?int $extendReasonCode = null;

    /** A string that contains a unique identifier you provide to track this mass extension request. */
    public ?string $requestIdentifier = null;

    /** The product identifier of the auto-renewable subscription to extend. */
    public ?string $productId = null;

    /**
     * A list of storefront country codes to limit which subscribers receive the extension.
     *
     * @var string[]|null
     */
    public ?array $storefrontCountryCodes = null;

    /**
     * Serialize to array for the JSON request body.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $body = [];

        if ($this->extendByDays !== null) {
            $body['extendByDays'] = $this->extendByDays;
        }

        if ($this->extendReasonCode !== null) {
            $body['extendReasonCode'] = $this->extendReasonCode;
        }

        if ($this->requestIdentifier !== null) {
            $body['requestIdentifier'] = $this->requestIdentifier;
        }

        if ($this->productId !== null) {
            $body['productId'] = $this->productId;
        }

        if (!empty($this->storefrontCountryCodes)) {
            $body['storefrontCountryCodes'] = array_values($this->storefrontCountryCodes);
        }

        return $body;
    }
}
