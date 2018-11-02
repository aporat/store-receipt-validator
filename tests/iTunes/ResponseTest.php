<?php

use ReceiptValidator\iTunes\Response;
use PHPUnit\Framework\TestCase;

/**
 * @group library
 */
class iTunesResponseTest extends TestCase
{

  public function testInvalidOptionsToConstructor(): void
  {
    $this->expectException(\ReceiptValidator\RunTimeException::class);

    new Response(null);
  }

  public function testInvalidReceipt(): void
  {
    $response = new Response(['status' => Response::RESULT_DATA_MALFORMED, 'receipt' => []]);

    $this->assertFalse(
      $response->isValid(),
      'receipt must be invalid'
    );

    $this->assertEquals(
      Response::RESULT_DATA_MALFORMED,
      $response->getResultCode(),
      'receipt result code must match'
    );
  }

  public function testReceiptSentToWrongEndpoint(): void
  {
    $response = new Response(['status' => Response::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION]);

    $this->assertFalse(
      $response->isValid(),
      'receipt must be invalid'
    );

    $this->assertEquals(
      Response::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION,
      $response->getResultCode(),
      'receipt result code must match'
    );
  }

  public function testValidReceipt(): void
  {
    $response = new Response(array('status' => Response::RESULT_OK, 'receipt' => array('testValue')));

    $this->assertTrue($response->isValid(), 'receipt must be valid');
    $this->assertEquals(Response::RESULT_OK, $response->getResultCode(), 'receipt result code must match');
  }

  public function testReceiptWithLatestReceiptInfo(): void
  {
    $jsonResponseString = file_get_contents(__DIR__ . '/fixtures/inAppPurchaseResponse.json');
    $jsonResponseArray = json_decode($jsonResponseString, true);

    $response = new Response($jsonResponseArray);

    $this->assertEquals(
      Response::RESULT_OK,
      $response->getResultCode()
    );

    $this->assertContainsOnlyInstancesOf(
      ReceiptValidator\iTunes\PurchaseItem::class,
      $response->getLatestReceiptInfo()
    );

    $this->assertCount(
      2,
      $response->getLatestReceiptInfo()
    );

    $this->assertEquals(
      $jsonResponseArray['latest_receipt'],
      $response->getLatestReceipt(),
      'latest receipt must match'
    );

    $this->assertEquals(
      $jsonResponseArray['receipt']['bundle_id'],
      $response->getBundleId(),
      'receipt bundle id must match'
    );

    $this->assertEquals(
      '2013-08-01T07:00:00+00:00',
      $response->getOriginalPurchaseDate()->toIso8601String()
    );

    $this->assertEquals(
      '2013-08-01T07:00:00+00:00',
      $response->getReceiptCreationDate()->toIso8601String()
    );

    $this->assertEquals(
      '2015-05-24T16:31:18+00:00',
      $response->getRequestDate()->toIso8601String()
    );

    $this->assertContainsOnlyInstancesOf(
      ReceiptValidator\iTunes\PendingRenewalInfo::class,
      $response->getPendingRenewalInfo()
    );

    $this->assertEquals(
      11202513425662,
      $response->getAppItemId()
    );

    $this->assertEquals(
      1000000093384828,
      $response->getLatestReceiptInfo()[0]->getTransactionId()
    );

    $this->assertEquals(
      $jsonResponseArray,
      $response->getRawData()
    );

  }
}
