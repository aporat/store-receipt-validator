<?php
namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\RunTimeException;

class Validator
{
    /**
     * google client
     * 
     * @var Google_Client
     */
    protected $_client = null;

    protected $_play_client = null;
    
    protected $_package_name = null;
    
    protected $_purchase_token = null;
    
    protected $_product_id = null;
    
    public function __construct(array $options = [])
    {
        $this->_client = new \Google_Client();
        $this->_client->setClientId($options['client_id']);
        $this->_client->setClientSecret($options['client_secret']);
        
        try {
            $this->_client->setAccessToken(file_get_contents('/tmp/google_access_token.txt'));
        } catch (\Exception $e) {
            echo 'Unable to load existing token - ' . $e->getMessage() . PHP_EOL;
        }
        
        if ($this->_client->isAccessTokenExpired()) {
            echo 'Access Token Expired' . PHP_EOL;
            $this->_client->refreshToken($options['refresh_token']);
            
            file_put_contents('/tmp/google_access_token.txt', $this->_client->getAccessToken());
        }
        
        $this->_play_client = new \Google_Service_AndroidPublisher($this->_client);
        
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
        $response = null;
        try {
            $response = $this->_play_client->inapppurchases->get($this->_package_name, $this->_product_id, $this->_purchase_token);
        } catch (\Exception $e) {
            echo 'Unable to load reciept - ' . $e->getMessage() . PHP_EOL;
        
        }
        
       return $response;
    }
}
