<?php

namespace ReceiptValidator\iTunes;

use Carbon\Carbon;
use ReceiptValidator\AbstractResponse;
use ReceiptValidator\Exceptions\ValidationException;

class Response extends AbstractResponse
{
    /**
     * latest receipt.
     * @var string|null
     */
    protected ?string $latest_receipt = null;

    /**
     * latest receipt info (for auto-renewable subscriptions).
     * @var Transaction[]
     */
    protected array $latest_receipt_info = [];

    /**
     * pending renewal info.
     * @var RenewalInfo[]
     */
    protected array $pending_renewal_info = [];

    /**
     * bundle_id (app) belongs to the receipt.
     * @var string|null
     */
    protected ?string $bundle_id = null;

    /**
     * item id.
     * @var string|null
     */
    protected ?string $app_item_id = null;

    /**
     * original_purchase_date.
     * @var Carbon|null
     */
    protected ?Carbon $original_purchase_date;

    /**
     * request date.
     * @var Carbon|null
     */
    protected ?Carbon $request_date;

    /**
     * The date when the app receipt was created.
     * @var Carbon|null
     */
    protected ?Carbon $receipt_creation_date;

    /**
     * Retry validation for this receipt. Only applicable to status codes 21100-21199.
     * @var bool
     */
    protected bool $is_retryable = false;

    /**
     * Parse Data from JSON Response.
     *
     * @return $this
     * @throws ValidationException
     */
    public function parse(): self
    {
        if (!is_array($this->rawData)) {
            throw new ValidationException('Response must be an array');
        }

        if ($this->isIOS7StyleReceipt()) {
            $this->parseIOS7StyleReceipt();
        } elseif ($this->isIOS6StyleReceipt()) {
            $this->parseIOS6StyleReceipt();
        }

        return $this;
    }

    protected function isIOS7StyleReceipt(): bool
    {
        return array_key_exists('receipt', $this->rawData)
            && is_array($this->rawData['receipt'])
            && array_key_exists('in_app', $this->rawData['receipt'])
            && is_array($this->rawData['receipt']['in_app']);
    }

    /**
     * Parse receipt for iOS >= 7.
     *
     * @throws ValidationException
     */
    protected function parseIOS7StyleReceipt(): void
    {
        $this->app_item_id = $this->rawData['receipt']['app_item_id'];
        $this->transactions = [];

        if (array_key_exists('original_purchase_date_ms', $this->rawData['receipt'])) {
            $this->original_purchase_date = Carbon::createFromTimestampUTC(
                (int)round($this->rawData['receipt']['original_purchase_date_ms'] / 1000)
            );
        }

        if (array_key_exists('request_date_ms', $this->rawData['receipt'])) {
            $this->request_date = Carbon::createFromTimestampUTC(
                (int)round($this->rawData['receipt']['request_date_ms'] / 1000)
            );
        }

        if (array_key_exists('receipt_creation_date_ms', $this->rawData['receipt'])) {
            $this->receipt_creation_date = Carbon::createFromTimestampUTC(
                (int)round($this->rawData['receipt']['receipt_creation_date_ms'] / 1000)
            );
        }

        foreach ($this->rawData['receipt']['in_app'] as $purchase_item_data) {
            $this->transactions[] = new Transaction($purchase_item_data);
        }

        if (array_key_exists('bundle_id', $this->rawData['receipt'])) {
            $this->bundle_id = $this->rawData['receipt']['bundle_id'];
        }

        if (array_key_exists('latest_receipt_info', $this->rawData)) {
            $this->latest_receipt_info = array_map(
                fn($data) => new Transaction($data),
                $this->rawData['latest_receipt_info']
            );
        }

        if (array_key_exists('latest_receipt', $this->rawData)) {
            $this->latest_receipt = $this->rawData['latest_receipt'];
        }

        if (array_key_exists('pending_renewal_info', $this->rawData)) {
            $this->pending_renewal_info = array_map(
                fn($data) => new RenewalInfo($data),
                $this->rawData['pending_renewal_info']
            );
        }

        if (array_key_exists('is-retryable', $this->rawData)) {
            $this->is_retryable = true;
        }
    }

    protected function isIOS6StyleReceipt(): bool
    {
        return !$this->isIOS7StyleReceipt() && array_key_exists('receipt', $this->rawData);
    }

    /**
     * Parse receipt for iOS <= 6.
     *
     * @throws ValidationException
     */
    protected function parseIOS6StyleReceipt(): void
    {
        $this->transactions = [new Transaction($this->rawData['receipt'])];

        if (array_key_exists('bid', $this->rawData['receipt'])) {
            $this->bundle_id = $this->rawData['receipt']['bid'];
        }
    }

    /**
     * Returns retry status or not.
     *
     * @return bool
     */
    public function isRetryable(): bool
    {
        return $this->is_retryable;
    }

    /**
     * Get latest receipt.
     *
     * @return string|null
     */
    public function getLatestReceipt(): ?string
    {
        return $this->latest_receipt;
    }

    /**
     * Get latest receipt info.
     *
     * @return Transaction[]
     */
    public function getLatestReceiptInfo(): array
    {
        return $this->latest_receipt_info;
    }

    /**
     * Get pending renewal info.
     *
     * @return RenewalInfo[]
     */
    public function getPendingRenewalInfo(): array
    {
        return $this->pending_renewal_info;
    }

    /**
     * Get bundle ID.
     *
     * @return string|null
     */
    public function getBundleId(): ?string
    {
        return $this->bundle_id;
    }

    /**
     * Get app item ID.
     *
     * @return string|null
     */
    public function getAppItemId(): ?string
    {
        return $this->app_item_id;
    }

    /**
     * Get original purchase date.
     *
     * @return Carbon|null
     */
    public function getOriginalPurchaseDate(): ?Carbon
    {
        return $this->original_purchase_date;
    }

    /**
     * Get request date.
     *
     * @return Carbon|null
     */
    public function getRequestDate(): ?Carbon
    {
        return $this->request_date;
    }

    /**
     * Get receipt creation date.
     *
     * @return Carbon|null
     */
    public function getReceiptCreationDate(): ?Carbon
    {
        return $this->receipt_creation_date;
    }
}
