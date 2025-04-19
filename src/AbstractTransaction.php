<?php

namespace ReceiptValidator;

use ReceiptValidator\Exceptions\ValidationException;

abstract class AbstractTransaction
{
    /**
     * Raw JSON data from the response.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $rawData = null;

    /**
     * Quantity.
     *
     * @var int
     */
    protected int $quantity = 0;

    /**
     * Product ID.
     *
     * @var string
     */
    protected string $productId;

    /**
     * Transaction ID.
     *
     * @var string
     */
    protected string $transactionId;

    /**
     * Constructor.
     *
     * @param array<string, mixed>|null $data
     *
     * @throws ValidationException
     */
    public function __construct(?array $data = [])
    {
        $this->rawData = $data;

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
     * Get raw response data.
     *
     * @return array<string, mixed>|null
     */
    public function getRawData(): ?array
    {
        return $this->rawData;
    }

    /**
     * Get quantity.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Set quantity.
     */
    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * Get product ID.
     */
    public function getProductId(): string
    {
        return $this->productId;
    }

    /**
     * Set product ID.
     */
    public function setProductId(string $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    /**
     * Get transaction ID.
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * Set transaction ID.
     */
    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }
}
