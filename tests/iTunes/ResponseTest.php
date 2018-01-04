<?php

use ReceiptValidator\iTunes\Response;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Constraint\IsType;

/**
 * @group library
 */
class iTunesResponseTest extends TestCase
{

  public function testInvalidOptionsToConstructor()
  {
    $this->expectException("ReceiptValidator\\RuntimeException");
    $this->expectExceptionMessage("Response must be a scalar value");

    new Response('invalid');
  }

  public function testInvalidReceipt()
  {
    $response = new Response(array('status' => Response::RESULT_DATA_MALFORMED, 'receipt' => array()));

    $this->assertFalse($response->isValid(), 'receipt must be invalid');
    $this->assertEquals(Response::RESULT_DATA_MALFORMED, $response->getResultCode(), 'receipt result code must match');
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

    $this->assertInternalType(IsType::TYPE_ARRAY, $response->getLatestReceiptInfo());
    $this->assertEquals($jsonResponseArray['latest_receipt_info'], $response->getLatestReceiptInfo(), 'latest receipt info must match');

    $this->assertInternalType(IsType::TYPE_STRING, $response->getLatestReceipt());
    $this->assertEquals($jsonResponseArray['latest_receipt'], $response->getLatestReceipt(), 'latest receipt must match');

    $this->assertInternalType(IsType::TYPE_STRING, $response->getBundleId());
    $this->assertEquals($jsonResponseArray['receipt']['bundle_id'], $response->getBundleId(), 'receipt bundle id must match');

    $this->assertInternalType(IsType::TYPE_ARRAY, $response->getPendingRenewalInfo());
    $this->assertEquals($jsonResponseArray['pending_renewal_info'], $response->getPendingRenewalInfo(), 'pending renewal info must match');
  }
}
