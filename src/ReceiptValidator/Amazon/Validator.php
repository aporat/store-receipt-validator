<?php
namespace ReceiptValidator\Amazon;

use Aws\CloudFront\Exception\Exception;
use Guzzle\Http\Client as GuzzleClient;
use ReceiptValidator\RunTimeException as RunTimeException;

class Validator
{

    const ENDPOINT_SANDBOX = 'http://localhost:8080/RVSSandbox/';

    const ENDPOINT_PRODUCTION = 'https://appstore-sdk.amazon.com/version/1.0/verifyReceiptId/';

    /**
     * endpoint url
     *
     * @var string
     */
    protected $_endpoint;

    /**
     * Guzzle http client
     *
     * @var \Guzzle\Http\Client
     */
    protected $_client = null;


    /**
     * @var string
     */
    protected $_userId = null;

    /**
     * @var string
     */
    protected $_receiptId = null;

    /**
     * @var string
     */
    protected $_developerSecret = null;

    /**
     * @var string
     */
    protected $_product_id = null;

    public function __construct($endpoint = self::ENDPOINT_PRODUCTION)
    {
        if ($endpoint != self::ENDPOINT_PRODUCTION && $endpoint != self::ENDPOINT_SANDBOX) {
            throw new RunTimeException("Invalid endpoint '{$endpoint}'");
        }

        $this->_endpoint = $endpoint;
    }


    /**
     *
     * @param string $userId
     * @return \ReceiptValidator\Amazon\Validator
     */
    public function setUserId($userId)
    {
        $this->_userId = $userId;

        return $this;
    }

    /**
     *
     * @param string $receiptId
     * @return \ReceiptValidator\Amazon\Validator
     */
    public function setReceiptId($receiptId)
    {
        $this->_receiptId = $receiptId;

        return $this;
    }

    /**
     *
     * @param int $developerSecret
     * @return \ReceiptValidator\Amazon\Validator
     */
    public function setDeveloperSecret($developerSecret)
    {
        $this->_developerSecret = $developerSecret;

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
            $this->_client = new GuzzleClient($this->_endpoint);
        }

        return $this->_client;
    }

    /**
     * validate the receipt data
     *
     * @return Response
     */
    public function validate()
    {

        $httpResponse = $this->getClient()->get(sprintf("developer/%s/user/%s/receiptId/%s", $this->_developerSecret, $this->_userId, $this->_receiptId), null, ['exceptions' => false])->send();

        return new Response($httpResponse->getStatusCode(), $httpResponse->json());
    }
}
