<?php

namespace ReceiptValidator\Amazon;

use GuzzleHttp\Exception\GuzzleException;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

class Validator extends AbstractValidator
{
    /**
     * Amazon RVS Error: Invalid receiptID
     *
     * @var int
     */
    public const int RESULT_INVALID_RECEIPT = 400;

    /**
     * Amazon RVS Error: Invalid developerSecret
     *
     * @var int
     */
    public const int RESULT_INVALID_DEVELOPER_SECRET = 496;

    /**
     * Amazon RVS Error: Invalid userId
     *
     * @var int
     */
    public const int RESULT_INVALID_USER_ID = 497;

    /**
     * Amazon RVS Error: Internal Server Error
     *
     * @var int
     */
    public const int RESULT_INTERNAL_ERROR = 500;

    /**
     * Amazon RVS sandbox endpoint.
     *
     * @var string
     */
    public const string ENDPOINT_SANDBOX = 'https://appstore-sdk.amazon.com/sandbox';

    /**
     * Amazon RVS production endpoint.
     *
     * @var string
     */
    public const string ENDPOINT_PRODUCTION = 'https://appstore-sdk.amazon.com';

    /**
     * Error messages for known Amazon RVS response codes.
     *
     * @var array<int, string>
     */
    protected const array ERROR_MESSAGES = [
        self::RESULT_INVALID_RECEIPT => 'Invalid receipt ID.',
        self::RESULT_INVALID_DEVELOPER_SECRET => 'Invalid developer secret.',
        self::RESULT_INVALID_USER_ID => 'Invalid user ID.',
        self::RESULT_INTERNAL_ERROR => 'Internal server error.',
    ];

    /**
     * User ID.
     *
     * @var string|null
     */
    protected ?string $userId = null;

    /**
     * Receipt ID.
     *
     * @var string|null
     */
    protected ?string $receiptId = null;

    /**
     * Developer secret.
     *
     * @var string|null
     */
    protected ?string $developerSecret = null;

    /**
     * Validator constructor.
     *
     * @param string $developerSecret
     * @param Environment $environment
     */
    public function __construct(string $developerSecret, Environment $environment)
    {
        $this->developerSecret = $developerSecret;
        $this->environment = $environment;
    }

    /**
     * Validate the receipt by sending a request to Amazon's RVS.
     *
     * @return Response
     * @throws ValidationException
     */
    public function validate(): Response
    {
        return $this->makeRequest();
    }

    /**
     * Perform the HTTP request and parse the response.
     *
     * @return Response
     * @throws ValidationException
     */
    protected function makeRequest(): Response
    {
        $endpoint = $this->environment === Environment::PRODUCTION
            ? self::ENDPOINT_PRODUCTION
            : self::ENDPOINT_SANDBOX;

        try {
            $httpResponse = $this->getClient($endpoint)->request(
                'GET',
                sprintf(
                    '/version/1.0/verifyReceiptId/developer/%s/user/%s/receiptId/%s',
                    $this->developerSecret,
                    $this->userId,
                    $this->receiptId
                )
            );

            $statusCode = $httpResponse->getStatusCode();

            if ($statusCode !== 200) {
                $message = self::ERROR_MESSAGES[$statusCode] ?? 'Unexpected error occurred while validating the receipt.';
                throw new ValidationException($message, $statusCode);
            }

            $decodedBody = json_decode($httpResponse->getBody(), true);
            return new Response($decodedBody, $this->environment);
        } catch (GuzzleException $e) {
            throw new ValidationException('Amazon validation request failed', 0, $e);
        }
    }

    /**
     * Get the developer secret.
     *
     * @return string|null
     */
    public function getDeveloperSecret(): ?string
    {
        return $this->developerSecret;
    }

    /**
     * Set the developer secret.
     *
     * @param string|null $developerSecret
     * @return $this
     */
    public function setDeveloperSecret(?string $developerSecret): self
    {
        $this->developerSecret = $developerSecret;
        return $this;
    }

    /**
     * Set the user ID.
     *
     * @param string|null $userId
     * @return $this
     */
    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Set the receipt ID.
     *
     * @param string|null $receiptId
     * @return $this
     */
    public function setReceiptId(?string $receiptId): self
    {
        $this->receiptId = $receiptId;
        return $this;
    }
}
