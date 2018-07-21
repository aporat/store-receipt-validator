<?php
namespace ReceiptValidator\iTunes;

use ReceiptValidator\RunTimeException;
use Carbon\Carbon;

class Response
{

  /* @var int
   * receipt response is valid
   */
  const RESULT_OK = 0;

  /* @var int
   * The App Store could not read the JSON object you provided.
   */
  const RESULT_APPSTORE_CANNOT_READ = 21000;

  /* @var int
   * The data in the receipt-data property was malformed or missing.
   */
  const RESULT_DATA_MALFORMED = 21002;

  /* @var int
   * The receipt could not be authenticated.
   */
  const RESULT_RECEIPT_NOT_AUTHENTICATED = 21003;

  /* @var int
   * The shared secret you provided does not match the shared secret on file for your account.
   * Only returned for iOS 6 style transaction receipts for auto-renewable subscriptions.
   */
  const RESULT_SHARED_SECRET_NOT_MATCH = 21004;

  /* @var int
   * The receipt server is not currently available.
   */
  const RESULT_RECEIPT_SERVER_UNAVAILABLE = 21005;

  /* @var int
   * This receipt is valid but the subscription has expired. When this status code is returned to your server,
   * the receipt data is also decoded and returned as part of the response.
   * Only returned for iOS 6 style transaction receipts for auto-renewable subscriptions.
   */
  const RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED = 21006;

  /* @var int
   * This receipt is from the test environment, but it was sent to the production environment for verification.
   * Send it to the test environment instead.
   * special case for app review handling - forward any request that is intended for the Sandbox but was sent to Production,
   * this is what the app review team does
   */
  const RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION = 21007;

  /* @var int
   * This receipt is from the production environment, but it was sent to the test environment for verification.
   * Send it to the production environment instead.
   */
  const RESULT_PRODUCTION_RECEIPT_SENT_TO_SANDBOX = 21008;

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
  protected $raw_data = null;

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
  public function setResultCode(int $code): self
  {
    $this->result_code = $code;

    return $this;
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
   * @return string
   */
  public function getLatestReceipt(): string
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
   * returns if the receipt is valid or not
   *
   * @return boolean
   */
  public function isValid(): bool
  {
    return ($this->result_code == self::RESULT_OK);
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
      throw new RuntimeException('Response must be a scalar value');
    }

    // ios > 7 receipt validation
    if (array_key_exists('receipt', $this->raw_data) && is_array($this->raw_data['receipt']) && array_key_exists('in_app', $this->raw_data['receipt']) && is_array($this->raw_data['receipt']['in_app'])) {
      $this->result_code = $this->raw_data['status'];
      $this->receipt = $this->raw_data['receipt'];
      $this->app_item_id = $this->raw_data['receipt']['app_item_id'];
      $this->purchases = [];

      if (array_key_exists('original_purchase_date_ms', $this->raw_data['receipt'])) {
        $this->original_purchase_date = Carbon::createFromTimestampUTC(intval(round($this->raw_data['receipt']['original_purchase_date_ms'] / 1000)));
      }

      if (array_key_exists('request_date_ms', $this->raw_data['receipt'])) {
        $this->request_date = Carbon::createFromTimestampUTC(intval(round($this->raw_data['receipt']['request_date_ms'] / 1000)));
      }

      if (array_key_exists('receipt_creation_date_ms', $this->raw_data['receipt'])) {
        $this->receipt_creation_date = Carbon::createFromTimestampUTC(intval(round($this->raw_data['receipt']['receipt_creation_date_ms'] / 1000)));
      }

      foreach ($this->raw_data['receipt']['in_app'] as $purchase_item_data) {
        $this->raw_data[] = new PurchaseItem($purchase_item_data);
      }

      if (array_key_exists('bundle_id', $this->raw_data['receipt'])) {
        $this->bundle_id = $this->raw_data['receipt']['bundle_id'];
      }

      if (array_key_exists('latest_receipt_info', $this->raw_data)) {

        $this->latest_receipt_info = array_map(function ($data) {
          return new PurchaseItem($data);
        }, $this->raw_data['latest_receipt_info']);

        usort($this->latest_receipt_info, function (PurchaseItem $a, PurchaseItem $b) {
          return $b->getPurchaseDate()->timestamp - $a->getPurchaseDate()->timestamp;
        });
      }

      if (array_key_exists('latest_receipt', $this->raw_data)) {
        $this->latest_receipt = $this->raw_data['latest_receipt'];
      }

      if (array_key_exists('pending_renewal_info', $this->raw_data)) {
        $this->pending_renewal_info = array_map(function ($data) {
            return new PendingRenewalInfo($data);
        }, $this->raw_data['pending_renewal_info']);
      }
    } elseif (array_key_exists('receipt', $this->raw_data)) {

      // ios <= 6.0 validation
      $this->result_code = $this->raw_data['status'];

      if (array_key_exists('receipt', $this->raw_data)) {
        $this->receipt = $this->raw_data['receipt'];
        $this->purchases = [];
        $this->purchases[] = new PurchaseItem($this->raw_data['receipt']);

        if (array_key_exists('bid', $this->raw_data['receipt'])) {
          $this->bundle_id = $this->raw_data['receipt']['bid'];
        }
      }
    } elseif (array_key_exists('status', $this->raw_data)) {
      $this->result_code = $this->raw_data['status'];
    } else {
      $this->result_code = self::RESULT_DATA_MALFORMED;
    }

    return $this;
  }
}
