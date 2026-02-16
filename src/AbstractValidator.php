<?php

declare(strict_types=1);

namespace ReceiptValidator;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use ReceiptValidator\Exceptions\ValidationException;

abstract class AbstractValidator
{
    /**
     * @const array<RequestOptions::*, string>
     */
    public const array DEFAULT_REQUEST_OPTIONS = [
        RequestOptions::TIMEOUT         => 30,
        RequestOptions::CONNECT_TIMEOUT => 30,
        RequestOptions::HTTP_ERRORS     => false,
    ];

    /** Environment (sandbox or production). */
    public Environment $environment = Environment::PRODUCTION;

    /** HTTP client instance. */
    protected ?HttpClientInterface $client = null;

    /**
     * Guzzle request options.
     *
     * @var array<RequestOptions::*, mixed>
     */
    protected array $requestOptions = [];

    /**
     * Optionally inject a preconfigured HTTP client and its base URI.
     * Useful for testing and for handler stacks or custom middleware.
     *
     * @param array<RequestOptions::*, mixed> $requestOptions
     */
    public function setHttpClient(HttpClientInterface $client, array $requestOptions = []): self
    {
        $this->client  = $client;
        $this->requestOptions = $requestOptions;

        return $this;
    }

    /** Get environment. */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /** Set the environment. */
    public function setEnvironment(Environment $environment): self
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * Validate the receipt.
     *
     * @throws ValidationException
     */
    abstract public function validate(): mixed;

    /**
     * Perform the HTTP request
     *
     * @param array<RequestOptions::*, mixed> $options
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    final protected function makeRequest(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->getClient()->request(
            $method,
            $url,
            array_merge(self::DEFAULT_REQUEST_OPTIONS, $this->requestOptions, $options),
        );
    }

    /**
     * Get the Guzzle HTTP client.
     *
     * Creates a new client if none exists.
     */
    private function getClient(): HttpClientInterface
    {
        if ($this->client === null) {
            $this->client = new HttpClient();
        }

        return $this->client;
    }
}
