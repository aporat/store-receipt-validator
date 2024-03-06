<?php

namespace ReceiptValidator\iTunes;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use ReceiptValidator\RunTimeException;

class Validator
{
    const ENDPOINT_SANDBOX = 'https://sandbox.itunes.apple.com';
    const ENDPOINT_PRODUCTION = 'https://buy.itunes.apple.com';

    /**
     * endpoint url.
     *
     * @var string
     */
    protected $endpoint;

    /**
     * Whether to exclude old transactions.
     *
     * @var bool
     */
    protected $exclude_old_transactions = false;

    /**
     * itunes receipt data, in base64 format.
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
    protected $shared_secret;

    /**
     * Guzzle http client.
     *
     * @var HttpClient
     */
    protected $client;

    /**
     * request options.
     *
     * @var array
     */
    protected $request_options = [];

    /**
     * Validator constructor.
     *
     * @param string $endpoint
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $endpoint = self::ENDPOINT_PRODUCTION)
    {
        $this->setEndpoint($endpoint);
    }

    /**
     * Get receipt data.
     *
     * @return string|null
     */
    public function getReceiptData(): ?string
    {
        return $this->receipt_data;
    }

    /**
     * Set receipt data, either in base64, or in json.
     *
     * @param string|null $receipt_data
     *
     * @return $this
     */
    public function setReceiptData(?string $receipt_data = null): self
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
     *
     * @return $this
     */
    public function setSharedSecret(?string $shared_secret = null): self
    {
        $this->shared_secret = $shared_secret;

        return $this;
    }

    /**
     * get endpoint.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @param string $endpoint
     *
     * @throws InvalidArgumentException
     *
     * @return $this
     */
    public function setEndpoint(string $endpoint): self
    {
        if ($endpoint !== self::ENDPOINT_PRODUCTION && $endpoint !== self::ENDPOINT_SANDBOX) {
            throw new InvalidArgumentException("Invalid endpoint '{$endpoint}'");
        }
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Get exclude old transactions.
     *
     * @return bool
     */
    public function getExcludeOldTransactions(): bool
    {
        return $this->exclude_old_transactions;
    }

    /**
     * Set exclude old transactions.
     *
     * @param bool $exclude
     *
     * @return Validator
     */
    public function setExcludeOldTransactions(bool $exclude): self
    {
        $this->exclude_old_transactions = $exclude;

        return $this;
    }

    /**
     * Get Client Request Options.
     *
     * @return array
     */
    public function getRequestOptions(): array
    {
        return $this->request_options;
    }

    /**
     * Set Client Options.
     *
     * @param array $request_options
     *
     * @return Validator
     */
    public function setRequestOptions(array $request_options): self
    {
        $this->request_options = $request_options;

        return $this;
    }

    /**
     * Get Guzzle client config.
     *
     * @return array
     */
    protected function getClientConfig(): array
    {
        if (!isset($this->request_options['base_uri'])) {
            $base_uri = ['base_uri' => $this->endpoint];

            return array_merge($this->request_options, $base_uri);
        }

        return $this->request_options;
    }

    /**
     * Returns the Guzzle client.
     *
     * @return HttpClient
     */
    protected function getClient(): HttpClient
    {
        if ($this->client === null) {
            $this->client = new HttpClient($this->getClientConfig());
        }

        return $this->client;
    }

    /**
     * Prepare request data (json).
     *
     * @return string
     */
    protected function prepareRequestData(): string
    {
        $request = [
            'receipt-data'             => $this->getReceiptData(),
            'exclude-old-transactions' => $this->getExcludeOldTransactions(),
        ];

        if ($this->shared_secret !== null) {
            $request['password'] = $this->shared_secret;
        }

        return json_encode($request);
    }

    /**
     * @param null|string $receipt_data
     * @param null|string $shared_secret
     *
     * @throws RunTimeException
     * @throws GuzzleException
     *
     * @return ResponseInterface
     */
    public function validate(?string $receipt_data = null, ?string $shared_secret = null): ResponseInterface
    {
        if ($receipt_data !== null) {
            $this->setReceiptData($receipt_data);
        }

        if ($shared_secret !== null) {
            $this->setSharedSecret($shared_secret);
        }

        $client = $this->getClient();

        return $this->sendRequestUsingClient($client);
    }

    /**
     * @param HttpClient $client
     *
     * @throws RunTimeException
     * @throws GuzzleException
     *
     * @return ProductionResponse|SandboxResponse
     */
    private function sendRequestUsingClient(HttpClient $client)
    {
        $baseUri = (string) $client->getConfig('base_uri');

        $httpResponse = $client->request('POST', '/verifyReceipt', ['body' => $this->prepareRequestData()]);

        if ($httpResponse->getStatusCode() !== 200) {
            throw new RunTimeException('Unable to get response from itunes server');
        }

        $decodedBody = json_decode($httpResponse->getBody(), true);

        if ($baseUri === self::ENDPOINT_PRODUCTION) {
            $response = new ProductionResponse($decodedBody);

            // on a 21007 error, retry the request in the sandbox environment
            // these are receipts from the Apple review team
            if ($response->getResultCode() === ResponseInterface::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION) {
                $config = array_merge($this->getClientConfig(), ['base_uri' => self::ENDPOINT_SANDBOX]);
                $client = new HttpClient($config);

                return $this->sendRequestUsingClient($client);
            }
        } else {
            $response = new SandboxResponse($decodedBody);
        }

        return $response;
    }
}
