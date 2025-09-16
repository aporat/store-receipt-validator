<?php

namespace ReceiptValidator\AppleAppStore;

/**
 * Represents the subtype of an App Store Server Notification V2.
 *
 * The subtype provides more specific information about the event that
 * triggered the notification.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/subtype
 */
enum ServerNotificationSubtype: string
{
    // --- Purchase & Subscription Changes ---

    /**
     * A new subscription purchase.
     */
    case INITIAL_BUY = 'INITIAL_BUY';

    /**
     * A customer resubscribed to a product they were previously subscribed to.
     */
    case RESUBSCRIBE = 'RESUBSCRIBE';

    /**
     * A subscription plan change to a lower level of service.
     */
    case DOWNGRADE = 'DOWNGRADE';

    /**
     * A subscription plan change to a higher level of service.
     */
    case UPGRADE = 'UPGRADE';

    /**
     * A customer enabled auto-renew for a subscription.
     */
    case AUTO_RENEW_ENABLED = 'AUTO_RENEW_ENABLED';

    /**
     * A customer disabled auto-renew for a subscription.
     */
    case AUTO_RENEW_DISABLED = 'AUTO_RENEW_DISABLED';

    /**
     * The customer voluntarily cancelled a subscription.
     */
    case VOLUNTARY = 'VOLUNTARY';

    /**
     * A notification with a summary of refunded transactions.
     */
    case SUMMARY = 'SUMMARY';

    // --- Billing & Renewal Issues ---

    /**
     * A subscription failed to renew due to a billing issue.
     */
    case BILLING_RETRY = 'BILLING_RETRY';

    /**
     * The subscription entered a grace period due to a billing issue.
     */
    case GRACE_PERIOD = 'GRACE_PERIOD';

    /**
     * A subscription renewal was recovered after a billing issue.
     */
    case BILLING_RECOVERY = 'BILLING_RECOVERY';

    /**
     * A billing error occurred, such as the product not being for sale.
     */
    case PRODUCT_NOT_FOR_SALE = 'PRODUCT_NOT_FOR_SALE';

    // --- Price Increases ---

    /**
     * A subscription price increase that the customer has not yet consented to.
     */
    case PENDING = 'PENDING';

    /**
     * A customer consented to a subscription price increase.
     */
    case ACCEPTED = 'ACCEPTED';

    /**
     * A subscription price increase occurred.
     */
    case PRICE_INCREASE = 'PRICE_INCREASE';

    // --- Refund Reversals ---
    /**
     * A refund reversal event has failed.
     */
    case FAILURE = 'FAILURE';

    /**
     * A refund reversal event was not reported.
     */
    case UNREPORTED = 'UNREPORTED';
}
