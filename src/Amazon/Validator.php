<?php

namespace ReceiptValidator\Amazon;

use GuzzleHttp\Exception\GuzzleException;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

class Validator extends AbstractValidator
{
    /**
     * Amazon RVS sandbox endpoint.
     */
    public const string ENDPOINT_SANDBOX = 'https://appstore-sdk.amazon.com/sandbox';

    /**
     * Amazon RVS production endpoint.
     */
    public const string ENDPOINT_PRODUCTION = 'https://appstore-sdk.amazon.com';

    /**
     * User ID.
     */
    protected ?string $userId = null;

    /**
     * Receipt ID.
     */
    protected ?string $receiptId = null;

    /**
     * Developer secret.
     */
    protected ?string $developerSecret = null;

    /**
     * Validator constructor.
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

            $body = (string)$httpResponse->getBody();
            $decodedBody = json_decode($body, true);

            if ($httpResponse->getStatusCode() !== 200) {
                $httpStatusCode = $httpResponse->getStatusCode();

                $description = $decodedBody['message'] ?? 'An unexpected error occurred.';
                $fullMessage = "Amazon API error [{$httpStatusCode}]: {$description}";

                throw new ValidationException($fullMessage, $httpStatusCode);
            }

            return new Response($decodedBody, $this->environment);
        } catch (GuzzleException $e) {
            throw new ValidationException('Amazon validation request failed', 0, $e);
        }
    }

    public function getDeveloperSecret(): ?string
    {
        return $this->developerSecret;
    }

    public function setDeveloperSecret(?string $developerSecret): self
    {
        $this->developerSecret = $developerSecret;
        return $this;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function setReceiptId(?string $receiptId): self
    {
        $this->receiptId = $receiptId;
        return $this;
    }
}
