<?php

use ReceiptValidator\Amazon\Response;
use PHPUnit\Framework\TestCase;

/**
 * @group library
 */
class AmazonResponseTest extends TestCase
{

  public function testInvalidOptionsToConstructor()
  {
    $this->expectException("ReceiptValidator\\RuntimeException");
    $this->expectExceptionMessage("Response must be a scalar value");

    new Response(Response::RESULT_OK, 'invalid');
  }

  public function testInvalidReceipt()
  {
    $response = new Response(Response::RESULT_INTERNAL_ERROR, array(''));

    $this->assertFalse($response->isValid(), 'receipt must be invalid');
  }

   public function testValidReceipt()
   {
     $receipt = json_decode('{"betaProduct":false,"cancelDate":null,"parentProductId":null,"productId":"pack_100","productType":"CONSUMABLE","purchaseDate":1485359133060,"quantity":1,"receiptId":"M3qQCAiytxUzm3G05OworddJDiSi6ijXQGRFSK#AD=:1:11","renewalDate":null,"term":null,"termSku":null,"testTransaction":false}', true);

     $response = new Response(Response::RESULT_OK, $receipt);

     $this->assertTrue($response->isValid(), 'receipt must be valid');
     $this->assertEquals(Response::RESULT_OK, $response->getResultCode(), 'receipt result code must match');

     $this->assertCount(1, $response->getPurchases(), 'receipt must have single purchase');

     $purchase = $response->getPurchases()[0];
     $this->assertEquals('pack_100', $purchase->getProductId(), 'productId does not match');
     $this->assertEquals('M3qQCAiytxUzm3G05OworddJDiSi6ijXQGRFSK#AD=:1:11', $purchase->getTransactionId(), 'transactionId does not match');
     $this->assertEquals(1, $purchase->getQuantity(), 'quantity does not match');
   }

}