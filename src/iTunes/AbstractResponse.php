<?php

namespace ReceiptValidator\iTunes;

use ReceiptValidator\RunTimeException;
use Carbon\Carbon;

abstract class AbstractResponse
{
    /**
     * Result Code
     *
     * @var int
     */
    protected $result_code;

    /**
     * bundle_id (app) belongs to the receipt
     *
     * @var string
     */
    protected $bundle_id;

    /**
     * item id
     *
     * @var string
     */
    protected $app_item_id;

    /**
     * original_purchase_date
     *
     * @var Carbon|null
     */
    protected $original_purchase_date;

    /**
     * request date
     *
     * @var Carbon|null
     */
    protected $request_date;

    /**
     * The date when the app receipt was created
     *
     * @var Carbon|null
     */
    protected $receipt_creation_date;

    /**
     * receipt info
     *
     * @var array
     */
    protected $receipt = [];

    /**
     * latest receipt
     *
     * @var string
     */
    protected $latest_receipt;

    /**
     * latest receipt info (for auto-renewable subscriptions)
     *
     * @var PurchaseItem[]
     */
    protected $latest_receipt_info = [];

    /**
     * purchases info
     * @var PurchaseItem[]
     */
    protected $purchases = [];

    /**
     * pending renewal info
     * @var PendingRenewalInfo[]
     */
    protected $pending_renewal_info = [];

    /**
     * entire response of receipt
     * @var ?array
     */
    protected $raw_data;

    /**
     * Retry validation for this receipt. Only applicable to status codes 21100-21199
     *
     * @var boolean
     */
    protected $is_retryable = false;

    /**
     * Response constructor.
     * @param array|null $data
     * @throws RunTimeException
     */
    public function __construct(?array $data = null)
    {
        $this->raw_data = $data;
        $this->parseData();
    }

    /**
     * Get Result Code
     *
     * @return int
     */
    public function getResultCode(): int
    {
        return $this->result_code;
    }

    /**
     * Set Result Code
     *
     * @param int $code
     * @return self
     */
    public function setResultCode(int $code): void
    {
        $this->result_code = $code;
    }

    /**
     * Get purchases info
     *
     * @return PurchaseItem[]
     */
    public function getPurchases()
    {
        return $this->purchases;
    }

    /**
     * Get receipt info
     *
     * @return array
     */
    public function getReceipt(): array
    {
        return $this->receipt;
    }

    /**
     * Get latest receipt info
     *
     * @return PurchaseItem[]
     */
    public function getLatestReceiptInfo()
    {
        return $this->latest_receipt_info;
    }

    /**
     * Get latest receipt
     *
     * @return null|string
     */
    public function getLatestReceipt(): ?string
    {
        return $this->latest_receipt;
    }

    /**
     * Get the bundle id associated with the receipt
     *
     * @return string
     */
    public function getBundleId(): string
    {
        return $this->bundle_id;
    }

    /**
     * A string that the App Store uses to uniquely identify the application that created the transaction.
     *
     * @return string
     */
    public function getAppItemId(): string
    {
        return $this->app_item_id;
    }

    /**
     * @return Carbon|null
     */
    public function getOriginalPurchaseDate(): ?Carbon
    {
        return $this->original_purchase_date;
    }

    /**
     * @return Carbon|null
     */
    public function getRequestDate(): ?Carbon
    {
        return $this->request_date;
    }

    /**
     * @return Carbon|null
     */
    public function getReceiptCreationDate(): ?Carbon
    {
        return $this->receipt_creation_date;
    }

    /**
     * Get the pending renewal info
     *
     * @return PendingRenewalInfo[]
     */
    public function getPendingRenewalInfo()
    {
        return $this->pending_renewal_info;
    }

    /**
     * Get the raw data
     *
     * @return array
     */
    public function getRawData(): ?array
    {
        return $this->raw_data;
    }

    /**
     * returns if the receipt is valid or not
     *
     * @return boolean
     */
    public function isValid(): bool
    {
        return $this->result_code === ResponseInterface::RESULT_OK
            || $this->result_code === ResponseInterface::RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED;
    }

    /**
     * Returns retry status or not
     *
     * @return boolean
     */
    public function isRetryable(): bool
    {
        return $this->is_retryable;
    }

