<?php

namespace ReceiptValidator\GooglePlay;

use Google\Service\AndroidPublisher;
use Google\Service\Exception;

/**
 * Class Validator.
 */
class Validator
{
    /**
     * @var AndroidPublisher|null
     */
    protected ?AndroidPublisher $androidPublisherService = null;
    /**
     * @var string|null
     */
    protected ?string $package_name = null;
    /**
     * @var string|null
     */
    protected ?string $purchase_token = null;
    /**
     * @var string|null
     */
    protected ?string $product_id = null;
    /**
     * @var bool
     */
    private bool $validationModePurchase = true;

    /**
     * @var bool
     */
    private bool $validationSubscriptionV2 = false;

    /**
     * Validator constructor.
     *
     * @param AndroidPublisher $googleServiceAndroidPublisher
     * @param bool $validationModePurchase
     */
    public function __construct(
        AndroidPublisher $googleServiceAndroidPublisher,
        bool $validationModePurchase = true
    ) {
        $this->androidPublisherService = $googleServiceAndroidPublisher;
        $this->validationModePurchase = $validationModePurchase;
    }

    /**
     * @param string $package_name
     *
     * @return $this
     */
    public function setPackageName(string $package_name): self
    {
        $this->package_name = $package_name;

        return $this;
    }

    /**
     * @param string $purchase_token
     *
     * @return $this
     */
    public function setPurchaseToken(string $purchase_token): self
    {
        $this->purchase_token = $purchase_token;

        return $this;
    }

    /**
     * @param string $product_id
     *
     * @return $this
     */
    public function setProductId(string $product_id): self
    {
        $this->product_id = $product_id;

        return $this;
    }

    /**
     * @param bool $validationModePurchase
     *
     * @return Validator
     */
    public function setValidationModePurchase(bool $validationModePurchase): self
    {
        $this->validationModePurchase = $validationModePurchase;

        return $this;
    }

    /**
     * @param bool $validationSubscriptionV2
     *
     * @return Validator
     */
    public function setValidationSubscriptionV2(bool $validationSubscriptionV2): self
    {
        $this->validationSubscriptionV2 = $validationSubscriptionV2;

        return $this;
    }

    /**
     * @return PurchaseResponse|SubscriptionResponse
     * @throws Exception
     *
     */
    public function validate()
    {
        if ($this->validationModePurchase) {
            $result = $this->validatePurchase();
        } elseif ($this->validationSubscriptionV2) {
            $result = $this->validateSubscriptionV2();
        } else {
            $result = $this->validateSubscription();
        }

        return $result;
    }

    /**
     * @return PurchaseResponse
     * @throws Exception
     *
     */
    public function validatePurchase(): PurchaseResponse
    {
        return new PurchaseResponse(
            $this->androidPublisherService->purchases_products->get(
                $this->package_name,
                $this->product_id,
                $this->purchase_token
            )
        );
    }

    public function validateSubscriptionV2(): SubscriptionV2Response
    {
        return new SubscriptionV2Response(
            $this->androidPublisherService->purchases_subscriptionsv2->get(
                $this->package_name,
                $this->purchase_token
            )
        );
    }

    /**
     * @return SubscriptionResponse
     * @throws Exception
     *
     */
    public function validateSubscription(): SubscriptionResponse
    {
        return new SubscriptionResponse(
            $this->androidPublisherService->purchases_subscriptions->get(
                $this->package_name,
                $this->product_id,
                $this->purchase_token
            )
        );
    }

    /**
     * @return AndroidPublisher|null
     */
    public function getPublisherService(): ?AndroidPublisher
    {
        return $this->androidPublisherService;
    }
}
