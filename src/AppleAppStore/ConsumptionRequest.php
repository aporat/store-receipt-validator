<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

/**
 * Request body for the v2 Send Consumption Information endpoint.
 *
 * For the deprecated v1 endpoint, use {@see ConsumptionRequestV1}.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/consumptionrequest
 */
final class ConsumptionRequest
{
    /**
     * A Boolean value that indicates whether the customer consented to provide
     * consumption data to the App Store.
     *
     * Apple rejects requests where this is not true.
     */
    public bool $customerConsented;

    /**
     * A Boolean value that indicates whether you provided, prior to its purchase,
     * a free sample or trial of the content, or information about its functionality.
     */
    public bool $sampleContentProvided;

    /**
     * A value that indicates whether the app successfully delivered an in-app
     * purchase that works properly. Required by Apple.
     *
     * Prefer a {@see DeliveryStatus} case. A legacy v1 integer (0-5) is still
     * accepted and mapped via {@see DeliveryStatus::fromLegacyValue()}.
     */
    public DeliveryStatus|int|null $deliveryStatus = null;

    /**
     * The percentage of the in-app purchase the customer consumed, expressed as an
     * integer in milliunits with three decimal places of precision:
     * 40% is 40000, 67.932% is 67932 and 100% is 100000.
     *
     * Use {@see setConsumptionPercent()} to pass a plain percentage instead.
     *
     * Must be 0 when deliveryStatus is not DELIVERED, and must be omitted for
     * auto-renewable subscriptions.
     */
    public ?int $consumptionPercentage = null;

    /**
     * Your preferred outcome for the refund request.
     *
     * Prefer a {@see RefundPreference} case. A legacy v1 integer (0-3) is still
     * accepted and mapped via {@see RefundPreference::fromLegacyValue()}.
     */
    public RefundPreference|int|null $refundPreference = null;

    public function __construct(
        bool $customerConsented,
        bool $sampleContentProvided,
        DeliveryStatus|int|null $deliveryStatus = null,
    ) {
        $this->customerConsented     = $customerConsented;
        $this->sampleContentProvided = $sampleContentProvided;
        $this->deliveryStatus        = $deliveryStatus;
    }

    /**
     * Set the consumption percentage from a plain percentage (0-100).
     *
     * The value is converted to the milliunits Apple expects, so 67.932 becomes 67932.
     */
    public function setConsumptionPercent(float $percent): self
    {
        if ($percent < 0 || $percent > 100) {
            throw new \InvalidArgumentException('Consumption percent must be between 0 and 100.');
        }

        $this->consumptionPercentage = (int) round($percent * 1000);

        return $this;
    }

    /**
     * The delivery status as the v2 enum, resolving any legacy integer value.
     */
    public function getDeliveryStatus(): ?DeliveryStatus
    {
        return is_int($this->deliveryStatus)
            ? DeliveryStatus::fromLegacyValue($this->deliveryStatus)
            : $this->deliveryStatus;
    }

    /**
     * The refund preference as the v2 enum, resolving any legacy integer value.
     */
    public function getRefundPreference(): ?RefundPreference
    {
        return is_int($this->refundPreference)
            ? RefundPreference::fromLegacyValue($this->refundPreference)
            : $this->refundPreference;
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

        $deliveryStatus = $this->getDeliveryStatus();
        if ($deliveryStatus !== null) {
            $body['deliveryStatus'] = $deliveryStatus->value;
        }

        if ($this->consumptionPercentage !== null) {
            $body['consumptionPercentage'] = $this->consumptionPercentage;
        }

        $refundPreference = $this->getRefundPreference();
        if ($refundPreference !== null) {
            $body['refundPreference'] = $refundPreference->value;
        }

        return $body;
    }
}
