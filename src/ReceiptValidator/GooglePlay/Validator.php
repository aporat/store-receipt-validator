<?php
namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\RunTimeException as RunTimeException;

class Validator
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

    public function __construct(array $options = array())
    {
        $this->_client = new \Google_Client();
        $this->_client->setClientId($options['client_id']);
        $this->_client->setClientSecret($options['client_secret']);

        $cached_access_token_path = sys_get_temp_dir() . '/' . 'googleplay_access_token_' . md5($options['client_id']) . '.txt';

        touch($cached_access_token_path);
        chmod($cached_access_token_path, 0770);

        try {
            $this->_client->setAccessToken(file_get_contents($cached_access_token_path));
        } catch (\Exception $e) {
            // skip exceptions when the access token is not valid
        }

        try {
            if ($this->_client->isAccessTokenExpired()) {
                $this->_client->refreshToken($options['refresh_token']);
                file_put_contents($cached_access_token_path, $this->_client->getAccessToken());
            }
        } catch (\Exception $e) {
            throw new RuntimeException('Failed refreshing access token - ' . $e->getMessage());
        }

        $this->_androidPublisherService = new \Google_Service_AndroidPublisher($this->_client);

    }


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
