<?php

namespace ReceiptValidator\Amazon;

use ArrayAccess;
use Carbon\Carbon;
use ReceiptValidator\AbstractTransaction;
use ReceiptValidator\Exceptions\ValidationException;
use ReturnTypeWillChange;

class Transaction extends AbstractTransaction implements ArrayAccess
{

    /**
     * Purchase date.
     *
     * @var Carbon
     */
    protected Carbon $purchase_date;

    /**
     * Cancellation date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $cancellation_date = null;

    /**
     * Renewal date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $renewal_date = null;

    /**
     * Grace period end date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $grace_period_end_date = null;

    /**
     * Free trial end date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $free_trial_end_date = null;

    /**
     * Auto renewing status.
     *
     * @var bool|null
     */
    protected ?bool $auto_renewing = null;

    /**
     * Term duration of the subscription.
     *
     * @var string|null
     */
    protected ?string $term = null;

    /**
     * Term SKU of the subscription.
     *
     * @var string|null
     */
    protected ?string $term_sku = null;


    /**
     * Parse JSON response.
     *
     * @return $this
     * @throws ValidationException
     */
    public function parse(): self
    {
        if (!is_array($this->raw_data)) {
            throw new ValidationException('Response must be a scalar value');
        }

        $data = $this->raw_data;

        $this->setQuantity(isset($data['quantity']) ? (int)$data['quantity'] : 0);
        $this->setTransactionId($data['receiptId'] ?? '');
        $this->setProductId($data['productId'] ?? '');

        if (!empty($data['purchaseDate'])) {
            $this->purchase_date = Carbon::createFromTimestampUTC((int)round($data['purchaseDate'] / 1000));
        }

        if (!empty($data['cancelDate'])) {
            $this->cancellation_date = Carbon::createFromTimestampUTC((int)round($data['cancelDate'] / 1000));
        }

        if (!empty($data['renewalDate'])) {
            $this->renewal_date = Carbon::createFromTimestampUTC((int)round($data['renewalDate'] / 1000));
        }

        if (!empty($data['GracePeriodEndDate'])) {
            $this->grace_period_end_date = Carbon::createFromTimestampUTC((int)round($data['GracePeriodEndDate'] / 1000));
        }

        if (!empty($data['freeTrialEndDate'])) {
            $this->free_trial_end_date = Carbon::createFromTimestampUTC((int)round($data['freeTrialEndDate'] / 1000));
        }

        if (isset($data['AutoRenewing'])) {
            $this->auto_renewing = (bool)$data['AutoRenewing'];
        }

        if (isset($data['term'])) {
            $this->term = $data['term'];
        }

        if (isset($data['termSku'])) {
            $this->term_sku = $data['termSku'];
        }

        return $this;
    }

    /**
     * Get raw data.
     *
     * @return array|null
     */
    public function getRawData(): ?array
    {
        return $this->raw_data;
    }

    /**
     * Get purchase date.
     *
     * @return Carbon
     */
    public function getPurchaseDate(): Carbon
    {
        return $this->purchase_date;
    }

    /**
     * Get cancellation date.
     *
     * @return Carbon|null
     */
    public function getCancellationDate(): ?Carbon
    {
        return $this->cancellation_date;
    }

    /**
     * Get grace period end date.
     *
     * @return Carbon|null
     */
    public function getGracePeriodEndDate(): ?Carbon
    {
        return $this->grace_period_end_date;
    }

    /**
     * Get free trial end date.
     *
     * @return Carbon|null
     */
    public function getFreeTrialEndDate(): ?Carbon
    {
        return $this->free_trial_end_date;
    }

    /**
     * Get auto renewing status.
     *
     * @return bool|null
     */
    public function isAutoRenewing(): ?bool
    {
        return $this->auto_renewing;
    }

    /**
     * Get the term of the subscription.
     *
     * @return string|null
     */
    public function getTerm(): ?string
    {
        return $this->term;
    }

    /**
     * Get the term SKU of the subscription.
     *
     * @return string|null
     */
    public function getTermSku(): ?string
    {
        return $this->term_sku;
    }

    /**
     * Get renewal date.
     *
     * @return Carbon|null
     */
    public function getRenewalDate(): ?Carbon
    {
        return $this->renewal_date;
    }

    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        $this->raw_data[$offset] = $value;
        $this->parse();
    }

    #[ReturnTypeWillChange]
    public function offsetGet($offset): mixed
    {
        return $this->raw_data[$offset] ?? null;
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        unset($this->raw_data[$offset]);
    }

    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return isset($this->raw_data[$offset]);
    }
}
