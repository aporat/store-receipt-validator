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
    $response = new Response(array('status' => Response::RESULT_DATA_MALFORMED, 'receipt' => array()));

    $this->assertInstanceOf('ReceiptValidator\SubscriptionInterface', $response);
    $this->assertFalse($response->isValid(), 'receipt must be invalid');
    $this->assertEquals(Response::RESULT_DATA_MALFORMED, $response->getResultCode(), 'receipt result code must match');

      $response = new Response(array('status' => Response::RESULT_OK));

      $this->assertFalse($response->isValid(), 'receipt must be invalid');
  }

  public function testReceiptSentToWrongEndpoint()
  {
    $response = new Response(array('status' => Response::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION));

    $this->assertFalse($response->isValid(), 'receipt must be invalid');
    $this->assertEquals(Response::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION, $response->getResultCode(), 'receipt result code must match');
  }

  public function testValidReceipt()
  {
    $response = new Response(array('status' => Response::RESULT_OK, 'receipt' => array('testValue')));

    $this->assertTrue($response->isValid(), 'receipt must be valid');
    $this->assertEquals(Response::RESULT_OK, $response->getResultCode(), 'receipt result code must match');
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
    $this->assertEquals($jsonResponseArray['receipt']['app_item_id'], $response->getAppItemId(), 'receipt app item id must match');
    $this->assertEquals(end($jsonResponseArray['latest_receipt_info'])['transaction_id'], $response->getTransactionId(), 'receipt transaction id must match');
    $this->assertEquals(end($jsonResponseArray['latest_receipt_info'])['original_transaction_id'], $response->getOriginalTransactionId(), 'receipt original transaction id must match');
    $this->assertEquals(end($jsonResponseArray['latest_receipt_info'])['product_id'], $response->getProductId(), 'receipt product id must match');
    $this->assertEquals(end($jsonResponseArray['latest_receipt_info'])['expires_date_ms'], $response->getExpiresDate(), 'receipt expires date must match');
    $this->assertEquals($jsonResponseArray, $response->getRawResponse(), 'original receipt');
  }
}