    /**
     * Parse Data from JSON Response
     *
     * @throws RunTimeException
     * @return $this
     */
    public function parseData(): self
    {
        if (!is_array($this->raw_data)) {
            throw new RuntimeException('Response must be an array');
        }

        if (!array_key_exists('status', $this->raw_data)) {
            $this->result_code = ResponseInterface::RESULT_DATA_MALFORMED;

            return $this;
        }

        $this->result_code = $this->raw_data['status'];

        // ios > 7 receipt validation
        if ($this->isIOS7StyleReceipt()) {
            $this->parseIOS7StyleReceipt();
        } elseif ($this->isIOS6StyleReceipt()) {
            $this->parseIOS6StyleReceipt();
        }

        return $this;
    }

    protected function isIOS7StyleReceipt(): bool
    {
        return array_key_exists('receipt', $this->raw_data)
            && is_array($this->raw_data['receipt'])
            && array_key_exists('in_app', $this->raw_data['receipt'])
            && is_array($this->raw_data['receipt']['in_app']);
    }

    protected function isIOS6StyleReceipt(): bool
    {
        return !$this->isIOS7StyleReceipt() && array_key_exists('receipt', $this->raw_data);
    }

    /**
     * Collect data for iOS >= 7.0 receipt
     * @throws RunTimeException
     */
    protected function parseIOS7StyleReceipt(): void
    {
        $this->receipt = $this->raw_data['receipt'];
        $this->app_item_id = $this->raw_data['receipt']['app_item_id'];
        $this->purchases = [];

        if (array_key_exists('original_purchase_date_ms', $this->raw_data['receipt'])) {
            $this->original_purchase_date = Carbon::createFromTimestampUTC(
                (int)round($this->raw_data['receipt']['original_purchase_date_ms'] / 1000)
            );
        }

        if (array_key_exists('request_date_ms', $this->raw_data['receipt'])) {
            $this->request_date = Carbon::createFromTimestampUTC(
                (int)round($this->raw_data['receipt']['request_date_ms'] / 1000)
            );
        }

        if (array_key_exists('receipt_creation_date_ms', $this->raw_data['receipt'])) {
            $this->receipt_creation_date = Carbon::createFromTimestampUTC(
                (int)round($this->raw_data['receipt']['receipt_creation_date_ms'] / 1000)
            );
        }

        foreach ($this->raw_data['receipt']['in_app'] as $purchase_item_data) {
            $this->purchases[] = new PurchaseItem($purchase_item_data);
        }

        if (array_key_exists('bundle_id', $this->raw_data['receipt'])) {
            $this->bundle_id = $this->raw_data['receipt']['bundle_id'];
        }

        if (array_key_exists('latest_receipt_info', $this->raw_data)) {

            $this->latest_receipt_info = array_map(
                function ($data) {
                    return new PurchaseItem($data);
                },
                $this->raw_data['latest_receipt_info']
            );

            usort(
                $this->latest_receipt_info,
                function (PurchaseItem $a, PurchaseItem $b) {
                    return $b->getPurchaseDate()->timestamp - $a->getPurchaseDate()->timestamp;
                }
            );
        }

        if (array_key_exists('latest_receipt', $this->raw_data)) {
            $this->latest_receipt = $this->raw_data['latest_receipt'];
        }

        if (array_key_exists('pending_renewal_info', $this->raw_data)) {
            $this->pending_renewal_info = array_map(
                function ($data) {
                    return new PendingRenewalInfo($data);
                },
                $this->raw_data['pending_renewal_info']
            );
        }

        if (array_key_exists('is-retryable', $this->raw_data)) {
            $this->is_retryable = true;
        }
    }

    /**
     * Collect data for iOS <= 6.0 receipt
     * @throws RunTimeException
     */
    protected function parseIOS6StyleReceipt(): void
    {
        $this->receipt = $this->raw_data['receipt'];
        $this->purchases = [];
        $this->purchases[] = new PurchaseItem($this->raw_data['receipt']);

        if (array_key_exists('bid', $this->raw_data['receipt'])) {
            $this->bundle_id = $this->raw_data['receipt']['bid'];
        }
    }
}
