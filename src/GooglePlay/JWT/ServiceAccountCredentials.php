<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay\JWT;

use JsonException;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * The subset of a Google Cloud service-account key file needed to mint access tokens.
 *
 * Create one from the JSON key downloaded from the Google Cloud console (or the
 * Play Console's "API access" page) via {@see fromJson()}.
 */
final readonly class ServiceAccountCredentials
{
    /** Google's OAuth 2.0 token endpoint, used when the key file does not specify one. */
    public const string DEFAULT_TOKEN_URI = 'https://oauth2.googleapis.com/token';

    /**
     * @param string      $clientEmail  The service account's email address (the JWT issuer).
     * @param string      $privateKey   PEM-encoded RSA private key.
     * @param string|null $privateKeyId Key identifier, sent as the JWT "kid" header when present.
     * @param string      $tokenUri     OAuth token endpoint to exchange the assertion at.
     * @param string|null $projectId    Google Cloud project ID (informational).
     *
     * @throws ValidationException
     */
    public function __construct(
        public string $clientEmail,
        public string $privateKey,
        public ?string $privateKeyId = null,
        public string $tokenUri = self::DEFAULT_TOKEN_URI,
        public ?string $projectId = null,
    ) {
        if (trim($this->clientEmail) === '') {
            throw new ValidationException('Service account credentials are missing client_email.');
        }

        if (trim($this->privateKey) === '') {
            throw new ValidationException('Service account credentials are missing private_key.');
        }

        if (trim($this->tokenUri) === '') {
            throw new ValidationException('Service account credentials have an empty token_uri.');
        }
    }

    /**
     * Build credentials from the contents of a service-account JSON key file.
     *
     * @throws ValidationException
     */
    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ValidationException('Service account JSON could not be decoded: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new ValidationException('Service account JSON must decode to an object.');
        }

        return self::fromArray($data);
    }

    /**
     * Build credentials from an already-decoded service-account key file.
     *
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        $type = $data['type'] ?? 'service_account';
        if ($type !== 'service_account') {
            throw new ValidationException(sprintf('Expected a service_account key file, got type "%s".', (string) $type));
        }

        $tokenUri = $data['token_uri'] ?? null;

        return new self(
            clientEmail: (string) ($data['client_email'] ?? ''),
            privateKey: (string) ($data['private_key'] ?? ''),
            privateKeyId: isset($data['private_key_id']) ? (string) $data['private_key_id'] : null,
            tokenUri: is_string($tokenUri) && $tokenUri !== '' ? $tokenUri : self::DEFAULT_TOKEN_URI,
            projectId: isset($data['project_id']) ? (string) $data['project_id'] : null,
        );
    }
}
