<?php

namespace ReceiptValidator\Amazon;

use Carbon\Carbon;
use ReceiptValidator\RunTimeException;

class PurchaseItem
{
    /**
     * purchase item info.
     *
     * @var array|null
     */
    protected $raw_data;

    /**
     * quantity.
     *
     * @var int
     */
    protected $quantity;

    /**
     * product_id.
     *
     * @var string
     */
    protected $product_id;

    /**
     * transaction_id.
     *
     * @var string
     */
    protected $transaction_id;

    /**
     * purchase_date.
     *
     * @var Carbon
     */
    protected $purchase_date;

    /**
     * cancellation_date.
     *
     * @var Carbon|null
     */
    protected $cancellation_date;

    /**
     * renewal_date.
     *
     * @var Carbon|null
     */
    protected $renewal_date;

    /**
     * @return array|null
     */
    public function getRawResponse(): ?array
    {
        return $this->raw_data;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return string
     */
    public function getProductId(): string
    {
        return $this->product_id;
    }

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transaction_id;
    }

    /**
     * @return Carbon
     */
    public function getPurchaseDate(): Carbon
    {
        return $this->purchase_date;
    }

    /**
     * @return Carbon|null
     */
    public function getCancellationDate(): ?Carbon
    {
        return $this->cancellation_date;
    }

    /**
     * @return Carbon|null
     */
    public function getRenewalDate(): ?Carbon
    {
        return $this->renewal_date;
    }

    /**
     * PurchaseItem constructor.
     *
     * @param array|null $jsonResponse
     *
     * @throws RunTimeException
     */
    public function __construct($jsonResponse = null)
    {
        $this->raw_data = $jsonResponse;
        if ($this->raw_data !== null) {
            $this->parseJsonResponse();
        }
    }

    /**
     * Parse JSON Response.
     *
     * @throws RunTimeException
     *
     * @return PurchaseItem
     */
    public function parseJsonResponse(): self
    {
        $jsonResponse = $this->raw_data;
        if (!is_array($jsonResponse)) {
            throw new RuntimeException('Response must be a scalar value');
        }

        if (array_key_exists('quantity', $jsonResponse)) {
            $this->quantity = $jsonResponse['quantity'];
        }

        if (array_key_exists('receiptId', $jsonResponse)) {
            $this->transaction_id = $jsonResponse['receiptId'];
        }

        if (array_key_exists('productId', $jsonResponse)) {
            $this->product_id = $jsonResponse['productId'];
        }

        if (array_key_exists('purchaseDate', $jsonResponse) && !empty($jsonResponse['purchaseDate'])) {
            $this->purchase_date = Carbon::createFromTimestampUTC(intval(round($jsonResponse['purchaseDate'] / 1000)));
        }

        if (array_key_exists('cancelDate', $jsonResponse) && !empty($jsonResponse['cancelDate'])) {
            $this->cancellation_date = Carbon::createFromTimestampUTC(
                intval(round($jsonResponse['cancelDate'] / 1000))
            );
        }

        if (array_key_exists('renewalDate', $jsonResponse) && !empty($jsonResponse['renewalDate'])) {
            $this->renewal_date = Carbon::createFromTimestampUTC(intval(round($jsonResponse['renewalDate'] / 1000)));
        }

        return $this;
    }
}
