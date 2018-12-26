<?php

namespace ReceiptValidator\Amazon;

use ReceiptValidator\RunTimeException as RunTimeException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client as HttpClient;

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
     * @var \GuzzleHttp\Client
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

    /**
     * Validator constructor.
     *
     * @param string $endpoint
     * @throws RunTimeException
     */
    public function __construct(string $endpoint = self::ENDPOINT_PRODUCTION)
    {
        if ($endpoint != self::ENDPOINT_PRODUCTION && $endpoint != self::ENDPOINT_SANDBOX) {
            throw new RunTimeException("Invalid endpoint '{$endpoint}'");
        }

        $this->_endpoint = $endpoint;
    }


    /**
     *
     * @param string $userId
     * @return self
     */
    public function setUserId($userId): self
    {
        $this->_userId = $userId;

        return $this;
    }

    /**
     *
     * @param string $receiptId
     * @return self
     */
    public function setReceiptId($receiptId): self
    {
        $this->_receiptId = $receiptId;

        return $this;
    }


    /**
     * get developer secret
     *
     * @return string
     */
    public function getDeveloperSecret()
    {
        return $this->_developerSecret;
    }

    /**
     *
     * @param string $developerSecret
     * @return self
     */
    public function setDeveloperSecret($developerSecret): self
    {
        $this->_developerSecret = $developerSecret;

        return $this;
    }

    /**
     * get endpoint
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->_endpoint;
    }

    /**
     * set endpoint
     *
     * @param string $endpoint
     * @return $this
     */
    public function setEndpoint(string $endpoint): self
    {
        $this->_endpoint = $endpoint;

        return $this;
    }


    /**
     * returns the Guzzle client
     *
     * @return HttpClient
     */
    protected function getClient(): HttpClient
    {
        if ($this->_client == null) {
            $this->_client = new HttpClient(['base_uri' => $this->_endpoint]);
        }

        return $this->_client;
    }


    /**
     * validate the receipt data
     * @return Response
     * @throws RunTimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function validate()
    {
        try {
            $httpResponse = $this->getClient()->request(
                'GET',
                sprintf("developer/%s/user/%s/receiptId/%s", $this->_developerSecret, $this->_userId, $this->_receiptId)
            );

            return new Response($httpResponse->getStatusCode(), json_decode($httpResponse->getBody(), true));
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return new Response(
                    $e->getResponse()->getStatusCode(), json_decode($e->getResponse()->getBody(), true)
                );
            }
        }

        return new Response(Response::RESULT_INVALID_RECEIPT);
    }
}
