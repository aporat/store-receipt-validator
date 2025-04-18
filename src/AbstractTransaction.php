<?php

namespace ReceiptValidator;

abstract class AbstractTransaction
{
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
