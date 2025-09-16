<?php

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
    /**
     * Sandbox endpoint URL.
     */
    public const string ENDPOINT_SANDBOX = 'https://api.storekit-sandbox.itunes.apple.com';

    /**
     * Production endpoint URL.
     */
    public const string ENDPOINT_PRODUCTION = 'https://api.storekit.itunes.apple.com';

    /**
     * Transaction ID to validate.
     *
     * @var string|null
     */
    protected ?string $transactionId = null;

    /**
     * App Store Connect's private key (PEM or raw .p8 contents).
     *
     * @var string
     */
    protected string $signingKey;

    /**
     * Key ID for the private key.
     *
     * @var string
     */
    protected string $keyId;

    /**
     * Issuer ID (App Store Connect API key issuer).
     *
     * @var string
     */
    protected string $issuerId;

    /**
     * App bundle ID.
     *
     * @var string
     */
    protected string $bundleId;

    /**
     * Validator constructor.
     *
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
        $this->signingKey = $signingKey;
        $this->keyId = $keyId;
        $this->issuerId = $issuerId;
        $this->bundleId = $bundleId;
        $this->environment = $environment;
    }

    /**
     * Validate the transaction by calling the App Store Server API.
     *
     * @param string|null $transactionId
     * @return Response
     * @throws ValidationException
     */
    public function validate(?string $transactionId = null): Response
    {
        if ($transactionId !== null) {
            $this->setTransactionId($transactionId);
        }

        if (empty($this->transactionId)) {
            throw new ValidationException('Missing transaction ID for App Store Server API validation.');
        }

        $uri = sprintf('/inApps/v2/history/%s', $this->transactionId);

        $queryParams = [
            'sort' => 'DESCENDING',
        ];

        return $this->makeRequest('GET', $uri, $queryParams);
    }

    /**
     * Set the transaction ID.
     *
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * Perform the HTTP request to the App Store API.
     *
     * @param string $method
     * @param string $uri
     * @param array<string, mixed> $queryParams
     * @return Response
     * @throws ValidationException
     */
    protected function makeRequest(string $method, string $uri = '', array $queryParams = []): Response
    {
        $endpoint = $this->environment === Environment::PRODUCTION
            ? self::ENDPOINT_PRODUCTION
            : self::ENDPOINT_SANDBOX;

        $token = $this->generateToken();

        try {
            $httpResponse = $this->getClient($endpoint)->request($method, $uri, [
                'headers' => [
                    'Authorization' => "Bearer {$token->toString()}",
                    'Accept'        => 'application/json',
                ],
                'query' => $queryParams,
            ]);
        } catch (GuzzleException $e) {
            throw new ValidationException('Unable to connect to App Store Server API - ' . $e->getMessage(), 0, $e);
        }

        $statusCode = $httpResponse->getStatusCode();
        $body       = (string) $httpResponse->getBody();

        // Parse JSON (if any)
        $decoded = json_decode($body, true);
        $isJson  = is_array($decoded);

        if ($statusCode !== 200) {
            // Friendly defaults for common auth/not-found responses
            $errorMessage = match ($statusCode) {
                401 => 'Unauthenticated',
                404 => 'Not Found',
                default => ($isJson ? ($decoded['errorMessage'] ?? null) : null) ?? ($body !== '' ? $body : 'Unexpected error'),
            };

            // Prefer Apple's machine errorCode when present; otherwise use HTTP status
            $errorCode = $isJson && isset($decoded['errorCode'])
                ? (int) $decoded['errorCode']
                : $statusCode;

            $fullMessage = "App Store API error [{$errorCode}]: {$errorMessage}";
            throw new ValidationException($fullMessage, $errorCode);
        }

        if (!$isJson) {
            throw new ValidationException('Invalid response format from App Store Server API.');
        }

        return new Response($decoded);
    }

    /**
     * Generate a JWT for authenticating with the App Store Server API.
     *
     * @return Token
     * @throws ValidationException
     */
    private function generateToken(): Token
    {
        try {
            $signingKey = $this->signingKey;

            if ($signingKey === '') {
                throw new ValidationException('Cannot generate a token without a signing key.');
            }

            $issuer = new TokenIssuer(
                $this->issuerId,
                $this->bundleId,
                new TokenKey($this->keyId, InMemory::plainText($signingKey)),
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
     * @return string
     * @throws ValidationException
     */
    public function requestTestNotification(): string
    {
        $data = $this->makeRequest('POST', '/inApps/v1/notifications/test')->getRawData();

        if (!isset($data['testNotificationToken'])) {
            throw new ValidationException('Missing testNotificationToken in response.');
        }

        return $data['testNotificationToken'];
    }
}
