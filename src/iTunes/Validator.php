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
     * Receipt response is valid.
     *
     * @var int
     */
    public const int RESULT_OK = 0;

    /**
     * The receipt is valid, but purchased nothing.
     *
     * @var int
     */
    public const int RESULT_VALID_NO_PURCHASE = 2;

    /**
     * The App Store could not read the JSON object you provided.
     *
     * @var int
     */
    public const int RESULT_APPSTORE_CANNOT_READ = 21000;

    /**
     * The data in the receipt-data property was malformed or missing.
     *
     * @var int
     */
    public const int RESULT_DATA_MALFORMED = 21002;

    /**
     * The receipt could not be authenticated.
     *
     * @var int
     */
    public const int RESULT_RECEIPT_NOT_AUTHENTICATED = 21003;

    /**
     * The shared secret does not match.
     *
     * @var int
     */
    public const int RESULT_SHARED_SECRET_NOT_MATCH = 21004;

    /**
     * The receipt server is not currently available.
     *
     * @var int
     */
    public const int RESULT_RECEIPT_SERVER_UNAVAILABLE = 21005;

    /**
     * This receipt is valid but the subscription has expired.
     *
     * @var int
     */
    public const int RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED = 21006;

    /**
     * Sandbox receipt sent to production.
     *
     * @var int
     */
    public const int RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION = 21007;

    /**
     * Production receipt sent to sandbox.
     *
     * @var int
     */
    public const int RESULT_PRODUCTION_RECEIPT_SENT_TO_SANDBOX = 21008;

    /**
     * This receipt could not be authorized.
     *
     * @var int
     */
    public const int RESULT_RECEIPT_WITHOUT_PURCHASE = 21010;

    /**
     * Apple error message map.
     *
     * @var array<int, string>
     */
    protected const array ERROR_MESSAGES = [
        self::RESULT_APPSTORE_CANNOT_READ => 'The App Store could not read the JSON object you provided.',
        self::RESULT_DATA_MALFORMED => 'The data in the receipt-data property was malformed.',
        self::RESULT_RECEIPT_NOT_AUTHENTICATED => 'The receipt could not be authenticated.',
        self::RESULT_SHARED_SECRET_NOT_MATCH => 'The shared secret you provided does not match the shared secret on file for your account.',
        self::RESULT_RECEIPT_SERVER_UNAVAILABLE => 'The receipt server is not currently available.',
        self::RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED => 'This receipt is valid but the subscription has expired.',
        self::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION => 'This receipt is a sandbox receipt, but it was sent to the production service for verification.',
        self::RESULT_PRODUCTION_RECEIPT_SENT_TO_SANDBOX => 'This receipt is a production receipt, but it was sent to the sandbox service for verification.',
        self::RESULT_VALID_NO_PURCHASE => 'The receipt is valid, but purchased nothing.',
        self::RESULT_RECEIPT_WITHOUT_PURCHASE => 'This receipt could not be authorized. Treat this the same as if a purchase was never made.',
    ];

    /**
     * iTunes receipt data, in base64 format.
     *
     * @var string|null
     */
    protected ?string $receipt_data = null;

    /**
     * The shared secret for auto-renewable subscriptions.
     *
     * @var string|null
     */
    protected ?string $shared_secret = null;

    /**
     * Get the shared secret.
     *
     * @return string|null
     */
    public function getSharedSecret(): ?string
    {
        return $this->shared_secret;
    }

    /**
     * Set the shared secret.
     *
     * @param string|null $shared_secret
     * @return $this
     */
    public function setSharedSecret(?string $shared_secret = null): self
    {
        $this->shared_secret = $shared_secret;

        return $this;
    }

    /**
     * Validate the receipt.
     *
     * @param string|null $receipt_data
     * @return Response
     * @throws ValidationException
     */
    public function validate(?string $receipt_data = null): Response
    {
        if ($receipt_data !== null) {
            $this->setReceiptData($receipt_data);
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
        $status = $decodedBody['status'] ?? self::RESULT_OK;

        if (
            $this->environment === Environment::PRODUCTION &&
            $status === self::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION
        ) {
            return $this->makeRequest(Environment::SANDBOX);
        }

        if ($status !== self::RESULT_OK && $status !== self::RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED) {
            $message = self::ERROR_MESSAGES[$status] ?? 'Unknown error occurred during receipt validation';
            throw new ValidationException($message);
        }

        return new Response($decodedBody, $this->environment);
    }

    /**
     * Prepare request data (json).
     *
     * @return string
     */
    protected function prepareRequestData(): string
    {
        $request = [
            'receipt-data' => $this->getReceiptData()
        ];

        if ($this->shared_secret !== null) {
            $request['password'] = $this->shared_secret;
        }

        return json_encode($request);
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
     * Set receipt data, either in base64 or in JSON.
     *
     * @param string $receipt_data
     * @return $this
     */
    public function setReceiptData(string $receipt_data = ''): self
    {
        if (str_contains($receipt_data, '{')) {
            $this->receipt_data = base64_encode($receipt_data);
        } else {
            $this->receipt_data = $receipt_data;
        }

        return $this;
    }
}
