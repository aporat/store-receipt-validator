<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use GuzzleHttp\Exception\GuzzleException;
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

    /** @return array{production:string, sandbox:string} */
    protected function endpointMap(): array
    {
        return [
            Environment::PRODUCTION->value => self::ENDPOINT_PRODUCTION,
            Environment::SANDBOX->value    => self::ENDPOINT_SANDBOX,
        ];
    }

    /** Transaction ID to validate. */
    protected ?string $transactionId = null;

    /** App Store Connect's private key (PEM or raw .p8 contents). */
    protected string $signingKey;

    /** Key ID for the private key. */
    protected string $keyId;

    /** Issuer ID (App Store Connect API key issuer). */
    protected string $issuerId;

    /** App bundle ID. */
    protected string $bundleId;

    /**
     * @param string $signingKey The contents of your .p8 key
     * @param string $keyId      The Key ID from App Store Connect
     * @param string $issuerId   Your Issuer ID
     * @param string $bundleId   Your app's bundle identifier
     * @param Environment $environment Target environment (defaults to PRODUCTION)
     */
    public function __construct(
        string $signingKey,
        string $keyId,
        string $issuerId,
        string $bundleId,
        Environment $environment = Environment::PRODUCTION
    ) {
        $this->signingKey   = $signingKey;
        $this->keyId        = $keyId;
        $this->issuerId     = $issuerId;
        $this->bundleId     = $bundleId;
        $this->environment  = $environment;
    }

    /**
     * Validate the transaction by calling the App Store Server API.
     *
     * @throws ValidationException
     */
    public function validate(?string $transactionId = null): Response
    {
        if ($transactionId !== null) {
            $this->setTransactionId($transactionId);
        }

        if ($this->transactionId === null || $this->transactionId === '') {
            throw new ValidationException('Missing transaction ID for App Store Server API validation.');
        }

        $uri = sprintf('/inApps/v2/history/%s', $this->transactionId);

        return $this->makeRequest('GET', $uri, [
            'sort' => 'DESCENDING',
        ]);
    }

    /**
     * Set the transaction ID (fluent).
     */
    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * Perform the HTTP request to the App Store API.
     *
     * @param array<string,mixed> $queryParams
     * @throws ValidationException
     */
    protected function makeRequest(string $method, string $uri = '', array $queryParams = []): Response
    {
        $endpoint = $this->endpointForEnvironment();

        $token = $this->generateToken();

        try {
            $httpResponse = $this->getClient($endpoint)->request($method, $uri, [
                'headers' => $this->buildHeaders($token),
                'query'   => $queryParams,
            ]);
        } catch (GuzzleException $e) {
            throw new ValidationException('Unable to connect to App Store Server API - ' . $e->getMessage(), 0, $e);
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
                new Sha256()
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
        $data = $this->makeRequest('POST', '/inApps/v1/notifications/test')->getRawData();

        if (!isset($data['testNotificationToken'])) {
            throw new ValidationException('Missing testNotificationToken in response.');
        }

        return (string) $data['testNotificationToken'];
    }

    /**
     * Build request headers with the given token.
     *
     * @return array<string,string>
     */
    private function buildHeaders(Token $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token->toString(),
            'Accept'        => 'application/json',
        ];
    }
}
