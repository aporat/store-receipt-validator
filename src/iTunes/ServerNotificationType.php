<?php

namespace ReceiptValidator\iTunes;

/**
 * Notification types for App Store Server Notifications V1.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/notificationtype
 */
enum ServerNotificationType: string
{
    case CONSUMPTION_REQUEST = 'CONSUMPTION_REQUEST';
    case DID_CHANGE_RENEWAL_PREF = 'DID_CHANGE_RENEWAL_PREF';
    case DID_CHANGE_RENEWAL_STATUS = 'DID_CHANGE_RENEWAL_STATUS';
    case DID_FAIL_TO_RENEW = 'DID_FAIL_TO_RENEW';
    case DID_RENEW = 'DID_RENEW';
    case EXPIRED = 'EXPIRED';
    case GRACE_PERIOD_EXPIRED = 'GRACE_PERIOD_EXPIRED';
    case INITIAL_BUY = 'INITIAL_BUY';
    case OFFER_REDEEMED = 'OFFER_REDEEMED';
    case PRICE_INCREASE = 'PRICE_INCREASE';
    case REFUND = 'REFUND';
    case REFUND_DECLINED = 'REFUND_DECLINED';
    case RENEWAL_EXTENDED = 'RENEWAL_EXTENDED';
    case REVOKE = 'REVOKE';
    case SUBSCRIBED = 'SUBSCRIBED';
    case TEST = 'TEST';
}
