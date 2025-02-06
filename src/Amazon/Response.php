<?php

namespace ReceiptValidator\Amazon;

use ReceiptValidator\RunTimeException;

class Response
{
    /**
     * Response Codes.
     *
     * @var int
     */
    const RESULT_OK = 200;

    // Amazon RVS Error: Invalid receiptID
    const RESULT_INVALID_RECEIPT = 400;

    // Amazon RVS Error: Invalid developerSecret
    const RESULT_INVALID_DEVELOPER_SECRET = 496;

    // Amazon RVS Error: Invalid userId
    const RESULT_INVALID_USER_ID = 497;

    // Amazon RVS Error: Internal Server Error
    const RESULT_INTERNAL_ERROR = 500;

    /**
     * Result Code.
     *
     * @var int
     */
    protected int $code;

    /**
     * receipt info.
     *
     * @var array
     */
    protected array $receipt = [];

    /**
     * purchases info.
     *
     * @var PurchaseItem[]
     */
    protected array $purchases = [];

    /**
     * Response constructor.
     *
     * @param int        $httpStatusCode
     * @param array|null $jsonResponse
     *
     * @throws RunTimeException
     */
    public function __construct(int $httpStatusCode = 200, ?array $jsonResponse = [])
    {
        $this->code = $httpStatusCode;

        if ($jsonResponse !== null) {
            $this->parseJsonResponse($jsonResponse);
        }
    }

    /**
     * Get Result Code.
     *
     * @return int
     */
    public function getResultCode(): int
    {
        return $this->code;
    }

    /**
     * Get receipt info.
     *
     * @return array
     */
    public function getReceipt(): array
    {
        return $this->receipt;
    }

    /**
     * Get purchases info.
     *
     * @return PurchaseItem[]
     */
    public function getPurchases(): array
    {
        return $this->purchases;
    }

    /**
     * returns if the receipt is valid or not.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->code == self::RESULT_OK) {
            return true;
        }

        return false;
    }

    /**
     * Parse JSON Response.
     *
     * @param array|null $jsonResponse
     *
     * @throws RunTimeException
     *
     * @return $this
     */
    public function parseJsonResponse(?array $jsonResponse): self
    {
        if (!is_array($jsonResponse)) {
            throw new RuntimeException('Response must be a scalar value');
        }

        $this->receipt = $jsonResponse;
        $this->purchases = [];
        $this->purchases[] = new PurchaseItem($jsonResponse);

        return $this;
    }
}
