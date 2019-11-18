<?php

namespace ReceiptValidator\GooglePlay;

/**
 * Class Acknowledger
 * @package ReceiptValidator\GooglePlay
 */
class Acknowledger
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
     * Acknowledger constructor.
     * @param \Google_Service_AndroidPublisher $googleServiceAndroidPublisher
     */
    public function __construct(\Google_Service_AndroidPublisher $googleServiceAndroidPublisher) {
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
     * @param string $developerPayload
     * @return bool
     */
    public function acknowledge(string $developerPayload = '')
    {
        try {
            $this->_androidPublisherService->purchases_subscriptions->acknowledge(
                $this->_package_name,
                $this->_product_id,
                $this->_purchase_token,
                new \Google_Service_AndroidPublisher_SubscriptionPurchasesAcknowledgeRequest(
                    ['developerPayload' => $developerPayload]
                )
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return \Google_Service_AndroidPublisher
     */
    public function getPublisherService()
    {
        return $this->_androidPublisherService;
    }
}
