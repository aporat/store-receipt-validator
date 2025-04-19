<?php

namespace ReceiptValidator\iTunes;

use Carbon\Carbon;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Represents an App Store Server Notification V1.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/appstoreservernotification
 */
class ServerNotification
{
    /**
     * @var ServerNotificationType
     */
    protected ServerNotificationType $notificationType;

    /**
     * @var Environment
     */
    protected Environment $environment;

    /**
     * @var string
     */
    protected string $autoRenewProductId;

    /**
     * @var bool
     */
    protected bool $autoRenewStatus;

    /**
     * @var Carbon|null
     */
    protected ?Carbon $autoRenewStatusChangeDate = null;

    /**
     * @var string
     */
    protected string $bundleId;

    /**
     * @var string
     */
    protected string $bvrs;

    /**
     * @var string
     */
    protected string $originalTransactionId;

    /**
     * @var string
     */
    protected string $password;

    /**
     * @var Response
     */
    protected Response $latestReceipt;

    /**
     * @var RenewalInfo|null
     */
    protected ?RenewalInfo $pendingRenewalInfo = null;

    /**
     * @param array<string, mixed> $data
     * @param string|null $sharedSecret
     * @throws ValidationException
     */
    public function __construct(array $data, ?string $sharedSecret = null)
    {
        if (!isset($data['password']) || $data['password'] !== $sharedSecret) {
            throw new ValidationException('Invalid shared secret');
        }

        $this->password = $data['password'];
        $this->notificationType = ServerNotificationType::from($data['notification_type']);
        $this->environment = $data['environment'] === 'PROD' ? Environment::PRODUCTION : Environment::SANDBOX;
        $this->autoRenewProductId = $data['auto_renew_product_id'];
        $this->autoRenewStatus = filter_var($data['auto_renew_status'], FILTER_VALIDATE_BOOLEAN);
        $this->bundleId = $data['bid'];
        $this->bvrs = $data['bvrs'];
        $this->originalTransactionId = (string)$data['original_transaction_id'];

        if (isset($data['auto_renew_status_change_date_ms'])) {
            $this->autoRenewStatusChangeDate = Carbon::createFromTimestampMs((int)$data['auto_renew_status_change_date_ms']);
        }

        $this->latestReceipt = new Response($data['unified_receipt'], $this->environment);

        if (!empty($data['unified_receipt']['pending_renewal_info'][0])) {
            $this->pendingRenewalInfo = new RenewalInfo($data['unified_receipt']['pending_renewal_info'][0]);
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

    public function getAutoRenewStatusChangeDate(): ?Carbon
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
