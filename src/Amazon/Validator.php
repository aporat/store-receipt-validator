<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

use GuzzleHttp\Exception\GuzzleException;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

final class Validator extends AbstractValidator
{
    /** Amazon RVS sandbox endpoint. */
    public const string ENDPOINT_SANDBOX = 'https://appstore-sdk.amazon.com/sandbox';

    /** Amazon RVS production endpoint. */
    public const string ENDPOINT_PRODUCTION = 'https://appstore-sdk.amazon.com';

    /** @return array{production:string, sandbox:string} */
    protected function endpointMap(): array
    {
        return [
            Environment::PRODUCTION->value => self::ENDPOINT_PRODUCTION,
            Environment::SANDBOX->value    => self::ENDPOINT_SANDBOX,
        ];
    }

    /** User ID. */
    protected ?string $userId = null;

    /** Receipt ID. */
    protected ?string $receiptId = null;

    /** Developer secret. */
    protected ?string $developerSecret = null;

    public function __construct(string $developerSecret, Environment $environment = Environment::PRODUCTION)
    {
        $this->developerSecret = $developerSecret;
        $this->environment     = $environment;
    }

    /**
     * Validate the receipt by sending a request to Amazon's RVS.
     *
     * @throws ValidationException
     */
    public function validate(): Response
    {
        return $this->makeRequest();
    }

    /**
     * Perform the HTTP request and parse the response.
     *
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

        $endpoint = $this->endpointForEnvironment();

        // URL-encode path segments to be safe with special characters
        $path = sprintf(
            '/version/1.0/verifyReceiptId/developer/%s/user/%s/receiptId/%s',
            rawurlencode($this->developerSecret),
            rawurlencode($this->userId),
            rawurlencode($this->receiptId)
        );

        try {
            $httpResponse = $this->getClient($endpoint)->request('GET', $path);

            $status   = $httpResponse->getStatusCode();
            $rawBody  = (string) $httpResponse->getBody();
            $decoded  = json_decode($rawBody, true);

            // Non-JSON or empty body is an error either way
            if (!is_array($decoded)) {
                throw new ValidationException("Amazon API returned invalid JSON: " . json_last_error_msg(), $status);
            }

            if ($status !== 200) {
                // Amazon typically returns { "message": "InvalidDeveloperSecret" } on errors
                $machine = (string)($decoded['message'] ?? '');

                // If we recognize the machine code, use our friendly description; otherwise use the raw message
                $case = APIError::tryFrom($machine);
                $human = $case?->message() ?? ($machine !== '' ? $machine : 'An unknown error occurred.');

                // Use the HTTP status in the brackets (e.g., 496), per test expectation
                throw new ValidationException("Amazon API error [$status]: $human", $status);
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
