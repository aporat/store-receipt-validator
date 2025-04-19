<?php

namespace ReceiptValidator\AppleAppStore;

/**
 * Represents the subtype of a Server Notification V2.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/subtype
 */
enum ServerNotificationSubtype: string
{
    case INITIAL_BUY = 'INITIAL_BUY';
    case RESUBSCRIBE = 'RESUBSCRIBE';
    case DOWNGRADE = 'DOWNGRADE';
    case UPGRADE = 'UPGRADE';
    case AUTO_RENEW_ENABLED = 'AUTO_RENEW_ENABLED';
    case AUTO_RENEW_DISABLED = 'AUTO_RENEW_DISABLED';
    case VOLUNTARY = 'VOLUNTARY';
    case BILLING_RETRY = 'BILLING_RETRY';
    case PRICE_INCREASE = 'PRICE_INCREASE';
    case GRACE_PERIOD = 'GRACE_PERIOD';
    case PENDING = 'PENDING';
    case ACCEPTED = 'ACCEPTED';
    case BILLING_RECOVERY = 'BILLING_RECOVERY';
    case PRODUCT_NOT_FOR_SALE = 'PRODUCT_NOT_FOR_SALE';
    case SUMMARY = 'SUMMARY';
    case FAILURE = 'FAILURE';
    case UNREPORTED = 'UNREPORTED';
}
