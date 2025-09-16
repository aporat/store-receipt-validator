<?php

namespace ReceiptValidator;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\RequestOptions;
use ReceiptValidator\Exceptions\ValidationException;

abstract class AbstractValidator
{
    /**
     * HTTP client instance.
     */
    public ?HttpClient $client = null;

    /**
     * The base URI of the currently configured client.
     */
    protected ?string $baseUri = null;

    /**
     * Environment (sandbox or production).
     */
    protected Environment $environment = Environment::PRODUCTION;

    /**
     * Guzzle client options.
     *
     * @var array<string, mixed>
     */
    protected array $client_options = [
        RequestOptions::TIMEOUT => 30,
        RequestOptions::CONNECT_TIMEOUT => 30,
        RequestOptions::HTTP_ERRORS => false
    ];

    /**
     * Get environment.
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Set the environment.
     */
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
     * Get the Guzzle HTTP client.
     *
     * This method ensures a new client is created if the base URI changes,
     * which is critical for services that switch between production and sandbox endpoints.
     */
    protected function getClient(string $base_uri): HttpClient
    {
        if ($this->client === null || $this->baseUri !== $base_uri) {
            $options = $this->client_options;
            $options['base_uri'] = $base_uri;
            $this->client = new HttpClient($options);
            $this->baseUri = $base_uri;
        }

        return $this->client;
    }
}
