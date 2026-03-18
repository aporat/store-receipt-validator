<?php

declare(strict_types=1);

namespace ReceiptValidator\iTunes;

use Psr\Http\Client\ClientExceptionInterface;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use Throwable;

/**
 * Legacy iTunes receipt validator using the verifyReceipt endpoint.
 *
 * @deprecated since version 2.0. Use {@see \ReceiptValidator\AppleAppStore\Validator} instead.
 *             Apple has deprecated the verifyReceipt endpoint in favor of the App Store Server API.
 * @see https://developer.apple.com/documentation/appstoreserverapi
 */
class Validator extends AbstractValidator
{
    /** Sandbox endpoint URL. */
    public const string ENDPOINT_SANDBOX = 'https://sandbox.itunes.apple.com';

    /** Production endpoint URL. */
    public const string ENDPOINT_PRODUCTION = 'https://buy.itunes.apple.com';

    /** @return array{production:string, sandbox:string} */
    protected function endpointMap(): array
    {
        return [
            Environment::PRODUCTION->value => self::ENDPOINT_PRODUCTION,
            Environment::SANDBOX->value    => self::ENDPOINT_SANDBOX,
        ];
    }

    /** iTunes receipt data, in base64 format. */
    protected ?string $receiptData = null;

    /** The shared secret for auto-renewable subscriptions. */
    protected ?string $sharedSecret = null;

    public function __construct(?string $sharedSecret = null, Environment $environment = Environment::PRODUCTION)
    {
        parent::__construct();
        $this->sharedSecret = $sharedSecret;
        $this->environment  = $environment;
    }

    public function getSharedSecret(): ?string
    {
        return $this->sharedSecret;
    }

    public function setSharedSecret(?string $sharedSecret = null): self
    {
        $this->sharedSecret = $sharedSecret;
        return $this;
    }

    /**
     * Validate the receipt.
     *
     * @throws ValidationException
     */
    public function validate(?string $receiptData = null): Response
    {
        if ($receiptData !== null) {
            $this->setReceiptData($receiptData);
        }

        return $this->makeRequest();
    }

    /**
     * Perform the HTTP request and handle cross-environment retry logic if needed.
     *
     * @throws ValidationException
     */
    protected function makeRequest(?Environment $environment = null): Response
    {
        if ($environment !== null) {
            $this->setEnvironment($environment);
        }

        $endpoint = $this->endpointForEnvironment();

        $this->logger->debug('iTunes API request', [
            'environment' => $this->environment->value,
        ]);

        $body   = $this->prepareRequestData();
        $stream = $this->getStreamFactory()->createStream($body);

        $request = $this->getRequestFactory()
            ->createRequest('POST', $endpoint . '/verifyReceipt')
            ->withBody($stream);

        foreach ($this->buildHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        try {
            $httpResponse = $this->getClient()->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('iTunes API connection failed', [
                'environment' => $this->environment->value,
                'error'       => $e->getMessage(),
            ]);
            throw new ValidationException('Unable to connect to iTunes server - ' . $e->getMessage(), 0, $e);
        }

        if ($httpResponse->getStatusCode() !== 200) {
            $this->logger->warning('iTunes API unexpected HTTP status', [
                'environment' => $this->environment->value,
                'status_code' => $httpResponse->getStatusCode(),
            ]);
            throw new ValidationException('Unable to get response from iTunes server');
        }

        $raw = (string) $httpResponse->getBody();

        try {
            $decodedBody = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new ValidationException('iTunes server returned invalid JSON: ' . $e->getMessage());
        }

        if (!is_array($decodedBody)) {
            throw new ValidationException('iTunes server returned an unexpected response structure.');
        }

        $status = (int) ($decodedBody['status'] ?? APIError::VALID->value);

        // Sandbox receipt was sent to production → retry on sandbox (21007)
        if ($this->environment === Environment::PRODUCTION && $status === APIError::SANDBOX_RECEIPT_ON_PRODUCTION->value) {
            $this->logger->info('iTunes receipt environment mismatch, retrying on sandbox', [
                'original_environment' => Environment::PRODUCTION->value,
            ]);
            return $this->makeRequest(Environment::SANDBOX);
        }

        // Production receipt was sent to sandbox → retry on production (21008)
        if ($this->environment === Environment::SANDBOX && $status === APIError::PRODUCTION_RECEIPT_ON_SANDBOX->value) {
            $this->logger->info('iTunes receipt environment mismatch, retrying on production', [
                'original_environment' => Environment::SANDBOX->value,
            ]);
            return $this->makeRequest(Environment::PRODUCTION);
        }

        // Anything not VALID or SUBSCRIPTION_EXPIRED is an error
        if ($status !== APIError::VALID->value && $status !== APIError::SUBSCRIPTION_EXPIRED->value) {
            $errorCase = APIError::tryFrom($status);
            $description = $errorCase ? $errorCase->message() : 'An unknown error occurred.';
            $fullMessage = "iTunes API error [$status]: $description";
            $this->logger->warning('iTunes API error response', [
                'environment' => $this->environment->value,
                'status'      => $status,
                'error'       => $description,
            ]);
            throw new ValidationException($fullMessage, $status);
        }

        $this->logger->info('iTunes API request successful', [
            'environment' => $this->environment->value,
            'status'      => $status,
        ]);

        return new Response($decodedBody, $this->environment);
    }

    /**
     * Prepare request data (JSON).
     *
     * @throws ValidationException
     */
    protected function prepareRequestData(): string
    {
        if ($this->receiptData === null || $this->receiptData === '') {
            throw new ValidationException('Receipt data must be set before validation.');
        }

        $payload = [
            'receipt-data' => $this->receiptData,
        ];

        if ($this->sharedSecret !== null && $this->sharedSecret !== '') {
            $payload['password'] = $this->sharedSecret;
        }

        try {
            return json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new ValidationException('Unable to encode request for iTunes server: ' . $e->getMessage());
        }
    }

    public function getReceiptData(): ?string
    {
        return $this->receiptData;
    }

    /**
     * Set receipt data, either in base64 or as raw JSON (auto-encodes JSON).
     */
    public function setReceiptData(string $receiptData = ''): self
    {
        $trimmed = ltrim($receiptData);
        // If it looks like raw JSON, base64-encode it to conform with Apple's API
        $this->receiptData = ($trimmed !== '' && $trimmed[0] === '{')
            ? base64_encode($receiptData)
            : $receiptData;

        return $this;
    }

    /**
     * Build request headers.
     *
     * @return array<string,string>
     */
    private function buildHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }
}
