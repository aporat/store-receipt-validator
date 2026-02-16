<?php

declare(strict_types=1);

namespace ReceiptValidator\iTunes;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
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

    /** iTunes receipt data, in base64 format. */
    private ?string $receiptData = null;

    public function __construct(
        private ?string $sharedSecret = null,
        Environment $environment = Environment::PRODUCTION,
    ) {
        $this->environment  = $environment;
    }

    public function setSharedSecret(?string $sharedSecret): self
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

        if (empty($this->receiptData)) {
            throw new ValidationException('Receipt data must be set before validation.');
        }

        return $this->getResponse($this->environment);
    }

    /**
     * Perform the HTTP request and handle cross-environment retry logic if needed.
     *
     * @throws ValidationException
     */
    protected function getResponse(Environment $environment): Response
    {
        $baseUrl = match ($environment) {
            Environment::PRODUCTION => self::ENDPOINT_PRODUCTION,
            Environment::SANDBOX => self::ENDPOINT_SANDBOX,
        };

        $payload = [
            'receipt-data' => $this->receiptData,
        ];
        if (!empty($this->sharedSecret)) {
            $payload['password'] = $this->sharedSecret;
        }

        try {
            $httpResponse = $this->makeRequest(
                'POST',
                $baseUrl . '/verifyReceipt',
                [
                    RequestOptions::JSON    => $payload,
                    RequestOptions::HEADERS => [
                        'Accept' => 'application/json',
                    ],
                ],
            );
        } catch (GuzzleException $e) {
            throw new ValidationException('Unable to connect to iTunes server - ' . $e->getMessage(), previous: $e);
        }

        if ($httpResponse->getStatusCode() !== 200) {
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
        if ($environment === Environment::PRODUCTION && $status === APIError::SANDBOX_RECEIPT_ON_PRODUCTION->value) {
            return $this->getResponse(Environment::SANDBOX);
        }

        // Production receipt was sent to sandbox → retry on production (21008)
        if ($environment === Environment::SANDBOX && $status === APIError::PRODUCTION_RECEIPT_ON_SANDBOX->value) {
            return $this->getResponse(Environment::PRODUCTION);
        }

        // Anything not VALID or SUBSCRIPTION_EXPIRED is an error
        if ($status !== APIError::VALID->value && $status !== APIError::SUBSCRIPTION_EXPIRED->value) {
            $errorCase = APIError::tryFrom($status);
            $description = $errorCase ? $errorCase->message() : 'An unknown error occurred.';
            $fullMessage = "iTunes API error [$status]: $description";
            throw new ValidationException($fullMessage, $status);
        }

        return new Response($decodedBody, $this->environment);
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
        // If it looks like raw JSON, base64-encode it to conform with Apple’s API
        $this->receiptData = ($trimmed !== '' && $trimmed[0] === '{')
            ? base64_encode($receiptData)
            : $receiptData;

        return $this;
    }
}
