<?php

namespace ReceiptValidator\AppleAppStore;

use GuzzleHttp\Exception\GuzzleException;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\AppleAppStore\JWT\Key;
use ReceiptValidator\AppleAppStore\JWT\Issuer;
use ReceiptValidator\AppleAppStore\JWT\GeneratorConfig;
use ReceiptValidator\AppleAppStore\JWT\AppStoreJwsGenerator;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

/**
 * App Store Server API Validator
 */
class Validator extends AbstractValidator
{
    public const string ENDPOINT_SANDBOX = 'https://api.storekit-sandbox.itunes.apple.com';
    public const string ENDPOINT_PRODUCTION = 'https://api.storekit.itunes.apple.com';

    protected ?string $transactionId = null;

    protected string $signingKey;
    protected string $keyId;
    protected string $issuerId;
    protected string $bundleId;

    public function __construct(
        string $signingKey,
        string $keyId,
        string $issuerId,
        string $bundleId,
        Environment $environment = Environment::PRODUCTION
    ) {
        $this->environment = $environment;
        $this->signingKey = $signingKey;
        $this->keyId = $keyId;
        $this->issuerId = $issuerId;
        $this->bundleId = $bundleId;
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
            $this->transactionId = $transactionId;
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
                    'Authorization' => "Bearer {$token}",
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ValidationException('Unable to connect to App Store Server API - ' . $e->getMessage(), 0, $e);
        }

        $decodedBody = json_decode($httpResponse->getBody(), true);

        if ($httpResponse->getStatusCode() !== 200) {
            $errorMessage = $decodedBody['errorMessage'] ?? 'Unexpected status from App Store Server API: ' . $httpResponse->getStatusCode();
            throw new ValidationException($errorMessage);
        }


        return new Response($decodedBody, $this->environment);
    }

    /**
     * Generate a JWT using jwt and internal JWT classes.
     *
     * @return string
     */
    private function generateToken(): string
    {
        $config = GeneratorConfig::forAppStore(
            new Issuer(
                $this->issuerId,
                $this->bundleId,
                new Key($this->keyId, InMemory::plainText($this->signingKey)),
                new Sha256()
            ),
        );
        $jwsGenerator = new AppStoreJwsGenerator($config);

        return (string)$jwsGenerator->generate();
    }
}
