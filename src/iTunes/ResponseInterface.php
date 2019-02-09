<?php

namespace ReceiptValidator\iTunes;

use Carbon\Carbon;

interface ResponseInterface extends EnvironmentResponseInterface
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
     * @var int
     * This receipt could not be authorized. Treat this the same as if a purchase was never made.
     */
    const RESULT_RECEIPT_WITHOUT_PURCHASE = 21010;

    public function getResultCode(): int;

    public function setResultCode(int $code): void;

    /**
     * @return PurchaseItem[]
     */
    public function getPurchases();

    public function getReceipt(): array;

    /**
     * @return PurchaseItem[]
     */
    public function getLatestReceiptInfo();

    public function getLatestReceipt(): ?string;

    public function getBundleId(): string;

    public function getAppItemId(): string;

    public function getOriginalPurchaseDate(): ?Carbon;

    public function getRequestDate(): ?Carbon;

    public function getReceiptCreationDate(): ?Carbon;

    /**
     * @return PendingRenewalInfo[]
     */
    public function getPendingRenewalInfo();

    public function getRawData(): ?array;

    public function isValid(): bool;

    public function isRetryable(): bool;

    public function parseData();
}
