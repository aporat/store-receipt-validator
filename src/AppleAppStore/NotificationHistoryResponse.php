<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use ReceiptValidator\Support\ValueCasting;

/**
 * Response from the Get Notification History endpoint.
 *
 * Contains a paginated list of App Store Server Notification history items.
 * When {@see $hasMore} is true, pass {@see $paginationToken} back to
 * {@see Validator::getNotificationHistory()} to fetch the next page.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/notificationhistoryresponse
 */
final class NotificationHistoryResponse
{
    use ValueCasting;

    /**
     * The token to pass on the next request to fetch the next page.
     * Null when there are no more pages.
     */
    public readonly ?string $paginationToken;

    /**
     * Whether additional pages of notification history are available.
     */
    public readonly bool $hasMore;

    /**
     * The notification history entries for this page.
     *
     * @var list<NotificationHistoryItem>
     */
    public readonly array $notificationHistory;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->paginationToken = $this->toString($data, 'paginationToken');
        $this->hasMore         = $this->toBool($data, 'hasMore');

        $raw   = is_array($data['notificationHistory'] ?? null) ? $data['notificationHistory'] : [];
        $items = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $items[] = new NotificationHistoryItem($item);
            }
        }
        $this->notificationHistory = $items;
    }

    public function getPaginationToken(): ?string
    {
        return $this->paginationToken;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    /**
     * @return list<NotificationHistoryItem>
     */
    public function getNotificationHistory(): array
    {
        return $this->notificationHistory;
    }
}
