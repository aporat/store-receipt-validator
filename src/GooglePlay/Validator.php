<?php

namespace ReceiptValidator\GooglePlay;

use Google\Service\AndroidPublisher;

/**
 * Class Validator.
 */
class Validator
{
    /**
     * @var AndroidPublisher
     */
    protected $_androidPublisherService = null;
    /**
     * @var string
     */
    protected $_package_name = null;
    /**
     * @var string
     */
    protected $_purchase_token = null;
    /**
     * @var string
     */
    protected $_product_id = null;
    /**
     * @var bool
     */
    private $validationModePurchase = true;

    /**
     * @var bool
     */
    private $validationSubscriptionV2 = false;

    /**
     * Validator constructor.
     *
     * @param AndroidPublisher $googleServiceAndroidPublisher
     * @param bool             $validationModePurchase
     */
    public function __construct(
        AndroidPublisher $googleServiceAndroidPublisher,
        $validationModePurchase = true
    ) {
        $this->_androidPublisherService = $googleServiceAndroidPublisher;
        $this->validationModePurchase = $validationModePurchase;
    }

    /**
     * @param string $package_name
     *
     * @return $this
     */
    public function setPackageName($package_name)
    {
        $this->_package_name = $package_name;

        return $this;
    }

    /**
     * @param string $purchase_token
     *
     * @return $this
     */
    public function setPurchaseToken($purchase_token)
    {
        $this->_purchase_token = $purchase_token;

        return $this;
    }

    /**
     * @param string $product_id
     *
     * @return $this
     */
    public function setProductId($product_id)
    {
        $this->_product_id = $product_id;

        return $this;
    }

    /**
     * @param bool $validationModePurchase
     *
     * @return Validator
     */
    public function setValidationModePurchase($validationModePurchase)
    {
        $this->validationModePurchase = $validationModePurchase;

        return $this;
    }

    /**
     * @param bool
     *
     * @return Validator
     */
    public function setValidationSubscriptionV2(bool $validationSubscriptionV2)
    {
        $this->validationSubscriptionV2 = $validationSubscriptionV2;

        return $this;
    }

    /**
     * @return PurchaseResponse|SubscriptionResponse
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
     */
    public function validatePurchase()
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
     */
    public function validateSubscription()
    {
        return new SubscriptionResponse(
            $this->_androidPublisherService->purchases_subscriptions->get(
                $this->_package_name,
                $this->_product_id,
                $this->_purchase_token
            )
        );
    }

    public function validateSubscriptionV2()
    {
        return new SubscriptionV2Response(
            $this->_androidPublisherService->purchases_subscriptionsv2->get(
                $this->_package_name,
                $this->_purchase_token
            )
        );
    }

    /**
     * @return AndroidPublisher
     */
    public function getPublisherService()
    {
        return $this->_androidPublisherService;
    }
}
