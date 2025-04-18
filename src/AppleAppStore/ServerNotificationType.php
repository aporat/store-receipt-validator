<?php

namespace ReceiptValidator\AppleAppStore;

/**
 * Represents notification types for App Store Server Notifications V2.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/notificationtype
 */
enum ServerNotificationType: string
{
    case SUBSCRIBED = 'SUBSCRIBED';
    case DID_CHANGE_RENEWAL_PREF = 'DID_CHANGE_RENEWAL_PREF';
    case DID_CHANGE_RENEWAL_STATUS = 'DID_CHANGE_RENEWAL_STATUS';
    case OFFER_REDEEMED = 'OFFER_REDEEMED';
    case DID_RENEW = 'DID_RENEW';
    case EXPIRED = 'EXPIRED';
    case DID_FAIL_TO_RENEW = 'DID_FAIL_TO_RENEW';
    case GRACE_PERIOD_EXPIRED = 'GRACE_PERIOD_EXPIRED';
    case PRICE_INCREASE = 'PRICE_INCREASE';
    case REFUND = 'REFUND';
    case REFUND_DECLINED = 'REFUND_DECLINED';
    case CONSUMPTION_REQUEST = 'CONSUMPTION_REQUEST';
    case RENEWAL_EXTENDED = 'RENEWAL_EXTENDED';
    case REVOKE = 'REVOKE';
    case TEST = 'TEST';
    case RENEWAL_EXTENSION = 'RENEWAL_EXTENSION';
    case REFUND_REVERSED = 'REFUND_REVERSED';
    case EXTERNAL_PURCHASE_TOKEN = 'EXTERNAL_PURCHASE_TOKEN';
    case ONE_TIME_CHARGE = 'ONE_TIME_CHARGE';
}
