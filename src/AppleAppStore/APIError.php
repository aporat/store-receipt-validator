<?php

namespace ReceiptValidator\AppleAppStore;

/**
 * Represents error codes from the Apple App Store Server API.
 *
 * This enum provides a type-safe way to handle specific error codes returned
 * by the API, encapsulating the integer code and its official meaning.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/error_codes
 */
enum APIError: int
{
    /** @see https://developer.apple.com/documentation/appstoreserverapi/generalbadrequesterror */
    case GENERAL_BAD_REQUEST = 4000000;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidappidentifiererror */
    case INVALID_APP_IDENTIFIER = 4000002;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidrequestrevisionerror */
    case INVALID_REQUEST_REVISION = 4000005;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidtransactioniderror */
    case INVALID_TRANSACTION_ID = 4000006;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidoriginaltransactioniderror */
    case INVALID_ORIGINAL_TRANSACTION_ID = 4000008;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidextendbydayserror */
    case INVALID_EXTEND_BY_DAYS = 4000009;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidextendreasoncodeerror */
    case INVALID_EXTEND_REASON_CODE = 4000010;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidrequestidentifiererror */
    case INVALID_REQUEST_IDENTIFIER = 4000011;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/startdatetoofarinpasterror */
    case START_DATE_TOO_FAR_IN_PAST = 4000012;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/startdateafterenddateerror */
    case START_DATE_AFTER_END_DATE = 4000013;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidpaginationtokenerror */
    case INVALID_PAGINATION_TOKEN = 4000014;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidstartdateerror */
    case INVALID_START_DATE = 4000015;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidenddateerror */
    case INVALID_END_DATE = 4000016;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/paginationtokenexpirederror */
    case PAGINATION_TOKEN_EXPIRED = 4000017;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidnotificationtypeerror */
    case INVALID_NOTIFICATION_TYPE = 4000018;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/multiplefilterssuppliederror */
    case MULTIPLE_FILTERS_SUPPLIED = 4000019;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidtestnotificationtokenerror */
    case INVALID_TEST_NOTIFICATION_TOKEN = 4000020;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidsorterror */
    case INVALID_SORT = 4000021;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidproducttypeerror */
    case INVALID_PRODUCT_TYPE = 4000022;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidproductiderror */
    case INVALID_PRODUCT_ID = 4000023;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidsubscriptiongroupidentifiererror */
    case INVALID_SUBSCRIPTION_GROUP_IDENTIFIER = 4000024;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidexcluderevokederror */
    case INVALID_EXCLUDE_REVOKED = 4000025;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidinappownershiptypeerror */
    case INVALID_IN_APP_OWNERSHIP_TYPE = 4000026;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidemptystorefrontcountrycodelisterror */
    case INVALID_EMPTY_STOREFRONT_COUNTRY_CODE_LIST = 4000027;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidstorefrontcountrycodeerror */
    case INVALID_STOREFRONT_COUNTRY_CODE = 4000028;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidrevokederror */
    case INVALID_REVOKED = 4000030;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidstatuserror */
    case INVALID_STATUS = 4000031;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidaccounttenureerror */
    case INVALID_ACCOUNT_TENURE = 4000032;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidappaccounttokenerror */
    case INVALID_APP_ACCOUNT_TOKEN = 4000033;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidconsumptionstatuserror */
    case INVALID_CONSUMPTION_STATUS = 4000034;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidcustomerconsentederror */
    case INVALID_CUSTOMER_CONSENTED = 4000035;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invaliddeliverystatuserror */
    case INVALID_DELIVERY_STATUS = 4000036;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidlifetimedollarspurchasederror */
    case INVALID_LIFETIME_DOLLARS_PURCHASED = 4000037;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidlifetimedollarsrefundederror */
    case INVALID_LIFETIME_DOLLARS_REFUNDED = 4000038;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidplatformerror */
    case INVALID_PLATFORM = 4000039;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidplaytimeerror */
    case INVALID_PLAY_TIME = 4000040;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidsamplecontentprovidederror */
    case INVALID_SAMPLE_CONTENT_PROVIDED = 4000041;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invaliduserstatuserror */
    case INVALID_USER_STATUS = 4000042;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidtransactionnotconsumableerror */
    case INVALID_TRANSACTION_NOT_CONSUMABLE = 4000043;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/invalidtransactiontypenotsupportederror */
    case INVALID_TRANSACTION_TYPE_NOT_SUPPORTED = 4000047;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/apptransactionidnotsupportederror */
    case APP_TRANSACTION_ID_NOT_SUPPORTED = 4000048;

