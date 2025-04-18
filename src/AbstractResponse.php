<?php

namespace ReceiptValidator;

use ReceiptValidator\Exceptions\ValidationException;

abstract class AbstractResponse
{

    /**
     * Purchases array.
     *
     * @var array
     */
    protected array $transactions = [];

    /**
     * Raw JSON data from the response.
     *
     * @var array|null
     */
    protected ?array $raw_data = null;

    /**
     * Environment in which validation was performed.
     *
     * @var Environment
     */
    protected Environment $environment;

    /**
     * Constructor.
     *
     * @param array|null $data
     * @param Environment $environment
     *
     * @throws ValidationException
     */
    public function __construct(?array $data = [], Environment $environment = Environment::PRODUCTION)
    {
        $this->raw_data = $data;
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
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * Get raw response data.
     */
    public function getRawData(): ?array
    {
        return $this->raw_data;
    }

    /**
     * Get the environment used.
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Set the environment.
     */
    public function setEnvironment(Environment $environment): self
    {
        $this->environment = $environment;
        return $this;
    }
}
