<?php
namespace ReceiptValidator\Amazon;

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
   * purchase_date
   *
   * @var Carbon
   */
  protected $_purchase_date;

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
   * @return Carbon
   */
  public function getPurchaseDate()
  {
    return $this->_purchase_date;
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

    if (array_key_exists('receiptId', $jsonResponse)) {
      $this->_transaction_id = $jsonResponse['receiptId'];
    }

    if (array_key_exists('productId', $jsonResponse)) {
      $this->_product_id = $jsonResponse['productId'];
    }

    if (array_key_exists('purchaseDate', $jsonResponse) && !empty($jsonResponse['purchaseDate'])) {
      $this->_purchase_date = Carbon::createFromTimestampUTC(round($jsonResponse['purchaseDate'] / 1000));
    }

    if (array_key_exists('cancelDate', $jsonResponse) && !empty($jsonResponse['cancelDate'])) {
      $this->_cancellation_date = Carbon::createFromTimestampUTC(round($jsonResponse['cancelDate'] / 1000));
    }

    return $this;
  }
}
