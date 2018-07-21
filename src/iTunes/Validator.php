<?php

namespace ReceiptValidator\iTunes;

use ReceiptValidator\RunTimeException;
use GuzzleHttp\Client as HttpClient;

class Validator
{

  const ENDPOINT_SANDBOX = 'https://sandbox.itunes.apple.com/verifyReceipt';
  const ENDPOINT_PRODUCTION = 'https://buy.itunes.apple.com/verifyReceipt';

  /**
   * endpoint url
   *
   * @var string
   */
  protected $endpoint;

  /**
   * Whether to exclude old transactions
   *
   * @var bool
   */
  protected $exclude_old_transactions = false;

  /**
   * itunes receipt data, in base64 format
   *
   * @var string|null
   */
  protected $receiptData;

  /**
   * The shared secret is a unique code to receive your In-App Purchase receipts.
   * Without a shared secret, you will not be able to test or offer your automatically
   * renewable In-App Purchase subscriptions.
   *
   * @var string|null
   */
  protected $sharedSecret = null;

  /**
   * Guzzle http client
   *
   * @var \GuzzleHttp\Client
   */
  protected $client = null;

  /**
   * Validator constructor.
   * @param string $endpoint
   * @throws \InvalidArgumentException
   */
  public function __construct(string $endpoint = self::ENDPOINT_PRODUCTION)
  {
    if ($endpoint != self::ENDPOINT_PRODUCTION && $endpoint != self::ENDPOINT_SANDBOX) {
      throw new \InvalidArgumentException("Invalid endpoint '{$endpoint}'");
    }

    $this->endpoint = $endpoint;
  }

  /**
   * get receipt data
   *
   * @return string|null
   */
  public function getReceiptData(): ?string
  {
    return $this->receiptData;
  }

  /**
   * set receipt data, either in base64, or in json
   *
   * @param string|null $receiptData
   * @return $this
   */
  function setReceiptData($receiptData): self
  {
    if (strpos($receiptData, '{') !== false) {
      $this->receiptData = base64_encode($receiptData);
    } else {
      $this->receiptData = $receiptData;
    }

    return $this;
  }

  /**
   * @return string|null
   */
  public function getSharedSecret(): ?string
  {
    return $this->sharedSecret;
  }

  /**
   * @param string|null $sharedSecret
   * @return $this
   */
  public function setSharedSecret($sharedSecret = null): self
  {
    $this->sharedSecret = $sharedSecret;

    return $this;
  }

  /**
   * get endpoint
   *
   * @return string
   */
  public function getEndpoint(): string
  {
    return $this->endpoint;
  }

  /**
   * set endpoint
   *
   * @param string $endpoint
   * @return $this
   */
  function setEndpoint(string $endpoint): self
  {
    $this->endpoint = $endpoint;

    return $this;
  }

  /**
   * get exclude old transactions
   *
   * @return bool
   */
  public function getExcludeOldTransactions(): bool
  {
    return $this->exclude_old_transactions;
  }

  /**
   * set exclude old transactions
   *
   * @param bool $exclude
   * @return Validator
   */
  public function setExcludeOldTransactions(bool $exclude): self
  {
    $this->exclude_old_transactions = $exclude;

    return $this;
  }

  /**
   * returns the Guzzle client
   *
   * @return HttpClient
   */
  protected function getClient(): HttpClient
  {
    if ($this->client == null) {
      $this->client = new HttpClient(['base_uri' => $this->endpoint]);
    }

    return $this->client;
  }

  /**
   * encode the request in json
   *
   * @return string
   */
  private function encodeRequest(): string
  {
    $request = [
      'receipt-data' => $this->getReceiptData(),
      'exclude-old-transactions' => $this->getExcludeOldTransactions()
    ];

    if (!is_null($this->sharedSecret)) {
      $request['password'] = $this->sharedSecret;
    }

    return json_encode($request);
  }


  /**
   * @param null|string $receiptData
   * @param null|string $sharedSecret
   * @return Response
   * @throws RunTimeException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function validate(?string $receiptData = null, ?string $sharedSecret = null): Response
  {

    if ($receiptData != null) {
      $this->setReceiptData($receiptData);
    }

    if ($sharedSecret != null) {
      $this->setSharedSecret($sharedSecret);
    }

    $httpResponse = $this->getClient()->request('POST', null, ['body' => $this->encodeRequest()]);

    if ($httpResponse->getStatusCode() != 200) {
      throw new RunTimeException('Unable to get response from itunes server');
    }

    $response = new Response(json_decode($httpResponse->getBody(), true));

    // on a 21007 error, retry the request in the sandbox environment (if the current environment is production)
    // these are receipts from the Apple review team
    if ($this->endpoint == self::ENDPOINT_PRODUCTION && $response->getResultCode() == Response::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION) {
      $client = new HttpClient(['base_uri' => self::ENDPOINT_SANDBOX]);

      $httpResponse = $client->request('POST', null, ['body' => $this->encodeRequest()]);

      if ($httpResponse->getStatusCode() != 200) {
        throw new RunTimeException('Unable to get response from itunes server');
      }

      $response = new Response(json_decode($httpResponse->getBody(), true));
    }

    return $response;
  }
}
