<?php

declare(strict_types=1);

namespace ReceiptValidator\iTunes;

use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\Support\ValueCasting;
use ValueError;

/**
 * Represents an App Store Server Notification V1.
 *
 * @deprecated since version 2.0. Use {@see \ReceiptValidator\AppleAppStore\ServerNotification} instead.
 *             Apple has deprecated V1 notifications in favor of App Store Server Notifications V2.
 * @see https://developer.apple.com/documentation/appstoreservernotifications/appstoreservernotification
 * @see https://developer.apple.com/documentation/appstoreservernotifications
 */
class ServerNotification
{
    use ValueCasting;

    protected ServerNotificationType $notificationType;
    protected Environment $environment;

    protected string $autoRenewProductId = '';
    protected bool $autoRenewStatus = false;

    /** @var CarbonImmutable|null */
    protected ?CarbonImmutable $autoRenewStatusChangeDate = null;

    protected string $bundleId = '';
    protected string $bvrs = '';
    protected string $originalTransactionId = '';
    protected string $password = '';

    protected Response $latestReceipt;
    protected ?RenewalInfo $pendingRenewalInfo = null;

    /**
     * @param array<string, mixed> $data
     * @param string|null          $sharedSecret
     * @throws ValidationException
     */
    public function __construct(array $data, ?string $sharedSecret = null)
    {
        // Validate shared secret if one was provided by the caller
        if ($sharedSecret !== null) {
            if (!isset($data['password']) || $data['password'] !== $sharedSecret) {
                throw new ValidationException('Invalid shared secret');
            }
        }

        // Required keys (throw with clear messages if missing/invalid)
        $this->password = $data['password'] ?? '';
        if ($this->password === '') {
            throw new ValidationException('Missing password in server notification payload.');
        }

        $typeRaw = (string)($data['notification_type'] ?? '');
        try {
            $this->notificationType = ServerNotificationType::from($typeRaw);
        } catch (ValueError) {
            throw new ValidationException("Unknown notification_type: $typeRaw");
        }

        // Environment via ValueCasting (accepts 'production', 'prod', 'sandbox', etc.)
        // Default to SANDBOX if missing (matches prior behavior).
        $this->environment = $this->toEnvironment($data, 'environment', Environment::SANDBOX);

        // Optional scalars (string defaults are empty for BC with getters)
        $this->autoRenewProductId    = $this->toString($data, 'auto_renew_product_id', '') ?? '';
        $this->bundleId              = $this->toString($data, 'bid', '') ?? '';
        $this->bvrs                  = $this->toString($data, 'bvrs', '') ?? '';
        $this->originalTransactionId = $this->toString($data, 'original_transaction_id', '') ?? '';

        // Booleans (non-nullable; default false)
        $this->autoRenewStatus = $this->toBool($data, 'auto_renew_status');

        // Dates (ms → CarbonImmutable)
        $this->autoRenewStatusChangeDate = $this->toDateFromMs($data, 'auto_renew_status_change_date_ms');

        // Unified receipt is required for v1 notifications
        $unified = $data['unified_receipt'] ?? null;
        if (!is_array($unified)) {
            throw new ValidationException('Missing or invalid unified_receipt in server notification payload.');
        }
        $this->latestReceipt = new Response($unified, $this->environment);

        // Pending renewal info (first element if present)
        $pri = $unified['pending_renewal_info'][0] ?? null;
        if (is_array($pri)) {
            $this->pendingRenewalInfo = new RenewalInfo($pri);
        }
    }

    public function getNotificationType(): ServerNotificationType
    {
        return $this->notificationType;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function getAutoRenewProductId(): string
    {
        return $this->autoRenewProductId;
    }

    public function getAutoRenewStatus(): bool
    {
        return $this->autoRenewStatus;
    }

    public function getAutoRenewStatusChangeDate(): ?CarbonInterface
    {
        return $this->autoRenewStatusChangeDate;
    }

    public function getBundleId(): string
    {
        return $this->bundleId;
    }

    public function getBvrs(): string
    {
        return $this->bvrs;
    }

    public function getOriginalTransactionId(): string
    {
        return $this->originalTransactionId;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getLatestReceipt(): Response
    {
        return $this->latestReceipt;
    }

    public function getPendingRenewalInfo(): ?RenewalInfo
    {
        return $this->pendingRenewalInfo;
    }
}
