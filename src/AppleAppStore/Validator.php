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
 * App Store Server API Validator
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
     * App Store Connect private key.
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
     * @param string $signingKey
     * @param string $keyId
     * @param string $issuerId
     * @param string $bundleId
     * @param Environment $environment
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

        return $this->makeRequest();
    }

    /**
     * Perform the HTTP request to the App Store API.
     *
     * @return Response
     * @throws ValidationException
     */
    protected function makeRequest(): Response
    {
        if (empty($this->transactionId)) {
            throw new ValidationException('Missing transaction ID for App Store Server API validation.');
        }

        $endpoint = $this->environment === Environment::PRODUCTION
            ? self::ENDPOINT_PRODUCTION
            : self::ENDPOINT_SANDBOX;

        $url = sprintf('%s/inApps/v2/history/%s', $endpoint, $this->transactionId);
        $token = $this->generateToken();

        try {
            $httpResponse = $this->getClient($endpoint)->request('GET', $url, [
                'headers' => [
                    'Authorization' => "Bearer {$token->toString()}",
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ValidationException('Unable to connect to App Store Server API - ' . $e->getMessage(), 0, $e);
        }

        $body = (string) $httpResponse->getBody();
        $decoded = json_decode($body, true);

        if ($httpResponse->getStatusCode() !== 200) {
            $error = $decoded['errorMessage'] ?? "Unexpected status: {$httpResponse->getStatusCode()}";
            throw new ValidationException($error);
        }

        if (!is_array($decoded)) {
            throw new ValidationException('Invalid response format from App Store Server API.');
        }

        return new Response($decoded, $this->environment);
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
}
