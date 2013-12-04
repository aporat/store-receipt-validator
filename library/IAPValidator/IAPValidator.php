<?php
namespace IAPValidator;

use Guzzle\Http\Client;

class IAPValidator
{

    const ENDPOINT_SANDBOX = 'https://sandbox.itunes.apple.com/verifyReceipt';

    const ENDPOINT_PRODUCTION = 'https://buy.itunes.apple.com/verifyReceipt';

    /**
     * endpoint url
     * 
     * @var string
     */
    protected $_endpoint;

    /**
     * itunes receipt data, in base64 format
     * 
     * @var string
     */
    protected $_receiptData;
    
    
    /**
     * Guzzle http client
     * 
     * @var \Guzzle\Http\Client
     */
    protected $_client = null;

    public function __construct($endpoint = ENDPOINT_PRODUCTION)
    {
        if ($endpoint != self::ENDPOINT_PRODUCTION && $endpoint != self::ENDPOINT_SANDBOX) {
            throw new RunTimeException("Invalid endpoint '{$endpoint}'");
        }
        
        $this->_endpoint = $endpoint;
    }

    /**
     * get receipt data
     *
     * @return string
     */
    public function getReceiptData()
    {
        return $this->_receiptData;
    }

    /**
     * set receipt data, either in base64, or in json
     *
     * @param string $receiptData            
     * @return \IAPValidator\IAPValidator
     */
    function setReceiptData($receiptData)
    {
        if (strpos($receiptData, '{') !== false) {
            $this->_receiptData = base64_encode($receiptData);
        } else {
            $this->_receiptData = $receiptData;
        }
        
        return $this;
    }

    /**
     * get endpoint
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->_endpoint;
    }

    /**
     * set endpoint
     *
     * @param unknown $endpoint            
     * @return \IAPValidator\IAPValidator
     */
    function setEndpoint($endpoint)
    {
        $this->_endpoint = $endpoint;
        
        return $this;
    }

    /**
     * returns the Guzzle client
     *
     * @return \Guzzle\Http\Client
     */
    protected function getClient()
    {
        if ($this->_client == null) {
            $this->_client = new Client($this->_endpoint);
        }
        
        return $this->_client;
    }

    /**
     * encode the request in json
     *
     * @return string
     */
    private function encodeRequest()
    {
        return json_encode(array(
            'receipt-data' => $this->getReceiptData()
        ));
    }
    
    /**
     * validate the receipt data
     * 
     * @param string $receiptData
     */
    public function validate($receiptData = null)
    {
    	
        if ($receiptData!=null) {
            $this->setReceiptData($receiptData);
        }
        
        $response = $this->getClient()->post(null, null, $this->encodeRequest(), array('verify'=> false))->send();
        
        if ($response->getStatusCode()!=200) {
            throw new RunTimeException('Unable to get response from itunes server');
        }
        
        return $response->json();
    }
}
