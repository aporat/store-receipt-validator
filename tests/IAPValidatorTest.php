<?php
use IAPValidator\IAPValidator;

/**
 *
 * @group library
 */
class IAPValidatorTest extends PHPUnit_Framework_TestCase
{
    
    public function setUp()
    {
        parent::setUp();
    
        $this->validator = new IAPValidator(IAPValidator::ENVIRONMENT_SANDBOX_URL);
    }
    
    public function testInvalidOptionsToConstructor()
    {
        $this->setExpectedException(
                "IAPValidator\\RuntimeException", "Invalid environment url 'in-valid'"
        );
        
        $validator = new IAPValidator('in-valid');
    }
}
