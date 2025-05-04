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
     * Perform the HTTP request and handle retry logic if needed.
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
                [RequestOptions::BODY => $this->prepareRequestData()]
            );
        } catch (GuzzleException $e) {
            throw new ValidationException('Unable to connect to iTunes server - ' . $e->getMessage(), 0, $e);
        }

        if ($httpResponse->getStatusCode() !== 200) {
            throw new ValidationException('Unable to get response from iTunes server');
        }

        $decodedBody = json_decode($httpResponse->getBody(), true);
        $status = $decodedBody['status'] ?? APIError::VALID;

        if (
            $this->environment === Environment::PRODUCTION &&
            $status === APIError::SANDBOX_RECEIPT_ON_PRODUCTION
        ) {
            return $this->makeRequest(Environment::SANDBOX);
        }

        if ($status !== APIError::VALID && $status !== APIError::SUBSCRIPTION_EXPIRED) {
            $messages = APIError::messages();
            $description = $messages[$status] ?? 'Unknown error';
            $fullMessage = "iTunes API error [{$status}]: {$description}";
            throw new ValidationException($fullMessage, $status);
        }

        return new Response($decodedBody, $this->environment);
    }

    /**
     * Prepare request data (json).
     *
     * @return string
     * @throws ValidationException
     */
    protected function prepareRequestData(): string
    {
        $request = [
            'receipt-data' => $this->getReceiptData()
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
     * Set receipt data, either in base64 or in JSON.
     *
     * @param string $receiptData
     * @return $this
     */
    public function setReceiptData(string $receiptData = ''): self
    {
        if (str_contains($receiptData, '{')) {
            $this->receiptData = base64_encode($receiptData);
        } else {
            $this->receiptData = $receiptData;
        }

        return $this;
    }
}