    /** @see https://developer.apple.com/documentation/appstoreserverapi/subscriptionextensionineligibleerror */
    case SUBSCRIPTION_EXTENSION_INELIGIBLE = 4030004;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/subscriptionmaxextensionerror */
    case SUBSCRIPTION_MAX_EXTENSION = 4030005;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/familysharedsubscriptionextensionineligibleerror */
    case FAMILY_SHARED_SUBSCRIPTION_EXTENSION_INELIGIBLE = 4030007;

    /** @see https://developer.apple.com/documentation/appstoreserverapi/accountnotfounderror */
    case ACCOUNT_NOT_FOUND = 4040001;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/accountnotfoundretryableerror */
    case ACCOUNT_NOT_FOUND_RETRYABLE = 4040002;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/appnotfounderror */
    case APP_NOT_FOUND = 4040003;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/appnotfoundretryableerror */
    case APP_NOT_FOUND_RETRYABLE = 4040004;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/originaltransactionidnotfounderror */
    case ORIGINAL_TRANSACTION_ID_NOT_FOUND = 4040005;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/originaltransactionidnotfoundretryableerror */
    case ORIGINAL_TRANSACTION_ID_NOT_FOUND_RETRYABLE = 4040006;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/servernotificationurlnotfounderror */
    case SERVER_NOTIFICATION_URL_NOT_FOUND = 4040007;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/testnotificationnotfounderror */
    case TEST_NOTIFICATION_NOT_FOUND = 4040008;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/statusrequestnotfounderror */
    case STATUS_REQUEST_NOT_FOUND = 4040009;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/transactionidnotfounderror */
    case TRANSACTION_ID_NOT_FOUND = 4040010;

    /** @see https://developer.apple.com/documentation/appstoreserverapi/ratelimitexceedederror */
    case RATE_LIMIT_EXCEEDED = 4290000;

    /** @see https://developer.apple.com/documentation/appstoreserverapi/generalinternalerror */
    case GENERAL_INTERNAL = 5000000;
    /** @see https://developer.apple.com/documentation/appstoreserverapi/generalinternalretryableerror */
    case GENERAL_INTERNAL_RETRYABLE = 5000001;

