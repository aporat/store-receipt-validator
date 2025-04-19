<?php

namespace ReceiptValidator\iTunes;

/**
 * Notification types for App Store Server Notifications V1.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/notificationtype
 */
enum ServerNotificationType: string
{
    case CANCEL = 'CANCEL';
    case CONSUMPTION_REQUEST = 'CONSUMPTION_REQUEST';
    case DID_CHANGE_RENEWAL_PREF = 'DID_CHANGE_RENEWAL_PREF';
    case DID_CHANGE_RENEWAL_STATUS = 'DID_CHANGE_RENEWAL_STATUS';
    case DID_FAIL_TO_RENEW = 'DID_FAIL_TO_RENEW';
    case DID_RECOVER = 'DID_RECOVER';
    case DID_RENEW = 'DID_RENEW';
    case INITIAL_BUY = 'INITIAL_BUY';
    case INTERACTIVE_RENEWAL = 'INTERACTIVE_RENEWAL';
    case PRICE_INCREASE_CONSENT = 'PRICE_INCREASE_CONSENT';
    case REFUND = 'REFUND';
    case REVOKE = 'REVOKE';
    case TEST = 'TEST';
}
