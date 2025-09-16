<?php

namespace ReceiptValidator\AppleAppStore;

/**
 * Represents notification types for App Store Server Notifications V2.
 *
 * This enum provides a type-safe way to handle all possible notification
 * events sent by Apple's servers.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/notificationtype
 */
enum ServerNotificationType: string
{
    // --- Subscription Lifecycle Events ---

    /**
     * A notification that indicates a customer subscribed to a product.
     */
    case SUBSCRIBED = 'SUBSCRIBED';

    /**
     * A notification that indicates a successful auto-renewal of a subscription.
     */
    case DID_RENEW = 'DID_RENEW';

    /**
     * A notification that indicates a change in the subscription renewal preference.
     */
    case DID_CHANGE_RENEWAL_PREF = 'DID_CHANGE_RENEWAL_PREF';

    /**
     * A notification that indicates a change in the subscription renewal status.
     */
    case DID_CHANGE_RENEWAL_STATUS = 'DID_CHANGE_RENEWAL_STATUS';

    /**
     * A notification that indicates a subscription has expired.
     */
    case EXPIRED = 'EXPIRED';

    /**
     * A notification that indicates a subscription failed to renew due to a billing issue.
     */
    case DID_FAIL_TO_RENEW = 'DID_FAIL_TO_RENEW';

    /**
     * A notification that indicates the grace period for a subscription has ended without a renewal.
     */
    case GRACE_PERIOD_EXPIRED = 'GRACE_PERIOD_EXPIRED';

    /**
     * A notification that indicates a subscription renewal was extended by the developer.
     */
    case RENEWAL_EXTENDED = 'RENEWAL_EXTENDED';

    /**
     * A notification that indicates a renewal extension was applied in bulk.
     * @deprecated Replaced by RENEWAL_EXTENDED.
     */
    case RENEWAL_EXTENSION = 'RENEWAL_EXTENSION';

    // --- Offer and Price Change Events ---

    /**
     * A notification that indicates a customer redeemed a promotional offer.
     */
    case OFFER_REDEEMED = 'OFFER_REDEEMED';

    /**
     * A notification that indicates a subscription price increase that requires customer consent.
     */
    case PRICE_INCREASE = 'PRICE_INCREASE';

    // --- Refund and Revocation Events ---

    /**
     * A notification that indicates the App Store refunded a transaction.
     */
    case REFUND = 'REFUND';

    /**
     * A notification that indicates the App Store declined a refund request.
     */
    case REFUND_DECLINED = 'REFUND_DECLINED';

    /**
     * A notification that indicates a refund was reversed due to a dispute.
     */
    case REFUND_REVERSED = 'REFUND_REVERSED';

    /**
     * A notification that indicates Apple Support revoked access to a subscription.
     */
    case REVOKE = 'REVOKE';

    // --- Consumable and Other Purchase Events ---

    /**
     * A notification sent when a customer requests consumption data for a consumable in-app purchase.
     */
    case CONSUMPTION_REQUEST = 'CONSUMPTION_REQUEST';

    /**
     * A notification for a one-time charge purchase event.
     */
    case ONE_TIME_CHARGE = 'ONE_TIME_CHARGE';

    /**
     * A notification related to an external purchase token.
     */
    case EXTERNAL_PURCHASE_TOKEN = 'EXTERNAL_PURCHASE_TOKEN';

    // --- Testing ---

    /**
     * A notification sent when a test notification is requested from the App Store Server API.
     */
    case TEST = 'TEST';
}
