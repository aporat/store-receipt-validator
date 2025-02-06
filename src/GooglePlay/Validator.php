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
    protected ?AndroidPublisher $_androidPublisherService = null;
    /**
     * @var string|null
     */
    protected ?string $_package_name = null;
    /**
     * @var string|null
     */
    protected ?string $_purchase_token = null;
    /**
     * @var string|null
     */
    protected ?string $_product_id = null;
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
        $this->_androidPublisherService = $googleServiceAndroidPublisher;
        $this->validationModePurchase = $validationModePurchase;
    }

    /**
     * @param string $package_name
     *
     * @return $this
     */
    public function setPackageName(string $package_name): static
    {
        $this->_package_name = $package_name;

        return $this;
    }

    /**
     * @param string $purchase_token
     *
     * @return $this
     */
    public function setPurchaseToken(string $purchase_token): static
    {
        $this->_purchase_token = $purchase_token;

        return $this;
    }

    /**
     * @param string $product_id
     *
     * @return $this
     */
    public function setProductId(string $product_id): static
    {
        $this->_product_id = $product_id;

        return $this;
    }

    /**
     * @param bool $validationModePurchase
     *
     * @return Validator
     */
    public function setValidationModePurchase(bool $validationModePurchase): static
    {
        $this->validationModePurchase = $validationModePurchase;

        return $this;
    }

    /**
     * @param bool $validationSubscriptionV2
     *
     * @return Validator
     */
    public function setValidationSubscriptionV2(bool $validationSubscriptionV2): static
    {
        $this->validationSubscriptionV2 = $validationSubscriptionV2;

        return $this;
    }

    /**
     * @return PurchaseResponse|SubscriptionResponse
     * @throws Exception
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
     */
    public function validatePurchase(): PurchaseResponse
    {
        return new PurchaseResponse(
            $this->_androidPublisherService->purchases_products->get(
                $this->_package_name,
                $this->_product_id,
                $this->_purchase_token
            )
        );
    }

    /**
     * @return SubscriptionResponse
     * @throws Exception
     */
    public function validateSubscription(): SubscriptionResponse
    {
        return new SubscriptionResponse(
            $this->_androidPublisherService->purchases_subscriptions->get(
                $this->_package_name,
                $this->_product_id,
                $this->_purchase_token
            )
        );
    }

    public function validateSubscriptionV2(): SubscriptionV2Response
    {
        return new SubscriptionV2Response(
            $this->_androidPublisherService->purchases_subscriptionsv2->get(
                $this->_package_name,
                $this->_purchase_token
            )
        );
    }

    /**
     * @return AndroidPublisher|null
     */
    public function getPublisherService(): ?AndroidPublisher
    {
        return $this->_androidPublisherService;
    }
}
