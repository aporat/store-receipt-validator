<?php
use ReceiptValidator\iTunes\Response;

/**
 * @group library
 */
class ResponseTest extends PHPUnit_Framework_TestCase
{
    
    public function testInvalidOptionsToConstructor()
    {
        $this->setExpectedException("ReceiptValidator\\RuntimeException", "Response must be a scalar value");
        
        $response = new Response('invalid');
    }
    
    public function testInvalidReceipt()
    {
        $response = new Response(array('status' => 21002, 'receipt' => []));
        
        $this->assertFalse($response->isValid(), 'receipt must be invalid');
    }
    
    public function testValidReceipt()
    {
        $response = new Response(array('status' => 0, 'receipt' => []));
    
        $this->assertTrue($response->isValid(), 'receipt must be valid');
    }
    
}