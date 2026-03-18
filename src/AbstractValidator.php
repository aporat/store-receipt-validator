<?php

declare(strict_types=1);

namespace ReceiptValidator;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\RequestOptions;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReceiptValidator\Exceptions\ValidationException;

abstract class AbstractValidator
{
    /** PSR-3 logger. Defaults to NullLogger so logging is opt-in. */
    protected LoggerInterface $logger;

    /** PSR-18 HTTP client. */
    protected ?ClientInterface $client = null;

    /** PSR-17 request factory. */
    protected ?RequestFactoryInterface $requestFactory = null;

    /** PSR-17 stream factory. */
    protected ?StreamFactoryInterface $streamFactory = null;

    /** Environment (sandbox or production). */
    protected Environment $environment = Environment::PRODUCTION;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * Inject a PSR-3 logger. Returns $this for fluent chaining.
     */
    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Concrete validators must declare their endpoints.
     *
     * @return array{production:string, sandbox:string}
     */
    abstract protected function endpointMap(): array;

    /**
     * Resolve base URL for the current environment using the validator's map.
     */
    protected function endpointForEnvironment(): string
    {
        $map = $this->endpointMap();

        return $this->environment === Environment::PRODUCTION
            ? $map[Environment::PRODUCTION->value]
            : $map[Environment::SANDBOX->value];
    }

    /**
     * Inject a PSR-18 HTTP client. Useful for testing and custom middleware.
     */
    public function setHttpClient(ClientInterface $client): static
    {
        $this->client = $client;
        return $this;
    }

    /** Get environment. */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /** Set the environment. */
    public function setEnvironment(Environment $environment): static
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
     * Get the PSR-18 HTTP client.
     *
     * Creates a default Guzzle client if none was injected.
     */
    protected function getClient(): ClientInterface
    {
        return $this->client ??= new GuzzleClient([
            RequestOptions::TIMEOUT         => 30,
            RequestOptions::CONNECT_TIMEOUT => 30,
            RequestOptions::HTTP_ERRORS     => false,
        ]);
    }

    /**
     * Get the PSR-17 request factory.
     */
    protected function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory ??= new HttpFactory();
    }

    /**
     * Get the PSR-17 stream factory.
     */
    protected function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory ??= new HttpFactory();
    }
}
