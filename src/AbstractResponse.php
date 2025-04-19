<?php

namespace ReceiptValidator;

use ReceiptValidator\Exceptions\ValidationException;

abstract class AbstractResponse
{
    /**
     * Purchases array.
     *
     * @var array<AbstractTransaction>
     */
    protected array $transactions = [];

    /**
     * Raw JSON data from the response.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $rawData = null;

    /**
     * Environment in which validation was performed.
     *
     * @var Environment
     */
    protected Environment $environment;

    /**
     * Constructor.
     *
     * @param array<string, mixed>|null $data
     * @param Environment $environment
     *
     * @throws ValidationException
     */
    public function __construct(?array $data = [], Environment $environment = Environment::PRODUCTION)
    {
        $this->rawData = $data;
        $this->environment = $environment;

        $this->parse();
    }

    /**
     * Parse raw data into the response.
     *
     * @return $this
     * @throws ValidationException
     */
    abstract public function parse(): self;

    /**
     * Get transaction array.
     *
     * @return array<AbstractTransaction>
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * Get raw response data.
     *
     * @return array<string, mixed>|null
     */
    public function getRawData(): ?array
    {
        return $this->rawData;
    }

    /**
     * Get the environment used.
     *
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Set the environment.
     *
     * @param Environment $environment
     * @return $this
     */
    public function setEnvironment(Environment $environment): self
    {
        $this->environment = $environment;
        return $this;
    }
}
