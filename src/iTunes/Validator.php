<?php

namespace ReceiptValidator\iTunes;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

class Validator extends AbstractValidator
{
    /**
     * Sandbox endpoint URL.
     *
     * @var string
     */
    public const string ENDPOINT_SANDBOX = 'https://sandbox.itunes.apple.com';

    /**
     * Production endpoint URL.
     *
     * @var string
     */
    public const string ENDPOINT_PRODUCTION = 'https://buy.itunes.apple.com';

    /**
     * iTunes receipt data, in base64 format.
     *
     * @var string|null
     */
    protected ?string $receiptData = null;

    /**
     * The shared secret for auto-renewable subscriptions.
     *
     * @var string|null
     */
    protected ?string $sharedSecret = null;

    /**
     * Constructor.
     *
     * @param string|null $sharedSecret
     * @param Environment $environment
     */
    public function __construct(?string $sharedSecret = null, Environment $environment = Environment::PRODUCTION)
    {
        $this->sharedSecret = $sharedSecret;
        $this->environment = $environment;
    }

    /**
     * Get the shared secret.
     *
     * @return string|null
     */
    public function getSharedSecret(): ?string
    {
        return $this->sharedSecret;
    }

    /**
     * Set the shared secret.
     *
     * @param string|null $sharedSecret
     * @return $this
     */
    public function setSharedSecret(?string $sharedSecret = null): self
    {
        $this->sharedSecret = $sharedSecret;
        return $this;
    }

    /**
     * Validate the receipt.
     *
     * @param string|null $receiptData
     * @return Response
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
     * @param Environment|null $environment
     * @return Response
     * @throws ValidationException
     */
    protected function makeRequest(?Environment $environment = null): Response
    {
        if ($environment !== null) {
            $this->setEnvironment($environment);
        }

        $endpoint = $this->environment === Environment::PRODUCTION
            ? self::ENDPOINT_PRODUCTION
            : self::ENDPOINT_SANDBOX;

        try {
            $httpResponse = $this->getClient($endpoint)->request(
                'POST',
                '/verifyReceipt',
                [
                    RequestOptions::BODY => $this->prepareRequestData(),
                    RequestOptions::HEADERS => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            throw new ValidationException('Unable to connect to iTunes server - ' . $e->getMessage(), 0, $e);
        }

        if ($httpResponse->getStatusCode() !== 200) {
            throw new ValidationException('Unable to get response from iTunes server');
        }

        $raw = (string) $httpResponse->getBody();
        $decodedBody = json_decode($raw, true);
        if (!is_array($decodedBody)) {
            $jsonErr = function_exists('json_last_error_msg') ? json_last_error_msg() : 'Unknown JSON error';
            throw new ValidationException('iTunes server returned invalid JSON: ' . $jsonErr);
        }

        $status = $decodedBody['status'] ?? APIError::VALID->value;

        // Production receipt accidentally sent to sandbox → retry on production (21008)
        if (
            $this->environment === Environment::SANDBOX &&
            $status === APIError::PRODUCTION_RECEIPT_ON_SANDBOX->value
        ) {
            return $this->makeRequest(Environment::PRODUCTION);
        }

        // Sandbox receipt accidentally sent to production → retry on sandbox (21007)
        if (
            $this->environment === Environment::PRODUCTION &&
            $status === APIError::SANDBOX_RECEIPT_ON_PRODUCTION->value
        ) {
            return $this->makeRequest(Environment::SANDBOX);
        }

        // Non-success statuses (other than expired subscription, which is considered a valid outcome)
        if ($status !== APIError::VALID->value && $status !== APIError::SUBSCRIPTION_EXPIRED->value) {
            $errorCase = APIError::tryFrom((int) $status);
            $description = $errorCase ? $errorCase->message() : 'An unknown error occurred.';
            $fullMessage = "iTunes API error [{$status}]: {$description}";
            throw new ValidationException($fullMessage, (int) $status);
        }

        return new Response($decodedBody, $this->environment);
    }

    /**
     * Prepare request data (JSON).
     *
     * @return string
     * @throws ValidationException
     */
    protected function prepareRequestData(): string
    {
        if (empty($this->receiptData)) {
            throw new ValidationException('Receipt data must be set before validation.');
        }

        $request = [
            'receipt-data' => $this->receiptData,
        ];

        if ($this->sharedSecret !== null) {
            $request['password'] = $this->sharedSecret;
        }

        $data = json_encode($request);
        if ($data === false) {
            throw new ValidationException('Unable to encode data to iTunes server');
        }

        return $data;
    }

    /**
     * Get receipt data.
     *
     * @return string|null
     */
    public function getReceiptData(): ?string
    {
        return $this->receiptData;
    }

    /**
     * Set receipt data, either in base64 or as raw JSON (auto-encoded).
     *
     * @param string $receiptData
     * @return $this
     */
    public function setReceiptData(string $receiptData = ''): self
    {
        $trimmed = ltrim($receiptData);
        if ($trimmed !== '' && $trimmed[0] === '{') {
            $this->receiptData = base64_encode($receiptData);
        } else {
            $this->receiptData = $receiptData;
        }

        return $this;
    }
}
