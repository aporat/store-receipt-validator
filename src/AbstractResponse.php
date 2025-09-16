<?php

namespace ReceiptValidator;

use ReceiptValidator\Exceptions\ValidationException;

/**
 * Provides a base structure for API responses from different app stores.
 *
 * This abstract class standardizes how raw response data is parsed and accessed,
 * ensuring that all concrete response implementations offer a consistent interface.
 * Response objects are designed to be immutable after creation.
 */
abstract class AbstractResponse
{
    /**
     * A collection of transaction objects parsed from the response.
     *
     * @var array<AbstractTransaction>
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
     * Constructs the response object and triggers parsing.
     *
     * @param array<string, mixed> $data The raw decoded JSON data from the API response.
     * @param Environment $environment The environment used for the validation request.
     *
     * @throws ValidationException If parsing the raw data fails.
     */
    public function __construct(array $data = [], Environment $environment = Environment::PRODUCTION)
    {
        $this->rawData = $data;
        $this->environment = $environment;
    }

    /**
     * Returns the parsed transaction objects.
     *
     * @return array<AbstractTransaction> An array of transaction objects.
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * Returns the original, unprocessed response data.
     *
     * @return array<string, mixed> The raw data from the API.
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    /**
     * Returns the environment used for the validation request.
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }
}
