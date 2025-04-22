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
