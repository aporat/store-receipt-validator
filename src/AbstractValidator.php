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
    protected ?HttpClient $client = null;

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
        RequestOptions::HTTP_ERRORS => false,
    ];

    /**
     * Optionally inject a preconfigured HTTP client and its base URI.
     * Useful for testing and for handler stacks or custom middleware.
     */
    public function setHttpClient(HttpClient $client, ?string $baseUri = null): self
    {
        $this->client = $client;
        $this->baseUri = $baseUri;
        return $this;
    }

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
     * Get last configured base URI if present.
     */
    public function getBaseUri(): ?string
    {
        return $this->baseUri;
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
     * Creates a new client if none exists or if the base URI changed.
     * This is important when switching between production and sandbox endpoints.
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
