<?php

namespace ReceiptValidator;

use ReceiptValidator\Exceptions\ValidationException;

abstract class AbstractTransaction
{
    /**
     * Constructor.
     *
     * @param array|null $data
     *
     * @throws ValidationException
     */
    public function __construct(?array $data = [])
    {
        $this->raw_data = $data;

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
     * Raw JSON data from the response.
     *
     * @var array|null
     */
    protected ?array $raw_data = null;

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
    protected string $product_id;

    /**
     * Transaction ID.
     *
     * @var string
     */
    protected string $transaction_id;

    /**
     * Get raw response data.
     */
    public function getRawData(): ?array
    {
        return $this->raw_data;
    }

    /**
     * Get quantity.
     *
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Set quantity.
     *
     * @param int $quantity
     * @return $this
     */
    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * Get product ID.
     *
     * @return string
     */
    public function getProductId(): string
    {
        return $this->product_id;
    }

    /**
     * Set product ID.
     *
     * @param string $product_id
     * @return $this
     */
    public function setProductId(string $product_id): self
    {
        $this->product_id = $product_id;
        return $this;
    }

    /**
     * Get transaction ID.
     *
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transaction_id;
    }

    /**
     * Set transaction ID.
     *
     * @param string $transaction_id
     * @return $this
     */
    public function setTransactionId(string $transaction_id): self
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }
}
