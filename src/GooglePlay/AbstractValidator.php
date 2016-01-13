<?php

namespace ReceiptValidator\GooglePlay;

abstract class AbstractValidator
{
    const TYPE_PURCHASE = 1;
    const TYPE_SUBSCRIPTION = 2;

    /**
    * google client
    *
    * @var Google_Client
    */
    protected $_client = null;

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
     * @var int
     */
    protected $_purchase_type = self::TYPE_PURCHASE;

    /**
     * @var string
     */
    protected $_product_id = null;

    public function __construct($options = [])
    {
        $this->initClient($options);
        $this->_androidPublisherService = new \Google_Service_AndroidPublisher($this->_client);
    }

    abstract protected function initClient($options = []);

    /**
     *
     * @param string $package_name
     * @return \ReceiptValidator\GooglePlay\Validator
     */
    public function setPackageName($package_name)
    {
        $this->_package_name = $package_name;

        return $this;
    }

    /**
     *
     * @param string $purchase_token
     * @return \ReceiptValidator\GooglePlay\Validator
     */
    public function setPurchaseToken($purchase_token)
    {
        $this->_purchase_token = $purchase_token;

        return $this;
    }

    /**
     *
     * @param int $purchase_type
     * @return \ReceiptValidator\GooglePlay\Validator
     */
    public function setPurchaseType($purchase_type)
    {
        $this->_purchase_type = $purchase_type;

        return $this;
    }

    /**
     *
     * @param string $product_id
     * @return \ReceiptValidator\GooglePlay\Validator
     */
    public function setProductId($product_id)
    {
        $this->_product_id = $product_id;

        return $this;
    }

    public function validate()
    {
        switch ($this->_purchase_type) {
            case self::TYPE_SUBSCRIPTION:
                $request = $this->_androidPublisherService->purchases_subscriptions;
                break;
            default:
                $request = $this->_androidPublisherService->purchases_products;
        }

        $response = $request->get(
              $this->_package_name, $this->_product_id, $this->_purchase_token
        );

        return $response;
    }
}
