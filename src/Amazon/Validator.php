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
        if ($this->developerSecret === null || $this->developerSecret === '') {
            throw new ValidationException('Missing Amazon developer secret');
        }
        if ($this->userId === null || $this->userId === '') {
            throw new ValidationException('Missing Amazon userId');
        }
        if ($this->receiptId === null || $this->receiptId === '') {
            throw new ValidationException('Missing Amazon receiptId');
        }

        $endpoint = $this->environment === Environment::PRODUCTION
            ? self::ENDPOINT_PRODUCTION
            : self::ENDPOINT_SANDBOX;

        // URL-encode path segments to be safe with special characters
        $path = sprintf(
            '/version/1.0/verifyReceiptId/developer/%s/user/%s/receiptId/%s',
            rawurlencode($this->developerSecret),
            rawurlencode($this->userId),
            rawurlencode($this->receiptId)
        );

        try {
            $httpResponse = $this->getClient($endpoint)->request('GET', $path);

            $status = $httpResponse->getStatusCode();
            $raw    = (string) $httpResponse->getBody();

            $decoded = json_decode($raw, true);

            // Non-JSON or empty body is an error either way
            if (!is_array($decoded)) {
                $jsonErr = function_exists('json_last_error_msg') ? json_last_error_msg() : 'Unknown JSON error';
                throw new ValidationException("Amazon API returned invalid JSON: {$jsonErr}", $status);
            }

            if ($status !== 200) {
                // Amazon usually returns a string "message" like "InvalidDeveloperSecret"
                $machine = (string) ($decoded['message'] ?? '');
                $case    = APIError::tryFrom($machine);

                // Prefer typed enum message when we recognize it; otherwise fall back to raw text
                $human = $case?->message() ?? ($machine !== '' ? $machine : 'An unexpected error occurred.');
                throw new ValidationException("Amazon API error [{$status}]: {$human}", $status);
            }

            return new Response($decoded, $this->environment);
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
