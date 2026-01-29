<?php

namespace ReceiptValidator\iTunes;

/**
 * Notification types for App Store Server Notifications V1.
 *
 * @deprecated since version 2.0. Use {@see \ReceiptValidator\AppleAppStore\ServerNotificationType} instead.
 *             Apple has deprecated V1 notifications in favor of App Store Server Notifications V2.
 * @see https://developer.apple.com/documentation/appstoreservernotifications/notificationtype
 * @see https://developer.apple.com/documentation/appstoreservernotifications
 */
enum ServerNotificationType: string
{
    // --- Subscription Lifecycle Events ---

    /** A customer canceled their subscription. */
    case CANCEL = 'CANCEL';

    /** A notification that a customer started an initial subscription purchase. */
    case INITIAL_BUY = 'INITIAL_BUY';

    /** A subscription renewed automatically. */
    case DID_RENEW = 'DID_RENEW';

    /** A customer manually renewed a subscription. */
    case INTERACTIVE_RENEWAL = 'INTERACTIVE_RENEWAL';

    // --- Renewal Preference & Status Changes ---

    /** A customer changed the product they want to auto-renew. */
    case DID_CHANGE_RENEWAL_PREF = 'DID_CHANGE_RENEWAL_PREF';

    /** A customer enabled or disabled auto-renew status. */
    case DID_CHANGE_RENEWAL_STATUS = 'DID_CHANGE_RENEWAL_STATUS';

    /** Apple attempted to renew but failed (billing issue, etc.). */
    case DID_FAIL_TO_RENEW = 'DID_FAIL_TO_RENEW';

    /** A subscription was recovered after a failed renewal. */
    case DID_RECOVER = 'DID_RECOVER';

    // --- Price & Billing ---

    /** A customer consented to a subscription price increase. */
    case PRICE_INCREASE_CONSENT = 'PRICE_INCREASE_CONSENT';

    // --- Refunds & Revocations ---

    /** Apple issued a refund for a transaction. */
    case REFUND = 'REFUND';

    /** Apple revoked a subscription or purchase. */
    case REVOKE = 'REVOKE';

    // --- Other Events ---

    /** A consumption request for a consumable in-app purchase. */
    case CONSUMPTION_REQUEST = 'CONSUMPTION_REQUEST';

    /** A test notification sent from App Store Server. */
    case TEST = 'TEST';
}
