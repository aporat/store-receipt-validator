<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractResponse;
use ReceiptValidator\Environment;

/**
 * A subscription purchase as returned by `purchases.subscriptionsv2.get`.
 *
 * Line items are exposed as the response's transactions. Google has no sandbox
 * endpoint; the environment is derived from the `testPurchase` marker, which is
 * present for licence-tester purchases.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#SubscriptionPurchaseV2
 *
 * @extends AbstractResponse<SubscriptionLineItem>
 */
final class SubscriptionPurchase extends AbstractResponse
{
    /** Resource kind, always "androidpublisher#subscriptionPurchaseV2". */
    public readonly ?string $kind;

    /** ISO 3166-1 alpha-2 billing country of the user. */
    public readonly ?string $regionCode;

    /** The order ID of the latest order associated with the purchase. */
    public readonly ?string $latestOrderId;

    /** When the subscription was first granted. */
    public readonly ?CarbonImmutable $startTime;

    /** The current state of the subscription. */
    public readonly SubscriptionState $subscriptionState;

    /** The token of the purchase this one upgraded, downgraded or re-signed-up from. */
    public readonly ?string $linkedPurchaseToken;

    /** Whether the purchase has been acknowledged. */
    public readonly AcknowledgementState $acknowledgementState;

    /** True for purchases made by a licence-testing account. */
    public readonly bool $testPurchase;

    /** The developer-supplied account identifier (`externalAccountIdentifiers.externalAccountId`). */
    public readonly ?string $externalAccountId;

    /** The obfuscated account ID set via `setObfuscatedAccountId()` in the billing client. */
    public readonly ?string $obfuscatedExternalAccountId;

    /** The obfuscated profile ID set via `setObfuscatedProfileId()` in the billing client. */
    public readonly ?string $obfuscatedExternalProfileId;

    /** For paused subscriptions, when Play will automatically resume it. */
    public readonly ?CarbonImmutable $autoResumeTime;

    /**
     * Cancellation details when the state is CANCELED (raw `canceledStateContext`).
     *
     * @var array<string, mixed>|null
     */
    public readonly ?array $canceledStateContext;

    /**
     * Subscriber details for Subscribe with Google purchases (raw `subscribeWithGoogleInfo`).
     *
     * @var array<string, mixed>|null
     */
    public readonly ?array $subscribeWithGoogleInfo;

    /**
     * @param array<string, mixed> $data The decoded `SubscriptionPurchaseV2` JSON.
     */
    public function __construct(array $data = [])
    {
        $isTest = array_key_exists('testPurchase', $data) && $data['testPurchase'] !== null;

        parent::__construct($data, $isTest ? Environment::SANDBOX : Environment::PRODUCTION);

        $this->kind                = $this->toString($data, 'kind');
        $this->regionCode          = $this->toString($data, 'regionCode');
        $this->latestOrderId       = $this->toString($data, 'latestOrderId');
        $this->startTime           = $this->toDateFromRfc3339($data, 'startTime');
        $this->subscriptionState   = SubscriptionState::fromString($this->toString($data, 'subscriptionState') ?? '');
        $this->linkedPurchaseToken = $this->toString($data, 'linkedPurchaseToken');
        $this->acknowledgementState = AcknowledgementState::fromString($this->toString($data, 'acknowledgementState') ?? '');
        $this->testPurchase        = $isTest;

        $identifiers = is_array($data['externalAccountIdentifiers'] ?? null) ? $data['externalAccountIdentifiers'] : [];
        $this->externalAccountId           = $this->toString($identifiers, 'externalAccountId');
        $this->obfuscatedExternalAccountId = $this->toString($identifiers, 'obfuscatedExternalAccountId');
        $this->obfuscatedExternalProfileId = $this->toString($identifiers, 'obfuscatedExternalProfileId');

        $paused               = is_array($data['pausedStateContext'] ?? null) ? $data['pausedStateContext'] : [];
        $this->autoResumeTime = $this->toDateFromRfc3339($paused, 'autoResumeTime');

        $this->canceledStateContext    = is_array($data['canceledStateContext'] ?? null) ? $data['canceledStateContext'] : null;
        $this->subscribeWithGoogleInfo = is_array($data['subscribeWithGoogleInfo'] ?? null) ? $data['subscribeWithGoogleInfo'] : null;

        $items = [];
        foreach (is_array($data['lineItems'] ?? null) ? $data['lineItems'] : [] as $item) {
            if (is_array($item)) {
                $items[] = new SubscriptionLineItem($item);
            }
        }
        $this->setTransactions($items);
    }

