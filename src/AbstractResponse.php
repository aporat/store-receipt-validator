<?php

declare(strict_types=1);

namespace ReceiptValidator;

use ReceiptValidator\Support\ValueCasting;

/**
 * Provides a base structure for API responses from different app stores.
 *
 * This abstract class standardizes how raw response data is parsed and accessed,
 * ensuring that all concrete response implementations offer a consistent interface.
 * Response objects are designed to be immutable after creation.
 *
 * @template T of AbstractTransaction
 */
abstract class AbstractResponse
{
    use ValueCasting;

    /**
     * A collection of transaction objects parsed from the response.
     *
     * @var array<T>
     */
    protected array $transactions = [];

    /**
     * The original, unprocessed data from the store's API response.
     *
     * @var array<string, mixed>
     */
    protected readonly array $rawData;

    /**
     * The environment in which the validation was performed.
     */
    protected readonly Environment $environment;

    /**
     * Constructs the response object.
     *
     * @param array<string, mixed> $data The raw decoded JSON data from the API response.
     * @param Environment $environment   The environment used for the validation request.
     */
    public function __construct(array $data = [], Environment $environment = Environment::PRODUCTION)
    {
        $this->rawData = $data;
        $this->environment = $environment;
    }

    /**
     * Returns the parsed transaction objects.
     *
     * @return array<T> An array of transaction objects.
     */
    final public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * Returns the original, unprocessed response data.
     *
     * @return array<string, mixed> The raw data from the API.
     */
    final public function getRawData(): array
    {
        return $this->rawData;
    }

    /**
     * Returns the environment used for the validation request.
     */
    final public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Convenience: first transaction if present.
     *
     * @return T|null
     */
    final public function getFirstTransaction(): ?AbstractTransaction
    {
        /** @var T|null */
        return $this->transactions[0] ?? null;
    }

    /**
     * Protected helper to add a single transaction during construction.
     *
     * @param T $tx
     */
    final protected function addTransaction(AbstractTransaction $tx): void
    {
        $this->transactions[] = $tx;
    }

    /**
     * Protected helper to set transactions in bulk during construction.
     *
     * @param array<T> $txs
     */
    final protected function setTransactions(array $txs): void
    {
        $this->transactions = $txs;
    }
}
