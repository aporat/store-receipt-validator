<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;
use ValueError;

/**
 * Represents an App Store Server Notification V2.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/responsebodyv2decodedpayload
 */
class ServerNotification
{
    protected ServerNotificationType $notificationType;
    protected ?ServerNotificationSubtype $subtype = null;
    protected Environment $environment;
    protected CarbonImmutable $signedDate;
    protected string $bundleId = '';
    protected string $notificationUUID = '';
    protected ?Transaction $transaction = null;
    protected ?RenewalInfo $renewalInfo = null;

    /**
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    public function __construct(array $data)
    {
        if (!array_key_exists('signedPayload', $data)) {
            throw new ValidationException('signedPayload key is missing from signed payload');
        }

        $token = TokenGenerator::decodeToken($data['signedPayload']);

        $verifier = new TokenVerifier();
        if (!$verifier->verify($token)) {
            throw new ValidationException('Signature verification failed for server notification');
        }

        $claims = $token->claims()->all();

        // notificationType (required)
        $typeRaw = (string)($claims['notificationType'] ?? '');
        try {
            $this->notificationType = ServerNotificationType::from($typeRaw);
        } catch (ValueError) {
            throw new ValidationException("Unknown notificationType: {$typeRaw}");
        }

        // subtype (optional)
        if (!empty($claims['subtype'])) {
            $this->subtype = ServerNotificationSubtype::tryFrom((string)$claims['subtype']);
        }

        // Simple scalars
        $this->notificationUUID = (string)($claims['notificationUUID'] ?? '');
        $this->signedDate       = CarbonImmutable::createFromTimestampMs((int)($claims['signedDate'] ?? 0));

        $dataClaims = is_array($claims['data'] ?? null) ? $claims['data'] : [];

        $this->bundleId   = (string)($dataClaims['bundleId'] ?? '');
        $envRaw           = (string)($dataClaims['environment'] ?? 'Sandbox');
        $this->environment = Environment::fromString($envRaw); // accepts "sandbox", "production", "prod"

        // Nested signed JWS blobs → decode, then hydrate typed objects
        if (!empty($dataClaims['signedTransactionInfo'])) {
            $txToken       = TokenGenerator::decodeToken($dataClaims['signedTransactionInfo']);
            $this->transaction = new Transaction($txToken->claims()->all());
        }

        if (!empty($dataClaims['signedRenewalInfo'])) {
            $renewalToken  = TokenGenerator::decodeToken($dataClaims['signedRenewalInfo']);
            $this->renewalInfo = new RenewalInfo($renewalToken->claims()->all());
        }
    }

    public function getNotificationType(): ServerNotificationType
    {
        return $this->notificationType;
    }

    public function getSubtype(): ?ServerNotificationSubtype
    {
        return $this->subtype;
    }

    public function getNotificationUUID(): string
    {
        return $this->notificationUUID;
    }

    public function getSignedDate(): CarbonInterface
    {
        return $this->signedDate;
    }

    public function getBundleId(): string
    {
        return $this->bundleId;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function getRenewalInfo(): ?RenewalInfo
    {
        return $this->renewalInfo;
    }
}
