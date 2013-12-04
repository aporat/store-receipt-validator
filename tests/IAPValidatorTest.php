<?php
use IAPValidator\IAPValidator;

/**
 * @group library
 */
class IAPValidatorTest extends PHPUnit_Framework_TestCase
{

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
}
