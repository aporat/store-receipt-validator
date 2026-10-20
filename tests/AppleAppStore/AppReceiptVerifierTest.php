<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\AppReceipt;
use ReceiptValidator\AppleAppStore\AppReceiptVerifier;
use ReceiptValidator\AppleAppStore\Asn1\AttributeSetDecoder;
use ReceiptValidator\AppleAppStore\ReceiptUtility;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\Tests\AppleAppStore\Support\ReceiptSigning;
use ValueError;

/**
 * StoreKit 1 receipts are only read once Apple's signature over them checks
 * out against the pinned Apple root.
 *
 * fixtures/apple-sandbox-receipt.der is a real sandbox app receipt signed by
 * Apple, from TPInAppReceipt's test assets (https://github.com/tikhop/TPInAppReceipt,
 * Tests/Assets/receipt-sandbox-g5, MIT licence, Copyright (c) Pavel Tikhonenko).
 */
#[CoversClass(AppReceiptVerifier::class)]
#[CoversClass(AppReceipt::class)]
final class AppReceiptVerifierTest extends TestCase
{
    use ReceiptSigning;

    private const string APPLE_RECEIPT = __DIR__ . '/fixtures/apple-sandbox-receipt.der';

    public function testARealAppleReceiptVerifiesAgainstThePinnedRoot(): void
    {
        $receipt = (new AppReceiptVerifier())->verify(base64_encode((string) file_get_contents(self::APPLE_RECEIPT)));

        self::assertSame('dev.bonzer.weeka.app', $receipt->getBundleId());
        self::assertSame('2000001092134138', $receipt->getTransactionId());
        self::assertSame(['2000001092134138', '2000001092148094'], $receipt->getTransactionIds());
    }

    public function testThePinnedRootIsApples(): void
    {
        $pem = (string) file_get_contents(__DIR__ . '/../../resources/AppleIncRootCertificate.pem');

        self::assertSame(AppReceiptVerifier::APPLE_ROOT_CA_FINGERPRINT, openssl_x509_fingerprint($pem, 'sha256'));

        $parsed = openssl_x509_parse($pem);
        self::assertIsArray($parsed);
        self::assertSame('Apple Root CA', $parsed['subject']['CN'] ?? null);
    }

    public function testAReceiptSignedByATrustedChainYieldsItsBundleAndTransactions(): void
    {
        $receipt = $this->testVerifier()->verify(
            self::signedReceipt('com.example', ['1000000000000001', '1000000000000002'])
        );

        self::assertSame('com.example', $receipt->getBundleId());
        self::assertSame('1000000000000001', $receipt->getTransactionId());
        self::assertSame(['1000000000000001', '1000000000000002'], $receipt->getTransactionIds());
    }

    public function testWhitespaceInTheBase64IsTolerated(): void
    {
        $receipt = $this->testVerifier()->verify(chunk_split(self::signedReceipt('com.example', ['1']), 76, "\r\n"));

        self::assertSame('com.example', $receipt->getBundleId());
    }

    public function testAReceiptWithoutPurchasesHasNoTransaction(): void
    {
        $receipt = $this->testVerifier()->verify(self::signedReceipt('com.example', []));

        self::assertNull($receipt->getTransactionId());
        self::assertSame([], $receipt->getTransactionIds());
    }

    public function testAReceiptFromAChainAppleDidNotIssueIsRejected(): void
    {
        // The test chain is well formed but not Apple's: the default verifier refuses it.
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Apple Root CA');

        (new AppReceiptVerifier())->verify(self::signedReceipt('com.example', ['1000000000000001']));
    }

    public function testASelfSignedReceiptIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Apple Root CA');

        $this->testVerifier()->verify(
            self::signedReceipt('com.example', ['1000000000000001'], self::selfSignedReceiptSigner())
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
    public static function brokenChains(): iterable
    {
        // leaf, embedded intermediate, expected message
        yield 'leaf without the receipt-signing marker' => ['leaf-no-marker', 'intermediate', 'receipt signing'];
        yield 'intermediate without the WWDR marker' => ['leaf', 'intermediate-no-marker', 'Apple Root CA'];
        yield 'intermediate that is not a CA' => ['leaf', 'intermediate-not-ca', 'Apple Root CA'];
        yield 'intermediate issued by an impostor root' => ['leaf', 'intermediate-by-impostor', 'Apple Root CA'];
    }

    #[DataProvider('brokenChains')]
    public function testAChainMissingAnyApplePropertyIsRejected(string $leaf, string $intermediate, string $message): void
    {
        $root = (string) file_get_contents(__DIR__ . '/certs/chain-variants/root.pem');
        $verifier = new AppReceiptVerifier($root);

        // The variant chain itself is accepted, so a rejection below is about the broken link.
        $good = self::signedReceipt('com.example', ['1'], self::variantSigner('leaf', 'intermediate'));
        self::assertSame('com.example', $verifier->verify($good)->getBundleId());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($message);

        $verifier->verify(self::signedReceipt('com.example', ['1'], self::variantSigner($leaf, $intermediate)));
    }

    public function testAnUnsignedReceiptIsRejectedAlthoughItReadsFine(): void
    {
        $forged = self::unsignedReceipt('com.example', ['1000000000000001']);

        // What an unverified read does: hand back the id without checking anything.
        self::assertSame('1000000000000001', ReceiptUtility::extractTransactionIdFromAppReceipt($forged));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('signature');

        $this->testVerifier()->verify($forged);
    }

    public function testAReceiptAlteredAfterSigningIsRejected(): void
    {
        $der = (string) base64_decode(self::signedReceipt('com.example', ['1000000000000001']), true);
        $at = strpos($der, '1000000000000001');
        self::assertNotFalse($at);
        $der[$at] = '2';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('signature');

        $this->testVerifier()->verify(base64_encode($der));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function nonReceipts(): iterable
    {
        yield 'empty' => [''];
        yield 'not base64' => ['not-a-receipt!'];
        yield 'a bare transaction id' => ['100000987654321'];
        yield 'base64 garbage' => [base64_encode('garbage')];
        yield 'oversized' => [str_repeat('A', AppReceiptVerifier::MAX_BYTES * 2)];
    }

    #[DataProvider('nonReceipts')]
    public function testInputThatIsNotAReceiptIsRejected(string $input): void
    {
        $this->expectException(ValidationException::class);

        $this->testVerifier()->verify($input);
    }

    public function testAReceiptWhoseContentsDoNotDecodeIsRejected(): void
    {
        $decoder = new class implements AttributeSetDecoder {
            public function decodePkcs7Receipt(string $der): array
            {
                return [];
            }

            public function decodeAttributeSet(string $ber): array
            {
                throw new ValueError('nope');
            }

            public function decodeScalar(string $ber): ?string
            {
                return null;
            }
        };

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('unreadable');

        (new AppReceiptVerifier(self::testRootPem(), $decoder))->verify(self::signedReceipt('com.example', ['1']));
    }

    public function testAReceiptWithoutABundleIdIsRejected(): void
    {
        $decoder = new class implements AttributeSetDecoder {
            public function decodePkcs7Receipt(string $der): array
            {
                return [];
            }

            public function decodeAttributeSet(string $ber): array
            {
                return [['17', 'in-app']];
            }

            public function decodeScalar(string $ber): ?string
            {
                return null;
            }
        };

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('bundle id');

        (new AppReceiptVerifier(self::testRootPem(), $decoder))->verify(self::signedReceipt('com.example', ['1']));
    }

    private function testVerifier(): AppReceiptVerifier
    {
        return new AppReceiptVerifier(self::testRootPem());
    }
}
