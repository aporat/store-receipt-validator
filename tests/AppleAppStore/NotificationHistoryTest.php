<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\CheckTestNotificationResponse;
use ReceiptValidator\AppleAppStore\NotificationHistoryItem;
use ReceiptValidator\AppleAppStore\NotificationHistoryRequest;
use ReceiptValidator\AppleAppStore\NotificationHistoryResponse;
use ReceiptValidator\AppleAppStore\SendAttemptItem;
use ReceiptValidator\AppleAppStore\ServerNotification;
use ReceiptValidator\AppleAppStore\ServerNotificationSubtype;
use ReceiptValidator\AppleAppStore\ServerNotificationType;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\Tests\AppleAppStore\Support\JwsSigning;

/**
 * @group apple-app-store
 */
#[CoversClass(NotificationHistoryRequest::class)]
#[CoversClass(NotificationHistoryResponse::class)]
#[CoversClass(NotificationHistoryItem::class)]
#[CoversClass(SendAttemptItem::class)]
#[CoversClass(CheckTestNotificationResponse::class)]
final class NotificationHistoryTest extends TestCase
{
    use JwsSigning;

    // -------------------------------------------------------------------------
    // NotificationHistoryRequest
    // -------------------------------------------------------------------------

    public function testRequestAcceptsMillisecondTimestamps(): void
    {
        $request = new NotificationHistoryRequest(1_000_000_000_000, 2_000_000_000_000);

        self::assertSame(1_000_000_000_000, $request->startDate);
        self::assertSame(2_000_000_000_000, $request->endDate);
        self::assertSame([
            'startDate' => 1_000_000_000_000,
            'endDate'   => 2_000_000_000_000,
        ], $request->toArray());
    }

    public function testRequestAcceptsCarbonInstances(): void
    {
        $start = CarbonImmutable::createFromTimestampMs(1_700_000_000_000);
        $end   = CarbonImmutable::createFromTimestampMs(1_700_000_500_000);

        $request = new NotificationHistoryRequest($start, $end);

        self::assertSame(1_700_000_000_000, $request->startDate);
        self::assertSame(1_700_000_500_000, $request->endDate);
    }

    public function testRequestSerializesOptionalFilters(): void
    {
        $request                      = new NotificationHistoryRequest(1, 2);
        $request->notificationType    = ServerNotificationType::SUBSCRIBED;
        $request->notificationSubtype = ServerNotificationSubtype::INITIAL_BUY;
        $request->onlyFailures        = true;
        $request->transactionId       = '2000000000000001';

        self::assertSame([
            'startDate'           => 1,
            'endDate'             => 2,
            'notificationType'    => 'SUBSCRIBED',
            'notificationSubtype' => 'INITIAL_BUY',
            'onlyFailures'        => true,
            'transactionId'       => '2000000000000001',
        ], $request->toArray());
    }

    public function testRequestSerializesOnlyFailuresFalse(): void
    {
        $request               = new NotificationHistoryRequest(1, 2);
        $request->onlyFailures = false;

        self::assertSame(['startDate' => 1, 'endDate' => 2, 'onlyFailures' => false], $request->toArray());
    }

    // -------------------------------------------------------------------------
    // SendAttemptItem
    // -------------------------------------------------------------------------

    public function testSendAttemptItemParsesFields(): void
    {
        $item = new SendAttemptItem([
            'attemptDate'       => 1_700_000_000_000,
            'sendAttemptResult' => 'SUCCESS',
        ]);

        self::assertSame(1_700_000_000, $item->getAttemptDate()?->getTimestamp());
        self::assertSame('SUCCESS', $item->getSendAttemptResult());
        self::assertTrue($item->isSuccessful());
    }

    public function testSendAttemptItemHandlesMissingAndFailedFields(): void
    {
        $empty = new SendAttemptItem();
        self::assertNull($empty->getAttemptDate());
        self::assertNull($empty->getSendAttemptResult());
        self::assertFalse($empty->isSuccessful());

        $failed = new SendAttemptItem(['sendAttemptResult' => 'TIMED_OUT']);
        self::assertSame('TIMED_OUT', $failed->getSendAttemptResult());
        self::assertFalse($failed->isSuccessful());
    }

    // -------------------------------------------------------------------------
    // NotificationHistoryItem
    // -------------------------------------------------------------------------

