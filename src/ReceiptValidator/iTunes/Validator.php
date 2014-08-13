<?php
namespace ReceiptValidator\iTunes;

use Guzzle\Http\Client as GuzzleClient;
use ReceiptValidator\iTunes\Response;
use ReceiptValidator\RunTimeException;

class Validator
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
     * @return \ReceiptValidator\iTunes\Validator;
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
     * @param string $endpoint
     * @return\ReceiptValidator\iTunes\Validator;
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
            $this->_client = new GuzzleClient($this->_endpoint);
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
     *
     * @return Response
     */
    public function validate($receiptData = null)
    {

        if ($receiptData != null) {
            $this->setReceiptData($receiptData);
        }

        $httpResponse = $this->getClient()->post(null, null, $this->encodeRequest(), array('verify' => false))->send();

        if ($httpResponse->getStatusCode() != 200) {
            throw new RunTimeException('Unable to get response from itunes server');
        }

        $response = new Response($httpResponse->json());

        // on a 21007 error retry the request in the sandbox environment (if the current environment is Production)
        // these are receipts from apple review team
        if ($this->_endpoint == self::ENDPOINT_PRODUCTION && $response->getResultCode() == Response::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION) {
            $client = new GuzzleClient(self::ENDPOINT_SANDBOX);

            $httpResponse = $client->post(null, null, $this->encodeRequest(), array('verify' => false))->send();

            if ($httpResponse->getStatusCode() != 200) {
                throw new RunTimeException('Unable to get response from itunes server');
            }

            $response = new Response($httpResponse->json());
        }

        return $response;
    }
}
