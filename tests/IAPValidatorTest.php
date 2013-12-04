<?php
use IAPValidator\IAPValidator;

/**
 * @group library
 */
class IAPValidatorTest extends PHPUnit_Framework_TestCase
{

    private $testReceiptData = 'AluGxOuMy+RT1gkyFCoD1i1KT3KUZl+F5FAAW0ELBlCUbC9dW14876aW0OXBlNJ6pXbBBFB8K0LDy6LuoAS8iBiq3529aRbVRUSKCPeCDZ7apC2zqFYZ4N7bSFDMeb92wzN0X/dELxlkRH4bWjO67X7gnHcN47qHoVckSlGo/mpbAAADVzCCA1MwggI7oAMCAQICCGUUkU3ZWAS1MA0GCSqGSIb3DQEBBQUAMH8xCzAJBgNVBAYTAlVTMRMwEQYDVQQKDApBcHBsZSBJbmMuMSYwJAYDVQQLDB1BcHBsZSBDZXJ0aWZpY2F0aW9uIEF1dGhvcml0eTEzMDEGA1UEAwwqQXBwbGUgaVR1bmVzIFN0b3JlIENlcnRpZmljYXRpb24gQXV0aG9yaXR5MB4XDTA5MDYxNTIyMDU1NloXDTE0MDYxNDIyMDU1NlowZDEjMCEGA1UEAwwaUHVyY2hhc2VSZWNlaXB0Q2VydGlmaWNhdGUxGzAZBgNVBAsMEkFwcGxlIGlUdW5lcyBTdG9yZTETMBEGA1UECgwKQXBwbGUgSW5jLjELMAkGA1UEBhMCVVMwgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAMrRjF2ct4IrSdiTChaI0g8pwv/cmHs8p/RwV/rt/91XKVhNl4XIBimKjQQNfgHsDs6yju++DrKJE7uKsphMddKYfFE5rGXsAdBEjBwRIxexTevx3HLEFGAt1moKx509dhxtiIdDgJv2YaVs49B0uJvNdy6SMqNNLHsDLzDS9oZHAgMBAAGjcjBwMAwGA1UdEwEB/wQCMAAwHwYDVR0jBBgwFoAUNh3o4p2C0gEYtTJrDtdDC5FYQzowDgYDVR0PAQH/BAQDAgeAMB0GA1UdDgQWBBSpg4PyGUjFPhJXCBTMzaN+mV8k9TAQBgoqhkiG92NkBgUBBAIFADANBgkqhkiG9w0BAQUFAAOCAQEAEaSbPjtmN4C/IB3QEpK32RxacCDXdVXAeVReS5FaZxc+t88pQP93BiAxvdW/3eTSMGY5FbeAYL3etqP5gm8wrFojX0ikyVRStQ+/AQ0KEjtqB07kLs9QUe8czR8UGfdM1EumV/UgvDd4NwNYxLQMg4WTQfgkQQVy8GXZwVHgbE/UC6Y7053pGXBk51NPM3woxhd3gSRLvXj+loHsStcTEqe9pBDpmG5+sk4tw+GK3GMeEN5/+e1QT9np/Kl1nj+aBw7C0xsy0bFnaAd1cSS6xdory/CUvM6gtKsmnOOdqTesbp0bs8sn6Wqs0C9dgcxRHuOMZ2tm8npLUm7argOSzQ==';
    
    private $validator;
    
    public function setUp()
    {
        parent::setUp();
        
        $this->validator = new IAPValidator(IAPValidator::ENDPOINT_SANDBOX);
    }

    public function testInvalidOptionsToConstructor()
    {
        $this->setExpectedException("IAPValidator\\RuntimeException", "Invalid endpoint 'in-valid'");
        
        $validator = new IAPValidator('in-valid');
    }
    
    
    public function testSetEndpoint()
    {
        $this->validator->setEndpoint(IAPValidator::ENDPOINT_PRODUCTION);
        
        $this->assertEquals(IAPValidator::ENDPOINT_PRODUCTION, $this->validator->getEndpoint());
    }
    
    public function testSetReceiptData()
    {
        $this->validator->setReceiptData('test-data');
    
        $this->assertEquals('test-data', $this->validator->getReceiptData());
    }
    
    /*
    public function testValidate()
    {
        $this->validator->setReceiptData($this->testReceiptData)->validate();
    
    }*/
}
