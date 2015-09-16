<?php
namespace ReceiptValidator\Amazon;

use ReceiptValidator\RunTimeException;

class Response
{

    /**
     * Response Codes
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
     * Result Code
     *
     * @var int
     */
    protected $_code;


    /**
     * receipt info
     *
     * @var array
     */
    protected $_receipt = array();



    /**
     * Constructor
     *
     * @param int $httpStatusCode
     * @param array $jsonResponse
     * @return Response
     */
    public function __construct($httpStatusCode = 200, $jsonResponse = null)
    {
        $this->_code = $httpStatusCode;

        if ($jsonResponse !== null) {
            $this->parseJsonResponse($jsonResponse);
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
     * Get receipt info
     *
     * @return array
     */
    public function getReceipt()
    {
        return $this->_receipt;
    }

    /**
     * returns if the receipt is valid or not
     *
     * @return boolean
     */
    public function isValid()
    {
        if ($this->_code == self::RESULT_OK) {
            return true;
        }

        return false;
    }

    /**
     * Parse JSON Response
     *
     * @param string $jsonResponse
     * @return Message
     */
    public function parseJsonResponse($jsonResponse = null)
    {
        if (!is_array($jsonResponse)) {
            throw new RuntimeException('Response must be a scalar value');
        }

        $this->_receipt = $jsonResponse;

        return $this;
    }
}
