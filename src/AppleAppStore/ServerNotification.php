<?php

namespace ReceiptValidator\AppleAppStore;

use Carbon\Carbon;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;

/**
 * Represents an App Store Server Notification V2.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/responsebodyv2decodedpayload
 */
class ServerNotification
{
    /**
     * @var ServerNotificationType
     */
    protected ServerNotificationType $notificationType;

    /**
     * @var ServerNotificationSubtype|null
     */
    protected ?ServerNotificationSubtype $subtype = null;

    /**
     * @var Environment
     */
    protected Environment $environment;

    /**
     * @var Carbon
     */
    protected Carbon $signedDate;

    /**
     * @var string
     */
    protected string $bundleId;

    /**
     * @var string
     */
    protected string $notificationUUID;

    /**
     * @var Transaction|null
     */
    protected ?Transaction $transaction = null;

    /**
     * @var RenewalInfo|null
     */
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

        $typeValue = $claims['notificationType'] ?? '';
        $this->notificationType = ServerNotificationType::from($typeValue);

        if (!empty($claims['subtype'])) {
            $this->subtype = ServerNotificationSubtype::tryFrom($claims['subtype']);
        }

        $this->notificationUUID = $claims['notificationUUID'] ?? '';
        $this->signedDate = Carbon::createFromTimestampMs($claims['signedDate'] ?? 0);
        $this->bundleId = $claims['data']['bundleId'] ?? '';

        $env = $claims['data']['environment'] ?? 'Sandbox';
        $this->environment = $env === 'Production' ? Environment::PRODUCTION : Environment::SANDBOX;

        if (!empty($claims['data']['signedTransactionInfo'])) {
            $txToken = TokenGenerator::decodeToken($claims['data']['signedTransactionInfo']);
            $this->transaction = new Transaction($txToken->claims()->all());
        }

        if (!empty($claims['data']['signedRenewalInfo'])) {
            $renewalToken = TokenGenerator::decodeToken($claims['data']['signedRenewalInfo']);
            $this->renewalInfo = new RenewalInfo($renewalToken->claims()->all());
        }
    }

    /**
     * @return ServerNotificationType
     */
    public function getNotificationType(): ServerNotificationType
    {
        return $this->notificationType;
    }

    /**
     * @return ServerNotificationSubtype|null
     */
    public function getSubtype(): ?ServerNotificationSubtype
    {
        return $this->subtype;
    }

    /**
     * @return string
     */
    public function getNotificationUUID(): string
    {
        return $this->notificationUUID;
    }

    /**
     * @return Carbon
     */
    public function getSignedDate(): Carbon
    {
        return $this->signedDate;
    }

    /**
     * @return string
     */
    public function getBundleId(): string
    {
        return $this->bundleId;
    }

    /**
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * @return Transaction|null
     */
    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    /**
     * @return RenewalInfo|null
     */
    public function getRenewalInfo(): ?RenewalInfo
    {
        return $this->renewalInfo;
    }
}
