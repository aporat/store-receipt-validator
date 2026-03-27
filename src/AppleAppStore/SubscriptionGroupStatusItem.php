<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

/**
 * Represents a subscription group and all of its most recent transaction/renewal
 * entries for a given customer.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/subscriptiongroupidentifieritem
 */
final readonly class SubscriptionGroupStatusItem
{
    /** The identifier for the subscription group. */
    public string $subscriptionGroupIdentifier;

    /**
     * The most recent transactions for each product in the subscription group.
     *
     * @var list<LastTransactionItem>
     */
    public array $lastTransactions;

    /**
     * @param array<string, mixed> $data Raw group data from the API response.
     */
    public function __construct(array $data)
    {
        $this->subscriptionGroupIdentifier = (string) ($data['subscriptionGroupIdentifier'] ?? '');

        $raw = is_array($data['lastTransactions'] ?? null) ? $data['lastTransactions'] : [];

        $this->lastTransactions = array_values(
            array_map(
                static fn(array $item) => new LastTransactionItem($item),
                $raw
            )
        );
    }

    public function getSubscriptionGroupIdentifier(): string
    {
        return $this->subscriptionGroupIdentifier;
    }

    /**
     * @return list<LastTransactionItem>
     */
    public function getLastTransactions(): array
    {
        return $this->lastTransactions;
    }
}
