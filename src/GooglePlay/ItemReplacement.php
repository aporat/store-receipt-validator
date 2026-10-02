<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\Support\ValueCasting;

/**
 * The product a line item was replaced from, or is being replaced to (`itemReplacement`).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#ItemReplacement
 */
final readonly class ItemReplacement
{
    use ValueCasting;

    public ?string $productId;

    public ReplacementMode $replacementMode;

    public ?string $basePlanId;

    public ?string $offerId;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->productId       = $this->toString($data, 'productId');
        $this->replacementMode = ReplacementMode::fromString($this->toString($data, 'replacementMode'));
        $this->basePlanId      = $this->toString($data, 'basePlanId');
        $this->offerId         = $this->toString($data, 'offerId');
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function getReplacementMode(): ReplacementMode
    {
        return $this->replacementMode;
    }

    public function getBasePlanId(): ?string
    {
        return $this->basePlanId;
    }

    public function getOfferId(): ?string
    {
        return $this->offerId;
    }
}
