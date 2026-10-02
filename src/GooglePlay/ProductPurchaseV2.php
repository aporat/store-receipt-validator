<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractResponse;
use ReceiptValidator\Environment;

/**
 * A one-time product purchase as returned by `purchases.productsv2.getproductpurchasev2`.
 *
 * Unlike the v1 `ProductPurchase`, a v2 purchase is looked up by token alone and can
 * carry several line items (multi-quantity, purchase options, rentals, pre-orders).
 * Line items are exposed as the response's transactions. Google has no sandbox
 * endpoint; the environment is derived from the `testPurchaseContext` marker.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.productsv2#ProductPurchaseV2
 *
 * @extends AbstractResponse<ProductLineItem>
 */
final class ProductPurchaseV2 extends AbstractResponse
{
    /** Resource kind, always "androidpublisher#productPurchaseV2". */
    public readonly ?string $kind;

    /** The purchase token this purchase was looked up with. */
    public readonly ?string $purchaseToken;

    /** The order ID associated with the purchase. */
    public readonly ?string $orderId;

    /** The purchase state, or null if Google sent an unknown value. */
    public readonly ?ProductPurchaseState $purchaseState;

    /** Whether the purchase has been acknowledged. */
    public readonly AcknowledgementState $acknowledgementState;

    /** True for purchases made by a licence-testing account. */
    public readonly bool $testPurchase;

    /** The obfuscated account ID set via `setObfuscatedAccountId()` in the billing client. */
    public readonly ?string $obfuscatedExternalAccountId;

    /** The obfuscated profile ID set via `setObfuscatedProfileId()` in the billing client. */
    public readonly ?string $obfuscatedExternalProfileId;

    /** ISO 3166-1 alpha-2 billing country of the user. */
    public readonly ?string $regionCode;

    /** When the purchase state became PURCHASED. Absent while pending. */
    public readonly ?CarbonImmutable $purchaseCompletionTime;

    /**
     * @param array<string, mixed> $data          The decoded `ProductPurchaseV2` JSON.
     * @param string|null          $purchaseToken The token used for the lookup; Google does not echo it.
     */
    public function __construct(array $data = [], ?string $purchaseToken = null)
    {
        $isTest = array_key_exists('testPurchaseContext', $data) && $data['testPurchaseContext'] !== null;

        parent::__construct($data, $isTest ? Environment::SANDBOX : Environment::PRODUCTION);

        $this->kind          = $this->toString($data, 'kind');
        $this->purchaseToken = $purchaseToken;
        $this->orderId       = $this->toString($data, 'orderId');
        $this->testPurchase  = $isTest;

        $stateContext        = is_array($data['purchaseStateContext'] ?? null) ? $data['purchaseStateContext'] : [];
        $this->purchaseState = ProductPurchaseState::fromV2String($this->toString($stateContext, 'purchaseState'));

        $this->acknowledgementState        = AcknowledgementState::fromString($this->toString($data, 'acknowledgementState') ?? '');
        $this->obfuscatedExternalAccountId = $this->toString($data, 'obfuscatedExternalAccountId');
        $this->obfuscatedExternalProfileId = $this->toString($data, 'obfuscatedExternalProfileId');
        $this->regionCode                  = $this->toString($data, 'regionCode');
        $this->purchaseCompletionTime      = $this->toDateFromRfc3339($data, 'purchaseCompletionTime');

        $items = [];
        foreach (is_array($data['productLineItem'] ?? null) ? $data['productLineItem'] : [] as $item) {
            if (is_array($item)) {
                $items[] = new ProductLineItem($item, $this->orderId);
            }
        }
        $this->setTransactions($items);
    }

    public function getKind(): ?string
    {
        return $this->kind;
    }

    public function getPurchaseToken(): ?string
    {
        return $this->purchaseToken;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getPurchaseState(): ?ProductPurchaseState
    {
        return $this->purchaseState;
    }

    public function isPurchased(): bool
    {
        return $this->purchaseState === ProductPurchaseState::PURCHASED;
    }

    public function isPending(): bool
    {
        return $this->purchaseState === ProductPurchaseState::PENDING;
    }

    public function isCanceled(): bool
    {
        return $this->purchaseState === ProductPurchaseState::CANCELED;
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

    public function getObfuscatedExternalAccountId(): ?string
    {
        return $this->obfuscatedExternalAccountId;
    }

    public function getObfuscatedExternalProfileId(): ?string
    {
        return $this->obfuscatedExternalProfileId;
    }

    public function getRegionCode(): ?string
    {
        return $this->regionCode;
    }

    public function getPurchaseCompletionTime(): ?CarbonInterface
    {
        return $this->purchaseCompletionTime;
    }

    /**
     * The line items of this purchase. Alias of {@see getTransactions()}.
     *
     * @return array<ProductLineItem>
     */
    public function getLineItems(): array
    {
        return $this->getTransactions();
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
     * Whether every line item has been consumed. False when there are no line items.
     */
    public function isConsumed(): bool
    {
        $items = $this->getTransactions();
        if ($items === []) {
            return false;
        }

        foreach ($items as $item) {
            if (!$item->isConsumed()) {
                return false;
            }
        }

        return true;
    }
}
