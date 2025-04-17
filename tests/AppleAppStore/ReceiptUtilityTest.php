<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\ReceiptUtility;

class ReceiptUtilityTest extends TestCase
{
    public function testExtractTransactionIdFromTransactionReceipt(): void
    {
        $base64Receipt = 'ewoicHVyY2hhc2UtaW5mbyIgPSAiZXdvaWRISmhibk5oWTNScGIyNHRhV1FpSUQwZ0lqTXpPVGt6TXprNUlqc0tmUW89IjsKfQo=';

        $transactionId = ReceiptUtility::extractTransactionIdFromTransactionReceipt($base64Receipt);

        $this->assertNotNull($transactionId, 'Transaction ID should not be null');
        $this->assertEquals('33993399', $transactionId, 'Transaction ID not valid');
    }
}
