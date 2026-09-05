<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use JsonException;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\Support\ValueCasting;

/**
 * A Google Play Real-time Developer Notification (RTDN).
 *
 * Play publishes a `DeveloperNotification` to a Cloud Pub/Sub topic; a push
 * subscription delivers it to your endpoint wrapped in a Pub/Sub envelope whose
 * `message.data` is the base64-encoded JSON. Use {@see fromPubSubMessage()} for
 * the raw request body, or the constructor if you have already unwrapped it.
 *
 * Unlike Apple's notifications the payload is not signed and carries no purchase
 * data: it tells you *which* purchase token changed. Always re-read the purchase
 * from the Android Publisher API before granting or removing entitlement. Verify
 * the Pub/Sub push itself (OIDC bearer token) at the HTTP layer.
 *
 * Exactly one of the four payloads (subscription, one-time product, voided
 * purchase, test) is present on any given notification.
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference
 */
final class ServerNotification
{
    use ValueCasting;

    /** Notification schema version. */
    protected ?string $version;

    /** The package name of the app the notification is for. */
    protected string $packageName;

    /** When the event occurred. */
    protected CarbonImmutable $eventTime;

    protected ?SubscriptionNotification $subscriptionNotification = null;

    protected ?OneTimeProductNotification $oneTimeProductNotification = null;

    protected ?VoidedPurchaseNotification $voidedPurchaseNotification = null;

    protected bool $testNotification = false;

    /** @var array<string, mixed> */
    protected readonly array $rawData;

    /**
     * @param array<string, mixed> $data The decoded `DeveloperNotification` JSON.
     *
     * @throws ValidationException
     */
    public function __construct(array $data)
    {
        $this->rawData = $data;

        $packageName = $this->toString($data, 'packageName');
        if ($packageName === null) {
            throw new ValidationException('packageName is missing from the Google Play developer notification.');
        }

        $this->packageName = $packageName;
        $this->version     = $this->toString($data, 'version');
        $this->eventTime   = $this->toDateFromMs($data, 'eventTimeMillis') ?? CarbonImmutable::createFromTimestampMs(0)->utc();

        if (is_array($data['subscriptionNotification'] ?? null)) {
            $this->subscriptionNotification = new SubscriptionNotification($data['subscriptionNotification']);
        }

        if (is_array($data['oneTimeProductNotification'] ?? null)) {
            $this->oneTimeProductNotification = new OneTimeProductNotification($data['oneTimeProductNotification']);
        }

        if (is_array($data['voidedPurchaseNotification'] ?? null)) {
            $this->voidedPurchaseNotification = new VoidedPurchaseNotification($data['voidedPurchaseNotification']);
        }

        $this->testNotification = array_key_exists('testNotification', $data) && $data['testNotification'] !== null;

        if (
            $this->subscriptionNotification === null
            && $this->oneTimeProductNotification === null
            && $this->voidedPurchaseNotification === null
            && !$this->testNotification
        ) {
            throw new ValidationException('Google Play developer notification does not contain a recognised payload.');
        }
    }

    /**
     * Build a notification from a Pub/Sub push request body.
     *
     * @param array<string, mixed> $envelope The decoded JSON body: `{"message": {"data": "<base64>", ...}, "subscription": "..."}`.
     *
     * @throws ValidationException
     */
    public static function fromPubSubMessage(array $envelope): self
    {
        $message = $envelope['message'] ?? null;
        $data    = is_array($message) ? ($message['data'] ?? null) : null;

        if (!is_string($data) || $data === '') {
            throw new ValidationException('Pub/Sub message is missing message.data.');
        }

        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            throw new ValidationException('Pub/Sub message.data is not valid base64.');
        }

        try {
            $notification = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ValidationException('Pub/Sub message.data is not valid JSON: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($notification)) {
            throw new ValidationException('Pub/Sub message.data did not decode to a JSON object.');
        }

        return new self($notification);
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    public function getEventTime(): CarbonInterface
    {
        return $this->eventTime;
    }

    public function getSubscriptionNotification(): ?SubscriptionNotification
    {
        return $this->subscriptionNotification;
    }

    public function getOneTimeProductNotification(): ?OneTimeProductNotification
    {
        return $this->oneTimeProductNotification;
    }

    public function getVoidedPurchaseNotification(): ?VoidedPurchaseNotification
    {
        return $this->voidedPurchaseNotification;
    }

    public function isTestNotification(): bool
    {
        return $this->testNotification;
    }

    public function isSubscriptionNotification(): bool
    {
        return $this->subscriptionNotification !== null;
    }

    public function isOneTimeProductNotification(): bool
    {
        return $this->oneTimeProductNotification !== null;
    }

    public function isVoidedPurchaseNotification(): bool
    {
        return $this->voidedPurchaseNotification !== null;
    }

    /**
     * The purchase token referenced by whichever payload is present, or null for a
     * test notification.
     */
    public function getPurchaseToken(): ?string
    {
        $token = $this->subscriptionNotification?->getPurchaseToken()
            ?? $this->oneTimeProductNotification?->getPurchaseToken()
            ?? $this->voidedPurchaseNotification?->getPurchaseToken();

        return $token === '' ? null : $token;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }
}