    public function getKind(): ?string
    {
        return $this->kind;
    }

    public function getRegionCode(): ?string
    {
        return $this->regionCode;
    }

    public function getLatestOrderId(): ?string
    {
        return $this->latestOrderId;
    }

    public function getStartTime(): ?CarbonInterface
    {
        return $this->startTime;
    }

    public function getSubscriptionState(): SubscriptionState
    {
        return $this->subscriptionState;
    }

    public function getLinkedPurchaseToken(): ?string
    {
        return $this->linkedPurchaseToken;
    }

    public function getAcknowledgementState(): AcknowledgementState
    {
        return $this->acknowledgementState;
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledgementState === AcknowledgementState::ACKNOWLEDGED;
    }

    public function isTestPurchase(): bool
    {
        return $this->testPurchase;
    }

    public function getExternalAccountId(): ?string
    {
        return $this->externalAccountId;
    }

    public function getObfuscatedExternalAccountId(): ?string
    {
        return $this->obfuscatedExternalAccountId;
    }

    public function getObfuscatedExternalProfileId(): ?string
    {
        return $this->obfuscatedExternalProfileId;
    }

    public function getAutoResumeTime(): ?CarbonInterface
    {
        return $this->autoResumeTime;
    }

    /** @return array<string, mixed>|null */
    public function getCanceledStateContext(): ?array
    {
        return $this->canceledStateContext;
    }

    /** @return array<string, mixed>|null */
    public function getSubscribeWithGoogleInfo(): ?array
    {
        return $this->subscribeWithGoogleInfo;
    }

    /**
     * The line items of this purchase. Alias of {@see getTransactions()}.
     *
     * @return array<SubscriptionLineItem>
     */
    public function getLineItems(): array
    {
        return $this->getTransactions();
    }

    /**
     * The line item with the latest expiry, or null if there are none.
     */
    public function getLatestLineItem(): ?SubscriptionLineItem
    {
        $latest = null;

        foreach ($this->getTransactions() as $item) {
            if ($item->expiryTime === null) {
                continue;
            }

            if ($latest === null || $latest->expiryTime === null || $item->expiryTime->greaterThan($latest->expiryTime)) {
                $latest = $item;
            }
        }

        return $latest ?? ($this->getTransactions()[0] ?? null);
    }

    /**
     * The furthest expiry across all line items.
     */
    public function getExpiryTime(): ?CarbonInterface
    {
        return $this->getLatestLineItem()?->getExpiryTime();
    }

    /**
     * Product IDs of all line items.
     *
     * @return array<int, string>
     */
    public function getProductIds(): array
    {
        $ids = [];
        foreach ($this->getTransactions() as $item) {
            if ($item->getProductId() !== null) {
                $ids[] = $item->getProductId();
            }
        }

        return $ids;
    }

    /**
     * Whether Play considers the user entitled right now.
     *
     * Combines {@see SubscriptionState::isEntitled()} with the expiry time, so a
     * canceled-but-unexpired subscription counts and a stale ACTIVE payload does not.
     */
    public function isEntitled(?CarbonInterface $now = null): bool
    {
        if (!$this->subscriptionState->isEntitled()) {
            return false;
        }

        $expiry = $this->getExpiryTime();

        return $expiry === null || $expiry->greaterThan($now ?? CarbonImmutable::now());
    }

    /**
     * Whether any line item will auto-renew.
     */
    public function isAutoRenewing(): bool
    {
        foreach ($this->getTransactions() as $item) {
            if ($item->isAutoRenewEnabled()) {
                return true;
            }
        }

        return false;
    }
}
