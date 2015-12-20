<?php
namespace ReceiptValidator\Amazon;

use ReceiptValidator\RunTimeException as RunTimeException;
use GuzzleHttp\Exception\RequestException;

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
   * @return \GuzzleHttp\Client
   */
  protected function getClient()
  {
    if ($this->_client == null) {
      $this->_client = new \GuzzleHttp\Client(['base_uri' => $this->_endpoint]);
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
    try {
      $httpResponse = $this->getClient()->request('GET', sprintf("developer/%s/user/%s/receiptId/%s", $this->_developerSecret, $this->_userId, $this->_receiptId));

      return new Response($httpResponse->getStatusCode(), json_decode($httpResponse->getBody(), true));
    } catch (RequestException $e) {
      if ($e->hasResponse()) {
        return new Response($e->getResponse()->getStatusCode(), json_decode($e->getResponse()->getBody(), true));
      }
    }

    return new Response(Response::RESULT_INVALID_RECEIPT);
  }
}
