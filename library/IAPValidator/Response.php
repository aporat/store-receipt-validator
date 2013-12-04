<?php
namespace IAPValidator;

class Response
{
    /**
     * Response Codes
     *
     * @var int
     */
    const RESULT_OK = 0;
    const RESULT_APPSTORE_CANNOT_READ = 21000;
    const RESULT_DATA_MALFORMED = 21002;
    const RESULT_RECEIPT_NOT_AUTHENTICATED = 21003;
    const RESULT_SHARED_SECRET_NOT_MATCH = 21004;
    const RESULT_RECEIPT_SERVER_UNAVAILABLE = 21005;
    const RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED = 21006;
    const RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION = 21007; // special case for app review handling - forward any request that is intended for the Sandbox but was sent to Production, this is what the app review team does
    const RESULT_PRODUCTION_RECEIPT_SENT_TO_SENDBOX = 21008;
    
    /**
     * Result Code
     * @var int
     */
    protected $_code;
    
    /**
     * Constructor
     *
     * @param array $jsonResponse
     * @return Response
     */
    public function __construct($jsonResponse = null)
    {
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
     * returns if the receipt is valid or not
     * 
     * @return boolean
     */
    public function isValid()
    {
        if ($this->_code==self::RESULT_OK || $this->_code==self::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION) {
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
    public function parseJsonResponse($jsonResponse)
    {
        if (!is_array($jsonResponse)) {
            throw new RuntimeException('Response must be a scalar value');
        }
        
        $this->_code = $jsonResponse['status'];
    
        return $this;
    }
}