    /**
     * Returns a concise human-readable description for the error.
     */
    public function message(): string
    {
        return match ($this) {
            self::GENERAL_BAD_REQUEST => 'The request was invalid.',
            self::INVALID_APP_IDENTIFIER => 'The app identifier is invalid.',
            self::INVALID_REQUEST_REVISION => 'The request revision is invalid.',
            self::INVALID_TRANSACTION_ID => 'The transaction identifier is invalid.',
            self::INVALID_ORIGINAL_TRANSACTION_ID => 'The original transaction identifier is invalid.',
            self::INVALID_EXTEND_BY_DAYS => 'The extend-by-days value is invalid.',
            self::INVALID_EXTEND_REASON_CODE => 'The extend reason code is invalid.',
            self::INVALID_REQUEST_IDENTIFIER => 'The request identifier is invalid.',
            self::START_DATE_TOO_FAR_IN_PAST => 'The start date is earlier than allowed.',
            self::START_DATE_AFTER_END_DATE => 'The end date precedes or equals the start date.',
            self::INVALID_PAGINATION_TOKEN => 'The pagination token is invalid.',
            self::INVALID_START_DATE => 'The start date is invalid.',
            self::INVALID_END_DATE => 'The end date is invalid.',
            self::PAGINATION_TOKEN_EXPIRED => 'The pagination token expired.',
            self::INVALID_NOTIFICATION_TYPE => 'The notification type or subtype is invalid.',
            self::MULTIPLE_FILTERS_SUPPLIED => 'Too many filters were supplied.',
            self::INVALID_TEST_NOTIFICATION_TOKEN => 'The test notification token is invalid.',
            self::INVALID_SORT => 'The sort parameter is invalid.',
            self::INVALID_PRODUCT_TYPE => 'The product type parameter is invalid.',
            self::INVALID_PRODUCT_ID => 'The product identifier is invalid.',
            self::INVALID_SUBSCRIPTION_GROUP_IDENTIFIER => 'The subscription group identifier is invalid.',
            self::INVALID_EXCLUDE_REVOKED => 'The exclude-revoked parameter is invalid.',
            self::INVALID_IN_APP_OWNERSHIP_TYPE => 'The in-app ownership type is invalid.',
            self::INVALID_EMPTY_STOREFRONT_COUNTRY_CODE_LIST => 'The storefront country code list is empty.',
            self::INVALID_STOREFRONT_COUNTRY_CODE => 'The storefront country code is invalid.',
            self::INVALID_REVOKED => 'The revoked parameter is invalid.',
            self::INVALID_STATUS => 'The status parameter is invalid.',
            self::INVALID_ACCOUNT_TENURE => 'The account tenure value is invalid.',
            self::INVALID_APP_ACCOUNT_TOKEN => 'The app account token value is invalid.',
            self::INVALID_CONSUMPTION_STATUS => 'The consumption status value is invalid.',
            self::INVALID_CUSTOMER_CONSENTED => 'The customer consent value is invalid.',
            self::INVALID_DELIVERY_STATUS => 'The delivery status value is invalid.',
            self::INVALID_LIFETIME_DOLLARS_PURCHASED => 'The lifetime dollars purchased value is invalid.',
            self::INVALID_LIFETIME_DOLLARS_REFUNDED => 'The lifetime dollars refunded value is invalid.',
            self::INVALID_PLATFORM => 'The platform value is invalid.',
            self::INVALID_PLAY_TIME => 'The playtime value is invalid.',
            self::INVALID_SAMPLE_CONTENT_PROVIDED => 'The sample content provided value is invalid.',
            self::INVALID_USER_STATUS => 'The user status value is invalid.',
            self::INVALID_TRANSACTION_NOT_CONSUMABLE => 'The transaction isn’t a consumable purchase.',
            self::INVALID_TRANSACTION_TYPE_NOT_SUPPORTED => 'The transaction type isn’t supported by this endpoint.',
            self::APP_TRANSACTION_ID_NOT_SUPPORTED => 'This endpoint doesn’t support an app transaction ID.',
            self::SUBSCRIPTION_EXTENSION_INELIGIBLE => 'The subscription isn’t eligible for an extension.',
            self::SUBSCRIPTION_MAX_EXTENSION => 'The subscription already has the maximum extensions.',
            self::FAMILY_SHARED_SUBSCRIPTION_EXTENSION_INELIGIBLE => 'Family-shared subscriptions aren’t eligible for direct extension.',
            self::ACCOUNT_NOT_FOUND => 'The App Store account wasn’t found.',
            self::ACCOUNT_NOT_FOUND_RETRYABLE => 'The App Store account wasn’t found (retryable).',
            self::APP_NOT_FOUND => 'The app wasn’t found.',
            self::APP_NOT_FOUND_RETRYABLE => 'The app wasn’t found (retryable).',
            self::ORIGINAL_TRANSACTION_ID_NOT_FOUND => 'The original transaction identifier wasn’t found.',
            self::ORIGINAL_TRANSACTION_ID_NOT_FOUND_RETRYABLE => 'The original transaction identifier wasn’t found (retryable).',
            self::SERVER_NOTIFICATION_URL_NOT_FOUND => 'No notifications URL configured for this environment.',
            self::TEST_NOTIFICATION_NOT_FOUND => 'The test notification token is expired or unavailable.',
            self::STATUS_REQUEST_NOT_FOUND => 'No matching renewal-date extension status request found.',
            self::TRANSACTION_ID_NOT_FOUND => 'The transaction identifier wasn’t found.',
            self::RATE_LIMIT_EXCEEDED => 'The request exceeded the rate limit.',
            self::GENERAL_INTERNAL => 'A general internal error occurred.',
            self::GENERAL_INTERNAL_RETRYABLE => 'A general internal error occurred (retryable).',
        };
    }

    /**
     * Indicates whether Apple marks this error as retryable.
     */
    public function isRetryable(): bool
    {
        return match ($this) {
            self::ACCOUNT_NOT_FOUND_RETRYABLE,
            self::APP_NOT_FOUND_RETRYABLE,
            self::ORIGINAL_TRANSACTION_ID_NOT_FOUND_RETRYABLE,
            self::GENERAL_INTERNAL_RETRYABLE => true,
            default => false,
        };
    }

    /**
     * Convenience helper mirroring tryFrom().
     */
    public static function fromInt(int $code): ?self
    {
        return self::tryFrom($code);
    }
}
