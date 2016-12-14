<?php

namespace ReceiptValidator\GooglePlay;

/**
 * Class Validator
 * @package ReceiptValidator\GooglePlay
 */
class Validator
{
    /**
     * @var \Google_Service_AndroidPublisher
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
     * Validator constructor.
     * @param \Google_Service_AndroidPublisher $googleServiceAndroidPublisher
     */
    public function __construct(\Google_Service_AndroidPublisher $googleServiceAndroidPublisher)
    {
        $this->_androidPublisherService = $googleServiceAndroidPublisher;
    }

    /**
     *
     * @param string $package_name
     * @return $this
     */
    public function setPackageName($package_name)
    {
        $this->_package_name = $package_name;

        return $this;
    }

    /**
     *
     * @param string $purchase_token
     * @return $this
     */
    public function setPurchaseToken($purchase_token)
    {
        $this->_purchase_token = $purchase_token;

        return $this;
    }

    /**
     *
     * @param string $product_id
     * @return $this
     */
    public function setProductId($product_id)
    {
        $this->_product_id = $product_id;

        return $this;
    }

    /**
     * @return SubscriptionResponse
     */
    public function validateSubscription()
    {
        return new SubscriptionResponse($this->_androidPublisherService->purchases_subscriptions->get(
            $this->_package_name, $this->_product_id, $this->_purchase_token
        ));
    }

    /**
     * @return PurchaseResponse
     */
    public function validatePurchase()
    {
        return new PurchaseResponse($this->_androidPublisherService->purchases_products->get(
            $this->_package_name, $this->_product_id, $this->_purchase_token
        ));
    }
}
