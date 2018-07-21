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
  protected $receipt_data;

  /**
   * The shared secret is a unique code to receive your In-App Purchase receipts.
   * Without a shared secret, you will not be able to test or offer your automatically
   * renewable In-App Purchase subscriptions.
   *
   * @var string|null
   */
  protected $shared_secret = null;

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
    return $this->receipt_data;
  }

  /**
   * set receipt data, either in base64, or in json
   *
   * @param string|null $receipt_data
   * @return $this
   */
  public function setReceiptData($receipt_data): self
  {
    if (strpos($receipt_data, '{') !== false) {
      $this->receipt_data = base64_encode($receipt_data);
    } else {
      $this->receipt_data = $receipt_data;
    }

    return $this;
  }

  /**
   * @return string|null
   */
  public function getSharedSecret(): ?string
  {
    return $this->shared_secret;
  }

  /**
   * @param string|null $shared_secret
   * @return $this
   */
  public function setSharedSecret($shared_secret = null): self
  {
    $this->shared_secret = $shared_secret;

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
  public function setEndpoint(string $endpoint): self
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

    if (!is_null($this->shared_secret)) {
      $request['password'] = $this->shared_secret;
    }

    return json_encode($request);
  }


  /**
   * @param null|string $receipt_data
   * @param null|string $shared_secret
   * @return Response
   * @throws RunTimeException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function validate(?string $receipt_data = null, ?string $shared_secret = null): Response
  {

    if ($receipt_data !== null) {
      $this->setReceiptData($receipt_data);
    }

    if ($shared_secret !== null) {
      $this->setSharedSecret($shared_secret);
    }

    $http_response = $this->getClient()->request('POST', null, ['body' => $this->encodeRequest()]);

    if ($http_response->getStatusCode() != 200) {
      throw new RunTimeException('Unable to get response from itunes server');
    }

    $response = new Response(json_decode($http_response->getBody(), true));

    // on a 21007 error, retry the request in the sandbox environment (if the current environment is production)
    // these are receipts from the Apple review team
    if ($this->endpoint == self::ENDPOINT_PRODUCTION && $response->getResultCode() == Response::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION) {
      $client = new HttpClient(['base_uri' => self::ENDPOINT_SANDBOX]);

      $http_response = $client->request('POST', null, ['body' => $this->encodeRequest()]);

      if ($http_response->getStatusCode() != 200) {
        throw new RunTimeException('Unable to get response from itunes server');
      }

      $response = new Response(json_decode($http_response->getBody(), true));
    }

    return $response;
  }
}
