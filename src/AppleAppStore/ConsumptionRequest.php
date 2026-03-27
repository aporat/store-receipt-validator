<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

/**
 * Request body for the Send Consumption Information endpoint.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/send-consumption-information
 */
final class ConsumptionRequest
{
    /**
     * A Boolean value that indicates whether the customer consented to provide
     * consumption data to the App Store.
     */
    public bool $customerConsented;

    /**
     * A Boolean value that indicates whether you provided, prior to its purchase,
     * a free sample or trial of the content, or information about its functionality.
     */
    public bool $sampleContentProvided;

    /**
     * A value that indicates whether the app successfully delivered an in-app
     * purchase that works properly.
     *
     * Apple-defined values:
     *   0 - Delivered and working
     *   1 - Delivered but not working
     *   2 - Not delivered due to a quality issue
     *   3 - Not delivered due to a server outage
     *   4 - Not delivered due to an in-game currency change
     *   5 - Not delivered for other reasons
     */
    public ?int $deliveryStatus = null;

    /**
     * A value that indicates the extent to which the customer consumed the in-app
     * purchase (0–100, in increments of 10).
     */
    public ?int $consumptionPercentage = null;

    /**
     * A value that indicates your preference for how the App Store should proceed
     * when the customer requests a refund.
     *
     * Apple-defined values:
     *   0 - Undeclared (you have no preference)
     *   1 - No refund
     *   2 - Grant refund
     */
    public ?int $refundPreference = null;

    public function __construct(bool $customerConsented, bool $sampleContentProvided)
    {
        $this->customerConsented     = $customerConsented;
        $this->sampleContentProvided = $sampleContentProvided;
    }

    /**
     * Serialize to array for the JSON request body.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $body = [
            'customerConsented'     => $this->customerConsented,
            'sampleContentProvided' => $this->sampleContentProvided,
        ];

        if ($this->deliveryStatus !== null) {
            $body['deliveryStatus'] = $this->deliveryStatus;
        }

        if ($this->consumptionPercentage !== null) {
            $body['consumptionPercentage'] = $this->consumptionPercentage;
        }

        if ($this->refundPreference !== null) {
            $body['refundPreference'] = $this->refundPreference;
        }

        return $body;
    }
}
