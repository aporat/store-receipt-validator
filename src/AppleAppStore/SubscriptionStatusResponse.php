<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use ReceiptValidator\Environment;
use ReceiptValidator\Support\ValueCasting;

/**
 * Encapsulates the response from the Get All Subscription Statuses endpoint.
 *
 * Contains the subscription status for all of a customer's auto-renewable
 * subscriptions in your app, organised by subscription group.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/statusresponse
 */
final class SubscriptionStatusResponse
{
    use ValueCasting;

    /** The server environment in which the response was generated. */
    public readonly Environment $environment;

    /** The bundle identifier of the app. */
    public readonly ?string $bundleId;

    /** The unique identifier of the app in the App Store. */
    public readonly ?int $appAppleId;

    /**
     * An array of subscription groups, each containing the customer's most
     * recent transactions for every product in that group.
     *
     * @var list<SubscriptionGroupStatusItem>
     */
    public readonly array $data;

    /**
     * @param array<string, mixed> $data The raw decoded JSON data from the API response.
     */
    public function __construct(array $data = [])
    {
        $this->environment = $this->toEnvironment($data, 'environment');
        $this->bundleId    = $this->toString($data, 'bundleId');
        $this->appAppleId  = $this->toInt($data, 'appAppleId');

        $raw = is_array($data['data'] ?? null) ? $data['data'] : [];

        $this->data = array_values(
            array_map(
                static fn(array $group) => new SubscriptionGroupStatusItem($group),
                $raw
            )
        );
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function getBundleId(): ?string
    {
        return $this->bundleId;
    }

    public function getAppAppleId(): ?int
    {
        return $this->appAppleId;
    }

    /**
     * @return list<SubscriptionGroupStatusItem>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Convenience method: returns every LastTransactionItem across all subscription groups.
     *
     * @return list<LastTransactionItem>
     */
    public function getAllLastTransactions(): array
    {
        if ($this->data === []) {
            return [];
        }

        return array_merge(
            ...array_map(
                static fn(SubscriptionGroupStatusItem $g) => $g->lastTransactions,
                $this->data
            )
        );
    }
}
