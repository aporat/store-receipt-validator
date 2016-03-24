<?php
use ReceiptValidator\iTunes\Response;

/**
 * @group library
 */
class iTunesResponseTest extends PHPUnit_Framework_TestCase
{

  public function testInvalidOptionsToConstructor()
  {
    $this->setExpectedException("ReceiptValidator\\RuntimeException", "Response must be a scalar value");

    new Response('invalid');
  }

  public function testInvalidReceipt()
  {
    $response = new Response(array('status' => 21002, 'receipt' => array()));

    $this->assertFalse($response->isValid(), 'receipt must be invalid');
    $this->assertEquals(21002, $response->getResultCode(), 'receipt result code must match');
  }

  public function testReceiptSentToWrongEndpoint()
  {
    $response = new Response(array('status' => 21007));

    $this->assertFalse($response->isValid(), 'receipt must be invalid');
    $this->assertEquals(21007, $response->getResultCode(), 'receipt result code must match');
  }

  public function testValidReceipt()
  {
    $response = new Response(array('status' => 0, 'receipt' => array()));

    $this->assertTrue($response->isValid(), 'receipt must be valid');
    $this->assertEquals(0, $response->getResultCode(), 'receipt result code must match');
  }

  public function testReceiptWithLatestReceiptInfo()
  {
    $jsonResponseString = file_get_contents(__DIR__ . '/fixtures/inAppPurchaseResponse.json');
    $jsonResponseArray = json_decode($jsonResponseString, true);

    $response = new Response($jsonResponseArray);

    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $response->getLatestReceiptInfo());
    $this->assertEquals($jsonResponseArray['latest_receipt_info'], $response->getLatestReceiptInfo(), 'latest receipt info must match');

    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $response->getLatestReceipt());
    $this->assertEquals($jsonResponseArray['latest_receipt'], $response->getLatestReceipt(), 'latest receipt must match');

    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $response->getBundleId());
    $this->assertEquals($jsonResponseArray['receipt']['bundle_id'], $response->getBundleId(), 'receipt bundle id must match');
  }
}
