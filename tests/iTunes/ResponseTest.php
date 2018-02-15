<?php

use PHPUnit\Framework\Error\Notice;
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
    $this->assertContainsOnly('ReceiptValidator\iTunes\PurchaseItem', $response->getLatestReceiptInfo());

    // Check sorting correctly
    $this->assertEquals(2, count($response->getLatestReceiptInfo()));
    $firstItem = $response->getLatestReceiptInfo()[0];
    $this->assertEquals(1000000093384828, $firstItem->getTransactionId());

    $this->assertInternalType(IsType::TYPE_STRING, $response->getLatestReceipt());
    $this->assertEquals($jsonResponseArray['latest_receipt'], $response->getLatestReceipt(), 'latest receipt must match');

    $this->assertInternalType(IsType::TYPE_STRING, $response->getBundleId());
    $this->assertEquals($jsonResponseArray['receipt']['bundle_id'], $response->getBundleId(), 'receipt bundle id must match');

    $this->assertInternalType(IsType::TYPE_ARRAY, $response->getPendingRenewalInfo());
    $this->assertContainsOnly('ReceiptValidator\iTunes\PendingRenewalInfo', $response->getPendingRenewalInfo());
  }

  // For backwards compatability
  public function testPurchaseItemBehavesLikeArray()
  {
    $jsonResponseString = file_get_contents(__DIR__ . '/fixtures/inAppPurchaseResponse.json');
    $jsonResponseArray = json_decode($jsonResponseString, true);
    $response = new Response($jsonResponseArray);
    $firstItem = $response->getLatestReceiptInfo()[0];
    $this->assertNotNull($firstItem);

    // Get existing
    $this->assertEquals(1396071456569, $firstItem['purchase_date_ms']);
    $this->assertEquals('2014-03-29 05:37:36 Etc/GMT', $firstItem['purchase_date']);

    // Get non-existing
    $this->expectException(Notice::class);
    $temp = $firstItem['undefined_value'];

    // Set existing
    $firstItem['transaction_id'] = 999;
    $this->assertEquals(999, $firstItem['transaction_id']);
    $this->assertEquals(999, $firstItem->getTransactionId());

    // Set new
    $firstItem['undefined_value'] = 'test';
    $this->assertEquals('test', $firstItem['undefined_value']);

    // Exists
    $this->assertEquals(true, $firstItem['transaction_id']);
    $this->assertEquals(false, $firstItem['another_undefined_value']);

    // Unset
    $firstItem['unset_test'] = 'tmp';
    $this->assertEquals('tmp', $firstItem['unset_test']);
    unset($firstItem['unset_test']);
    $this->expectException(Notice::class);
    $firstItem['unset_test'];
  }
}
