<?php

namespace ReceiptValidator\Amazon;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use ReceiptValidator\RunTimeException;

class Validator
{
    public const string ENDPOINT_SANDBOX = 'http://localhost:8080/RVSSandbox/';
    public const string ENDPOINT_PRODUCTION = 'https://appstore-sdk.amazon.com/version/1.0/verifyReceiptId/';

    /**
     * endpoint url.
     *
     * @var string
     */
    protected string $endpoint;

    /**
     * Guzzle http client.
     *
     * @var HttpClient|null
     */
    protected ?HttpClient $client = null;

    /**
     * @var string|null
     */
    protected ?string $userId = null;

    /**
     * @var string|null
     */
    protected ?string $receiptId = null;

    /**
     * @var string|null
     */
    protected ?string $developerSecret = null;

    /**
     * Validator constructor.
     *
     * @param string $endpoint
     *
     * @throws RunTimeException
     */
    public function __construct(string $endpoint = self::ENDPOINT_PRODUCTION)
    {
        if ($endpoint != self::ENDPOINT_PRODUCTION && $endpoint != self::ENDPOINT_SANDBOX) {
            throw new RunTimeException("Invalid endpoint '$endpoint'");
        }

        $this->endpoint = $endpoint;
    }

    /**
     * @param string|null $userId
     *
     * @return self
     */
    public function setUserId(?string $userId = null): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @param string|null $receiptId
     *
     * @return self
     */
    public function setReceiptId(?string $receiptId): self
    {
        $this->receiptId = $receiptId;

        return $this;
    }

    /**
     * get developer secret.
     *
     * @return string|null
     */
    public function getDeveloperSecret(): ?string
    {
        return $this->developerSecret;
    }

    /**
     * @param string|null $developerSecret
     *
     * @return self
     */
    public function setDeveloperSecret(?string $developerSecret): self
    {
        $this->developerSecret = $developerSecret;

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
     * set endpoint.
     *
     * @param string $endpoint
     *
     * @return $this
     */
    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * validate the receipt data.
     *
     * @return Response
     * @throws GuzzleException
     *
     * @throws RunTimeException
     */
    public function validate(): Response
    {
        try {
            $httpResponse = $this->getClient()->request(
                'GET',
                sprintf('developer/%s/user/%s/receiptId/%s', $this->developerSecret, $this->userId, $this->receiptId)
            );

            return new Response($httpResponse->getStatusCode(), json_decode($httpResponse->getBody(), true));
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return new Response(
                    $e->getResponse()->getStatusCode(),
                    json_decode($e->getResponse()->getBody(), true)
                );
            }
        }

        return new Response(Response::RESULT_INVALID_RECEIPT);
    }

    /**
     * returns the Guzzle client.
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
}
