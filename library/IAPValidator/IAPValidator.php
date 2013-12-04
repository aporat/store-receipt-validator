<?php
namespace IAPValidator;

class IAPValidator {

    const ENVIRONMENT_SANDBOX_URL = 'https://sandbox.itunes.apple.com/verifyReceipt';
    const ENVIRONMENT_PRODUCTION_URL = 'https://buy.itunes.apple.com/verifyReceipt';
    
    
    /**
     * environment url
     * @var string
     */
    protected $_environmentUrl;

    public function __construct($environmentUrl = ENVIRONMENT_PRODUCTION_URL)
    {
        if ($environmentUrl != self::ENVIRONMENT_PRODUCTION_URL && $environmentUrl != self::ENVIRONMENT_SANDBOX_URL) {
            throw new RunTimeException(
                "Invalid environment url '{$environmentUrl}'"
            );
        }
        
        $this->_environmentUrl = $environmentUrl;
    }
}
