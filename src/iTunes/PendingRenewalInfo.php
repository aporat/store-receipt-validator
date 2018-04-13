<?php
namespace ReceiptValidator\iTunes;

use ArrayAccess;

class PendingRenewalInfo implements ArrayAccess
{
  /*!
   * Developer friendly field codes
   * @link https://developer.apple.com/library/content/releasenotes/General/ValidateAppStoreReceipt/Chapters/ReceiptFields.html
   */

  // Expiration Intent Codes //
  /* @var int Customer Cancelled */
  const EXPIRATION_INTENT_CANCELLED = 1;

  /* @var int Billing Error */
  const EXPIRATION_INTENT_BILLING_ERROR = 2;

  /* @var int Recent price increase was declined */
  const EXPIRATION_INTENT_INCREASE_DECLINED = 3;

  /* @var int Product unavailable at time of renewal */
  const EXPIRATION_INTENT_PRODUCT_UNAVAILABLE = 4;

  /* @var int Unknown */
  const EXPIRATION_INTENT_UNKNOWN = 5;


  // Retry flag codes //
  /* @var int Still attempting renewal */
  const RETRY_PERIOD_ACTIVE = 1;

  /* @var int Stopped attempting renewal */
  const RETRY_PERIOD_INACTIVE = 0;


  // Auto renew status codes //
  /* @var int Subscription will renew */
  const AUTO_RENEW_ACTIVE = 1;

  /* @var int Customer has turned off renewal */
  const AUTO_RENEW_INACTIVE = 0;

  /**#@+
   * Computed status code
   * @var string
   */
  const STATUS_ACTIVE  = 'active';
  const STATUS_PENDING = 'pending';
  const STATUS_EXPIRED = 'expired';
  /**#@-*/

  /**
   * Pending renewal info
   * @var array
   */
  protected $_raw = [];

  /**
   * Product ID
   * @var string|null
   */
  protected $_product_id;

  /**
   * Auto Renew Product ID
   * @var string|null
   */
  protected $_auto_renew_product_id;

  /**
   * Original Transation ID
   * @var string|null
   */
  protected $_original_transaction_id;

  /**
   * Expiration Intent Code
   * @var int|null
   */
  protected $_expiration_intent;

  /**
   * Is In Billing Retry Period Code
   * @var int|null
   */
  protected $_is_in_billing_retry_period;

  /**
   * Auto Renew Status Code
   * @var int|null
   */
  protected $_auto_renew_status;

  public function __construct(array $rawData)
  {
    $this->_raw = $rawData;
    $this->hydrateFromRawData();
  }

  /**
   * Hydrate the model from the provided data
   *
   * @return PendingRenewalInfo
   */
  protected function hydrateFromRawData() : self
  {
    // Always available
    $this->_product_id = $this->_raw['product_id'] ?? null;
    $this->_auto_renew_product_id = $this->_raw['auto_renew_product_id'] ?? null;
    $this->_auto_renew_status = isset($this->_raw['auto_renew_status']) ? (int) $this->_raw['auto_renew_status'] : null;

    // Also always available but not in existing fixture, so will assume optional for backwards compatibility
    $this->_original_transaction_id = $this->_raw['original_transaction_id'] ?? null;

    // Optionals
    $this->_expiration_intent = isset($this->_raw['expiration_intent']) ? (int) $this->_raw['expiration_intent'] : null;
    $this->_is_in_billing_retry_period = isset($this->_raw['is_in_billing_retry_period']) ? (int) $this->_raw['is_in_billing_retry_period'] : null;

    return $this;
  }

  /*****************************************
   * GETTERS
   *****************************************/

  /**
   * Product ID
   * @return string|null
   */
  public function getProductId()
  {
    return $this->_product_id;
  }

  /**
   * Auto Renew Product ID
   * @return string|null
   */
  public function getAutoRenewProductId()
  {
    return $this->_auto_renew_product_id;
  }

  /**
   * Auto Renew Status Code
   * @return int|null
   */
  public function getAutoRenewStatus()
  {
    return $this->_auto_renew_status;
  }

  /**
   * Original Transaction ID
   * @return string|null
   */
  public function getOriginalTransactionId()
  {
    return $this->_original_transaction_id;
  }

  /**
   * Expiration Intent Code
   * @return int|null
   */
  public function getExpirationIntent()
  {
    return $this->_expiration_intent;
  }

  /**
   * Is In Billing Retry Period Code
   * @return int|null
   */
  public function getIsInBillingRetryPeriod()
  {
    return $this->_is_in_billing_retry_period;
  }

  /*****************************************
   * Convenience methods
   *****************************************/

  /**
   * Status of Pending Renewal
   *
   * This is a computed property that assumes a particular status based on
   * contextual information.
   *
   * @return string|null
   */
  public function getStatus()
  {
    // Active when no expiration intent
    if (null === $this->_expiration_intent) {
      return $this::STATUS_ACTIVE;
    }

    // Pending when retrying
    if ($this::RETRY_PERIOD_ACTIVE === $this->_is_in_billing_retry_period) {
      return $this::STATUS_PENDING;
    }

    // Expired when not retrying
    if ($this::RETRY_PERIOD_INACTIVE === $this->_is_in_billing_retry_period) {
      return $this::STATUS_EXPIRED;
    }

    return null;
  }

  /**
   * Update a key and reprocess object properties
   *
   * @param $key
   * @param $value
   *
   * @throws RunTimeException
   */
  public function offsetSet($key, $value)
  {
    $this->_raw[$key] = $value;
    $this->hydrateFromRawData();
  }

  /**
   * Get a value
   *
   * @param $key
   * @return mixed
   */
  public function offsetGet($key)
  {
    return $this->_raw[$key];
  }

  /**
   * Unset a key
   *
   * @param $key
   */
  public function offsetUnset($key)
  {
    unset($this->_raw[$key]);
  }

  /**
   * Check if key exists
   *
   * @param $key
   * @return bool
   */
  public function offsetExists($key)
  {
    return isset($this->_raw[$key]);
  }
}
