<?php
namespace ReceiptValidator\iTunes;

use ReceiptValidator\RunTimeException;

class Response
{
  /**
   * Response Codes
   *
   * @var int
   */
  const RESULT_OK = 0;

  // The App Store could not read the JSON object you provided.
  const RESULT_APPSTORE_CANNOT_READ = 21000;

  // The data in the receipt-data property was malformed or missing.
  const RESULT_DATA_MALFORMED = 21002;

  // The receipt could not be authenticated.
  const RESULT_RECEIPT_NOT_AUTHENTICATED = 21003;

  // The shared secret you provided does not match the shared secret on file for your account.
  // Only returned for iOS 6 style transaction receipts for auto-renewable subscriptions.
  const RESULT_SHARED_SECRET_NOT_MATCH = 21004;

  // The receipt server is not currently available.
  const RESULT_RECEIPT_SERVER_UNAVAILABLE = 21005;

  // This receipt is valid but the subscription has expired. When this status code is returned to your server, the receipt data is also decoded and returned as part of the response.
  // Only returned for iOS 6 style transaction receipts for auto-renewable subscriptions.
  const RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED = 21006;

  // This receipt is from the test environment, but it was sent to the production environment for verification. Send it to the test environment instead.
  // special case for app review handling - forward any request that is intended for the Sandbox but was sent to Production, this is what the app review team does
  const RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION = 21007;

  // This receipt is from the production environment, but it was sent to the test environment for verification. Send it to the production environment instead.
  const RESULT_PRODUCTION_RECEIPT_SENT_TO_SANDBOX = 21008;

  /**
   * Result Code
   *
   * @var int
   */
  protected $_code;

  /**
   * bundle_id (app) belongs to the receipt
   *
   * @var string
   */
  protected $_bundle_id;

  /**
   * receipt info
   *
   * @var array
   */
  protected $_receipt = [];

  /**
   * latest receipt (needs for auto-renewable subscriptions)
   *
   * @var string
   */
  protected $_latest_receipt;

  /**
   * latest receipt info (needs for auto-renewable subscriptions)
   *
   * @var array
   */
  protected $_latest_receipt_info;

  /**
   * purchases info
   * @var PurchaseItem[]
   */
  protected $_purchases = [];

  /**
   * pending renewal info
   * @var string
   */
  protected $_pending_renewal_info;

  /**
   * Constructor
   *
   * @param array $jsonResponse
   */
  public function __construct($jsonResponse = null)
  {
    $this->response = $jsonResponse;
    if ($this->response !== null) {
      $this->parseJsonResponse();
    }
  }

  /**
   * Get Result Code
   *
   * @return int
   */
  public function getResultCode()
  {
    return $this->_code;
  }

  /**
   * Set Result Code
   *
   * @param int $code
   * @return Response
   */
  public function setResultCode($code)
  {
    $this->_code = $code;

    return $this;
  }

  /**
   * Get purchases info
   *
   * @return PurchaseItem[]
   */
  public function getPurchases()
  {
    return $this->_purchases;
  }

  /**
   * Get receipt info
   *
   * @return array
   */
  public function getReceipt()
  {
    return $this->_receipt;
  }

  /**
   * Get latest receipt info
   *
   * @return array
   */
  public function getLatestReceiptInfo()
  {
    return $this->_latest_receipt_info;
  }

  /**
   * Get latest receipt
   *
   * @return string
   */
  public function getLatestReceipt()
  {
    return $this->_latest_receipt;
  }

  /**
   * Get the bundle id associated with the receipt
   *
   * @return string
   */
  public function getBundleId()
  {
    return $this->_bundle_id;
  }

  /**
   * Get the pending renewal info
   *
   * @return string
   */
  public function getPendingRenewalInfo()
  {
    return $this->_pending_renewal_info;
  }

  /**
   * returns if the receipt is valid or not
   *
   * @return boolean
   */
  public function isValid()
  {
    return ($this->_code == self::RESULT_OK);
  }

  /**
   * Parse JSON Response
   *
   * @return Response
   * @throws RunTimeException
   */
  public function parseJsonResponse()
  {
    $jsonResponse = $this->response;
    if (!is_array($jsonResponse)) {
      throw new RuntimeException('Response must be a scalar value');
    }

    $iOS7plus = array_key_exists('receipt', $jsonResponse) && is_array($jsonResponse['receipt']) && array_key_exists('in_app', $jsonResponse['receipt']) && is_array($jsonResponse['receipt']['in_app']);
    $iOS6less = array_key_exists('receipt', $jsonResponse) && !$iOS7plus;

    if ($iOS7plus || $iOS6less) {
      $this->_receipt = $jsonResponse['receipt'];

      if (array_key_exists('latest_receipt_info', $jsonResponse)) {
        $this->_latest_receipt_info = $jsonResponse['latest_receipt_info'];
      }

      if (array_key_exists('latest_receipt', $jsonResponse)) {
        $this->_latest_receipt = $jsonResponse['latest_receipt'];
      }

      // ios > 7 receipt validation
      if ($iOS7plus) {
        $this->_app_item_id = $this->_receipt['app_item_id'];

        foreach ($jsonResponse['receipt']['in_app'] as $purchase_item_data) {
          $this->_purchases[] = new PurchaseItem($purchase_item_data);
        }

        if (array_key_exists('bundle_id', $jsonResponse['receipt'])) {
          $this->_bundle_id = $jsonResponse['receipt']['bundle_id'];
        }

        if (array_key_exists('pending_renewal_info', $jsonResponse)) {
          $this->_pending_renewal_info = $jsonResponse['pending_renewal_info'];
        }
        
      // ios <= 6 receipt validation
      } elseif ($iOS6less) {
        $this->_purchases[] = new PurchaseItem($jsonResponse['receipt']);

        if (array_key_exists('bid', $jsonResponse['receipt'])) {
          $this->_bundle_id = $jsonResponse['receipt']['bid'];
        }
      }
    }

    if (array_key_exists('status', $jsonResponse)) {
      $this->_code = $jsonResponse['status'];
    } else {
      $this->_code = self::RESULT_DATA_MALFORMED;
    }
    return $this;
  }
}
