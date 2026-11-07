<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\LastTransactionItem;
use ReceiptValidator\AppleAppStore\OrderLookupResponse;
use ReceiptValidator\AppleAppStore\RefundHistoryResponse;
use ReceiptValidator\AppleAppStore\RenewalInfo;
use ReceiptValidator\AppleAppStore\Response;
use ReceiptValidator\AppleAppStore\SubscriptionStatus;
use ReceiptValidator\AppleAppStore\Transaction;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\Tests\AppleAppStore\Support\JwsSigning;

/**
 * Responses that carry signed transactions decode them only once the JWS
 * verifies against Apple's chain. The only Apple-signed JWS in the fixtures is
 * the test notification, so it stands in for a signed transaction here: the
 * verifier checks the chain and signature, not the claim set.
 *
 * @group apple-app-store
 */
#[CoversClass(Response::class)]
#[CoversClass(RefundHistoryResponse::class)]
#[CoversClass(OrderLookupResponse::class)]
#[CoversClass(LastTransactionItem::class)]
final class SignedTransactionResponsesTest extends TestCase
{
    use JwsSigning;

    public function testTransactionHistoryResponseDecodesAppleSignedTransactions(): void
    {
        $response = new Response([
            'environment'        => 'Sandbox',
            'signedTransactions' => [self::appleSignedPayload()],
        ]);

        self::assertCount(1, $response->getTransactions());
        self::assertInstanceOf(Transaction::class, $response->getTransactions()[0]);
    }

    public function testTransactionHistoryResponseRejectsUntrustedSignatures(): void
    {
        $this->expectException(ValidationException::class);

        new Response([
            'signedTransactions' => [self::signWithStoreKitChain(self::storeKitTransactionClaims())],
        ]);
    }

    public function testRefundHistoryResponseDecodesAppleSignedTransactions(): void
    {
        $response = new RefundHistoryResponse([
            'signedTransactions' => [self::appleSignedPayload()],
            'revision'           => 'rev',
            'hasMore'            => false,
        ]);

        self::assertCount(1, $response->getSignedTransactions());
        self::assertInstanceOf(Transaction::class, $response->getSignedTransactions()[0]);
    }

    public function testRefundHistoryResponseSkipsUntrustedSignatures(): void
    {
        $response = new RefundHistoryResponse([
            'signedTransactions' => [self::signWithStoreKitChain(self::storeKitTransactionClaims())],
        ]);

        self::assertSame([], $response->getSignedTransactions());
    }

    public function testOrderLookupResponseDecodesAppleSignedTransactions(): void
    {
        $response = new OrderLookupResponse([
            'status'             => 0,
            'signedTransactions' => [self::appleSignedPayload()],
        ]);

        self::assertTrue($response->isValid());
        self::assertSame(0, $response->getStatus());
        self::assertCount(1, $response->getSignedTransactions());
        self::assertInstanceOf(Transaction::class, $response->getSignedTransactions()[0]);
    }

    public function testOrderLookupResponseSkipsInvalidTokens(): void
    {
        $response = new OrderLookupResponse([
            'status'             => 1,
            'signedTransactions' => ['', 42, 'not-a-jws', self::signWithStoreKitChain(self::storeKitTransactionClaims())],
        ]);

        self::assertFalse($response->isValid());
        self::assertSame(1, $response->getStatus());
        self::assertSame([], $response->getSignedTransactions());
    }

    public function testOrderLookupResponseHandlesMissingFields(): void
    {
        $response = new OrderLookupResponse([]);

        self::assertTrue($response->isValid());
        self::assertSame([], $response->getSignedTransactions());

        $noList = new OrderLookupResponse(['signedTransactions' => 'nope']);
        self::assertSame([], $noList->getSignedTransactions());
    }

    public function testLastTransactionItemDecodesAppleSignedInfo(): void
    {
        $item = new LastTransactionItem([
            'originalTransactionId' => '2000000000000001',
            'status'                => 1,
            'signedTransactionInfo' => self::appleSignedPayload(),
            'signedRenewalInfo'     => self::appleSignedPayload(),
        ]);

        self::assertSame('2000000000000001', $item->getOriginalTransactionId());
        self::assertSame(SubscriptionStatus::Active, $item->getStatus());
        self::assertInstanceOf(Transaction::class, $item->getTransactionInfo());
        self::assertInstanceOf(RenewalInfo::class, $item->getRenewalInfo());
    }

    public function testLastTransactionItemWithoutSignedInfoHasNulls(): void
    {
        $item = new LastTransactionItem(['originalTransactionId' => 'x', 'status' => 2]);

        self::assertSame(SubscriptionStatus::Expired, $item->getStatus());
        self::assertNull($item->getTransactionInfo());
        self::assertNull($item->getRenewalInfo());
    }

    public function testLastTransactionItemRejectsUntrustedSignedInfo(): void
    {
        $this->expectException(ValidationException::class);

        new LastTransactionItem([
            'originalTransactionId' => 'x',
            'status'                => 1,
            'signedTransactionInfo' => self::signWithStoreKitChain(self::storeKitTransactionClaims()),
        ]);
    }
}
