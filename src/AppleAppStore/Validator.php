<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain as Token;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenGeneratorConfig;
use ReceiptValidator\AppleAppStore\JWT\TokenIssuer;
use ReceiptValidator\AppleAppStore\JWT\TokenKey;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use Throwable;

/**
 * App Store Server API Validator.
 */
class Validator extends AbstractValidator
{
    /** Sandbox endpoint URL. */
    public const string ENDPOINT_SANDBOX = 'https://api.storekit-sandbox.itunes.apple.com';

    /** Production endpoint URL. */
    public const string ENDPOINT_PRODUCTION = 'https://api.storekit.itunes.apple.com';

    /** Transaction ID to validate. */
    private ?string $transactionId = null;

    /**
     * @param string $signingKey The contents of your .p8 key
     * @param string $keyId      The Key ID from App Store Connect
     * @param string $issuerId   Your Issuer ID
     * @param string $bundleId   Your app's bundle identifier
     * @param Environment $environment Target environment (defaults to PRODUCTION)
     */
    public function __construct(
        private readonly string $signingKey,
        private readonly string $keyId,
        private readonly string $issuerId,
        private readonly string $bundleId,
        Environment $environment = Environment::PRODUCTION,
    ) {
        $this->environment  = $environment;
    }

    /**
     * Validate the transaction by calling the App Store Server API.
     *
     * @throws ValidationException
     */
    public function validate(?string $transactionId = null): Response
    {
        $transactionId ??= $this->transactionId;
        if (empty($transactionId)) {
            throw new ValidationException('Missing transaction ID for App Store Server API validation.');
        }

        return $this->getResponse('GET', sprintf('/inApps/v2/history/%s', $transactionId), [
            RequestOptions::QUERY => [
                'sort' => 'DESCENDING',
            ],
        ]);
    }

    /**
     * Set the transaction ID (fluent).
     *
     * @param non-empty-string $transactionId
     */
    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * Generate a JWT for authenticating with the App Store Server API.
     *
     * @throws ValidationException
     */
    private function generateToken(): Token
    {
        try {
            if ($this->signingKey === '') {
                throw new ValidationException('Cannot generate a token without a signing key.');
            }

            $issuer = new TokenIssuer(
                $this->issuerId,
                $this->bundleId,
                new TokenKey($this->keyId, InMemory::plainText($this->signingKey)),
                new Sha256(),
            );

            $config = TokenGeneratorConfig::forAppStore($issuer);

            return (new TokenGenerator($config))->generate();
        } catch (Throwable $e) {
            throw new ValidationException('JWT generation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Request a test notification from the App Store Server API.
     *
     * @throws ValidationException
     */
    public function requestTestNotification(): string
    {
        $data = $this->getResponse('POST', '/inApps/v1/notifications/test')->getRawData();

        if (!isset($data['testNotificationToken'])) {
            throw new ValidationException('Missing testNotificationToken in response.');
        }

        return (string) $data['testNotificationToken'];
    }

    /**
     * @param array<RequestOptions::*, mixed> $options
     *
     * @throws \ReceiptValidator\Exceptions\ValidationException
     */
    private function getResponse(string $method, string $path, array $options = []): Response
    {
        $baseUrl = match ($this->environment) {
            Environment::SANDBOX => self::ENDPOINT_SANDBOX,
            Environment::PRODUCTION => self::ENDPOINT_PRODUCTION,
        };

        try {
            $httpResponse = $this->makeRequest($method, $baseUrl . $path, array_merge([
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer ' . $this->generateToken()->toString(),
                    'Accept' => 'application/json',
                ],
            ], $options));
        } catch (GuzzleException $e) {
            throw new ValidationException('Unable to connect to App Store Server API - ' . $e->getMessage(), previous: $e);
        }

        $statusCode = $httpResponse->getStatusCode();
        $body       = (string) $httpResponse->getBody();

        // Decode JSON (keep parse error message generic to satisfy strict tests)
        $decoded = null;
        $isJson  = false;
        if ($body !== '') {
            try {
                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                $isJson  = is_array($decoded);
            } catch (Throwable) {
                if ($statusCode === 200) {
                    throw new ValidationException('Invalid response format from App Store Server API.');
                }
            }
        }

        if ($statusCode !== 200) {
            // If Apple returns an errorCode, prefer enum-based message/code
            $apiCase = ($isJson && isset($decoded['errorCode']))
                ? APIError::tryFrom((int) $decoded['errorCode'])
                : null;

            if ($apiCase !== null) {
                $errorCode    = $apiCase->value;
                $errorMessage = $apiCase->message();
            } else {
                // Friendly defaults for common auth/not-found responses
                $errorMessage = match ($statusCode) {
                    401     => 'Unauthenticated',
                    404     => 'Not Found',
                    default => ($isJson ? ($decoded['errorMessage'] ?? null) : null) ?? ($body !== '' ? $body : 'Unexpected error'),
                };
                $errorCode = $statusCode;
            }

            throw new ValidationException("App Store API error [$errorCode]: $errorMessage", $errorCode);
        }

        if (!$isJson || !is_array($decoded)) {
            throw new ValidationException('Invalid response format from App Store Server API.');
        }

        return new Response($decoded);
    }
}