    public function testItemParsesSendAttemptsAndSkipsNonArrays(): void
    {
        $item = new NotificationHistoryItem([
            'signedPayload' => 'jws',
            'sendAttempts'  => [
                ['attemptDate' => 1, 'sendAttemptResult' => 'TIMED_OUT'],
                'not-an-attempt',
                ['attemptDate' => 2, 'sendAttemptResult' => 'SUCCESS'],
            ],
        ]);

        self::assertSame('jws', $item->getSignedPayload());
        self::assertCount(2, $item->getSendAttempts());
        self::assertContainsOnlyInstancesOf(SendAttemptItem::class, $item->getSendAttempts());
        self::assertTrue($item->wasDelivered());
    }

    public function testItemWasDeliveredLooksAtTheLastAttempt(): void
    {
        $item = new NotificationHistoryItem([
            'sendAttempts' => [
                ['sendAttemptResult' => 'SUCCESS'],
                ['sendAttemptResult' => 'TIMED_OUT'],
            ],
        ]);

        self::assertFalse($item->wasDelivered());
    }

    public function testItemWithoutAttemptsWasNotDelivered(): void
    {
        $item = new NotificationHistoryItem(['sendAttempts' => 'not-a-list']);

        self::assertSame([], $item->getSendAttempts());
        self::assertFalse($item->wasDelivered());
    }

    public function testItemDecodesAnAppleSignedNotification(): void
    {
        $item = new NotificationHistoryItem(['signedPayload' => self::appleSignedPayload()]);

        $notification = $item->decodeNotification();

        self::assertInstanceOf(ServerNotification::class, $notification);
        self::assertSame(ServerNotificationType::TEST, $notification->getNotificationType());
    }

    public function testItemDecodeThrowsWhenPayloadMissing(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('signedPayload is missing');

        (new NotificationHistoryItem([]))->decodeNotification();
    }

    public function testItemDecodeThrowsWhenPayloadEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('signedPayload is missing');

        (new NotificationHistoryItem(['signedPayload' => '']))->decodeNotification();
    }

    // -------------------------------------------------------------------------
    // NotificationHistoryResponse
    // -------------------------------------------------------------------------

    public function testResponseParsesItemsAndPagination(): void
    {
        $response = new NotificationHistoryResponse([
            'paginationToken'     => 'next-page',
            'hasMore'             => true,
            'notificationHistory' => [
                ['signedPayload' => 'a', 'sendAttempts' => []],
                'junk',
                ['signedPayload' => 'b'],
            ],
        ]);

        self::assertSame('next-page', $response->getPaginationToken());
        self::assertTrue($response->hasMore());
        self::assertCount(2, $response->getNotificationHistory());
        self::assertContainsOnlyInstancesOf(NotificationHistoryItem::class, $response->getNotificationHistory());
        self::assertSame('b', $response->getNotificationHistory()[1]->getSignedPayload());
    }

    public function testResponseHandlesMissingFields(): void
    {
        $response = new NotificationHistoryResponse([]);

        self::assertNull($response->getPaginationToken());
        self::assertFalse($response->hasMore());
        self::assertSame([], $response->getNotificationHistory());
    }

    // -------------------------------------------------------------------------
    // CheckTestNotificationResponse
    // -------------------------------------------------------------------------

    public function testCheckTestNotificationResponseParsesFields(): void
    {
        $response = new CheckTestNotificationResponse([
            'signedPayload'          => 'jws',
            'firstSendAttemptResult' => 'TIMED_OUT',
            'sendAttempts'           => [
                ['attemptDate' => 1, 'sendAttemptResult' => 'TIMED_OUT'],
                42,
                ['attemptDate' => 2, 'sendAttemptResult' => 'SUCCESS'],
            ],
        ]);

        self::assertSame('jws', $response->getSignedPayload());
        self::assertSame('TIMED_OUT', $response->getFirstSendAttemptResult());
        self::assertFalse($response->wasDelivered());
        self::assertCount(2, $response->getSendAttempts());
        self::assertTrue($response->getSendAttempts()[1]->isSuccessful());
    }

    public function testCheckTestNotificationResponseHandlesMissingFields(): void
    {
        $response = new CheckTestNotificationResponse();

        self::assertNull($response->getSignedPayload());
        self::assertNull($response->getFirstSendAttemptResult());
        self::assertSame([], $response->getSendAttempts());
        self::assertFalse($response->wasDelivered());
    }
}
