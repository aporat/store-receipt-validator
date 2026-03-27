<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

/**
 * Request body for the Extend a Subscription Renewal Date endpoint.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/extend-a-subscription-renewal-date
 */
final class ExtendRenewalDateRequest
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

    /** A string that contains a unique identifier you provide to track each subscription-renewal-date extension request. */
    public ?string $requestIdentifier = null;

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

        return $body;
    }
}
