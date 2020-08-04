<?php

namespace ReceiptValidator\Tests\WindowsStore;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\RunTimeException;
use ReceiptValidator\WindowsStore\Validator;

/**
 * @group library
 */
class WindowsValidatorTest extends TestCase
{
    /**
     * @dataProvider receiptProvider
     */
    public function testValidate($receipt): void
    {
        $validator = new Validator();
        $this->assertTrue($validator->validate($receipt), 'Receipt should validate successfully');
    }

    /**
     * @dataProvider receiptProvider
     */
    public function testValidateWithCache($receipt): void
    {
        $validator = new Validator(new DummyCache());
        $this->assertTrue($validator->validate($receipt), 'Receipt should validate successfully');
    }

    public function testValidateFails(): void
    {
        $this->expectException(RunTimeException::class);
        $this->expectExceptionMessage('Invalid XML');

        $validator = new Validator();
        $validator->validate('foo bar');
    }

    public function receiptProvider(): array
    {
        return [
            // App receipt
            [
                '<Receipt Version="1.0" ReceiptDate="2012-08-30T23:10:05Z" '.
                'CertificateId="b809e47cd0110a4db043b3f73e83acd917fe1336" '.
                'ReceiptDeviceId="4e362949-acc3-fe3a-e71b-89893eb4f528">'.
                '<AppReceipt Id="8ffa256d-eca8-712a-7cf8-cbf5522df24b" '.
                'AppId="55428GreenlakeApps.CurrentAppSimulatorEventTest_z7q3q7z11crfr" '.
                'PurchaseDate="2012-06-04T23:07:24Z" LicenseType="Full" />'.
                '<ProductReceipt Id="6bbf4366-6fb2-8be8-7947-92fd5f683530" '.
                'ProductId="Product1" PurchaseDate="2012-08-30T23:08:52Z" '.
                'ExpirationDate="2012-09-02T23:08:49Z" ProductType="Durable" '.
                'AppId="55428GreenlakeApps.CurrentAppSimulatorEventTest_z7q3q7z11crfr" />'.
                '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">'.
                '<SignedInfo>'.
                '<CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#" />'.
                '<SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256" />'.
                '<Reference URI="">'.
                '<Transforms>'.
                '<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" />'.
                '</Transforms>'.
                '<DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256" />'.
                '<DigestValue>cdiU06eD8X/w1aGCHeaGCG9w/kWZ8I099rw4mmPpvdU=</DigestValue>'.
                '</Reference>'.
                '</SignedInfo>'.
                '<SignatureValue>SjRIxS/2r2P6ZdgaR9bwUSa6ZItYYFpKLJZrnAa3zkMylbiWjh9oZGGng2p6/gtBHC2dSTZlLbqny'.
                'sJjl7mQp/A3wKaIkzjyRXv3kxoVaSV0pkqiPt04cIfFTP0JZkE5QD/vYxiWjeyGp1dThEM2RV811sRWvmEs/hHhVxb32e'.
                '8xCLtpALYx3a9lW51zRJJN0eNdPAvNoiCJlnogAoTToUQLHs72I1dECnSbeNPXiG7klpy5boKKMCZfnVXXkneWvVFtAA1'.
                'h2sB7ll40LEHO4oYN6VzD+uKd76QOgGmsu9iGVyRvvmMtahvtL1/pxoxsTRedhKq6zrzCfT8qfh3C1w=='.
                '</SignatureValue>'.
                '</Signature>'.
                '</Receipt>',
            ],
            // Product receipt
            [
                '<Receipt Version="1.0" ReceiptDate="2012-08-30T23:08:52Z" '.
                'CertificateId="b809e47cd0110a4db043b3f73e83acd917fe1336" '.
                'ReceiptDeviceId="4e362949-acc3-fe3a-e71b-89893eb4f528">'.
                '<ProductReceipt Id="6bbf4366-6fb2-8be8-7947-92fd5f683530" '.
                'ProductId="Product1" PurchaseDate="2012-08-30T23:08:52Z" '.
                'ExpirationDate="2012-09-02T23:08:49Z" ProductType="Durable" '.
                'AppId="55428GreenlakeApps.CurrentAppSimulatorEventTest_z7q3q7z11crfr" />'.
                '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">'.
                '<SignedInfo>'.
                '<CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#" />'.
                '<SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256" />'.
                '<Reference URI="">'.
                '<Transforms>'.
                '<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" />'.
                '</Transforms>'.
                '<DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256" />'.
                '<DigestValue>Uvi8jkTYd3HtpMmAMpOm94fLeqmcQ2KCrV1XmSuY1xI=</DigestValue>'.
                '</Reference>'.
                '</SignedInfo>'.
                '<SignatureValue>TT5fDET1X9nBk9/yKEJAjVASKjall3gw8u9N5Uizx4/Le9RtJtv+E9XSMjrOXK/TDicidIPLBjTbc'.
                'ZylYZdGPkMvAIc3/1mdLMZYJc+EXG9IsE9L74LmJ0OqGH5WjGK/UexAXxVBWDtBbDI2JLOaBevYsyy+4hLOcTXDSUA4tX'.
                'wPa2Bi+BRoUTdYE2mFW7ytOJNEs3jTiHrCK6JRvTyU9lGkNDMNx9loIr+mRks+BSf70KxPtE9XCpCvXyWa/Q1JaIyZI7l'.
                'lCH45Dn4SKFn6L/JBw8G8xSTrZ3sBYBKOnUDbSCfc8ucQX97EyivSPURvTyImmjpsXDm2LBaEgAMADg=='.
                '</SignatureValue>'.
                '</Signature>'.
                '</Receipt>',
            ],
        ];
    }
}
