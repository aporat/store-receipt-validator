<?php
namespace ReceiptValidator\iTunes;

use ReceiptValidator\RunTimeException;
use Carbon\Carbon;

class PurchaseItem
{

  /**
   * purchase item info
   *
   * @var array
   */
  protected $_response;

  /**
   * quantity
   *
   * @var int
   */
  protected $_quantity;

  /**
   * product_id
   *
   * @var string
   */
  protected $_product_id;

  /**
   * web_order_line_item_id
   *
   * @var string
   */
  protected $_web_order_line_item_id;

  /**
   * transaction_id
   *
   * @var string
   */
  protected $_transaction_id;

  /**
   * original_transaction_id
   *
   * @var string
   */
  protected $_original_transaction_id;

  /**
   * purchase_date
   *
   * @var Carbon
   */
  protected $_purchase_date;

  /**
   * original_purchase_date
   *
   * @var Carbon
   */
  protected $_original_purchase_date;

  /**
   * expires_date
   *
   * @var Carbon
   */
  protected $_expires_date;

  /**
   * cancellation_date
   *
   * @var Carbon
   */
  protected $_cancellation_date;

  /**
   * @return array
   */
  public function getRawResponse()
  {
    return $this->_response;
  }

  /**
   * @return int
   */
  public function getQuantity()
  {
    return $this->_quantity;
  }

  /**
   * @return string
   */
  public function getProductId()
  {
    return $this->_product_id;
  }

  /**
   * @return string
   */
  public function getWebOrderLineItemId()
  {
    return $this->_web_order_line_item_id;
  }

  /**
   * @return string
   */
  public function getTransactionId()
  {
    return $this->_transaction_id;
  }

  /**
   * @return string
   */
  public function getOriginalTransactionId()
  {
    return $this->_original_transaction_id;
  }

  /**
   * @return Carbon
   */
  public function getPurchaseDate()
  {
    return $this->_purchase_date;
  }

  /**
   * @return Carbon
   */
  public function getOriginalPurchaseDate()
  {
    return $this->_original_purchase_date;
  }

  /**
   * @return Carbon
   */
  public function getExpiresDate()
  {
    return $this->_expires_date;
  }

  /**
   * @return Carbon
   */
  public function getCancellationDate()
  {
    return $this->_cancellation_date;
  }

  /**
   * Constructor
   *
   * @param array $jsonResponse
   */
  public function __construct($jsonResponse = null)
  {
    $this->_response = $jsonResponse;
    if ($this->_response !== null) {
      $this->parseJsonResponse();
    }
  }

  /**
   * Parse JSON Response
   *
   * @return PurchaseItem
   * @throws RunTimeException
   */
  public function parseJsonResponse()
  {
    $jsonResponse = $this->_response;
    if (!is_array($jsonResponse)) {
      throw new RuntimeException('Response must be a scalar value');
    }

    if (array_key_exists('quantity', $jsonResponse)) {
      $this->_quantity = $jsonResponse['quantity'];
    }

    if (array_key_exists('transaction_id', $jsonResponse)) {
      $this->_transaction_id = $jsonResponse['transaction_id'];
    }

    if (array_key_exists('original_transaction_id', $jsonResponse)) {
      $this->_original_transaction_id = $jsonResponse['original_transaction_id'];
    }

    if (array_key_exists('product_id', $jsonResponse)) {
      $this->_product_id = $jsonResponse['product_id'];
    }

    if (array_key_exists('web_order_line_item_id', $jsonResponse)) {
      $this->_web_order_line_item_id = $jsonResponse['web_order_line_item_id'];
    }

    if (array_key_exists('purchase_date_ms', $jsonResponse)) {
      $this->_purchase_date = Carbon::createFromTimestampUTC(round($jsonResponse['purchase_date_ms'] / 1000));
    }

    if (array_key_exists('original_purchase_date_ms', $jsonResponse)) {
      $this->_original_purchase_date = Carbon::createFromTimestampUTC(round($jsonResponse['original_purchase_date_ms'] / 1000));
    }

    if (array_key_exists('expires_date_ms', $jsonResponse)) {
      $this->_expires_date = Carbon::createFromTimestampUTC(round($jsonResponse['expires_date_ms'] / 1000));
    }

    if (array_key_exists('cancellation_date_ms', $jsonResponse)) {
      $this->_cancellation_date = Carbon::createFromTimestampUTC(round($jsonResponse['cancellation_date_ms'] / 1000));
    }

    return $this;
  }
}
