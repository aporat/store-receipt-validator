<?php

namespace ReceiptValidator\AppleAppStore;

final class APIError
{
    /**
     * An error that indicates an invalid request.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/generalbadrequesterror
     */
    public const int GENERAL_BAD_REQUEST = 4000000;

    /**
     * An error that indicates an invalid app identifier.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidappidentifiererror
     */
    public const int INVALID_APP_IDENTIFIER = 4000002;

    /**
     * An error that indicates an invalid request revision.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidrequestrevisionerror
     */
    public const int INVALID_REQUEST_REVISION = 4000005;

    /**
     * An error that indicates an invalid transaction identifier.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidtransactioniderror
     */
    public const int INVALID_TRANSACTION_ID = 4000006;

    /**
     * An error that indicates an invalid original transaction identifier.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidoriginaltransactioniderror
     */
    public const int INVALID_ORIGINAL_TRANSACTION_ID = 4000008;

    /**
     * An error that indicates an invalid extend-by-days value.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidextendbydayserror
     */
    public const int INVALID_EXTEND_BY_DAYS = 4000009;

    /**
     * An error that indicates an invalid reason code.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidextendreasoncodeerror
     */
    public const int INVALID_EXTEND_REASON_CODE = 4000010;

    /**
     * An error that indicates an invalid request identifier.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidrequestidentifiererror
     */
    public const int INVALID_REQUEST_IDENTIFIER = 4000011;

    /**
     * An error that indicates that the start date is earlier than the earliest allowed date.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/startdatetoofarinpasterror
     */
    public const int START_DATE_TOO_FAR_IN_PAST = 4000012;

    /**
     * An error that indicates that the end date precedes the start date, or the two dates are equal.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/startdateafterenddateerror
     */
    public const int START_DATE_AFTER_END_DATE = 4000013;

    /**
     * An error that indicates the pagination token is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidpaginationtokenerror
     */
    public const int INVALID_PAGINATION_TOKEN = 4000014;

    /**
     * An error that indicates the start date is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidstartdateerror
     */
    public const int INVALID_START_DATE = 4000015;

    /**
     * An error that indicates the end date is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidenddateerror
     */
    public const int INVALID_END_DATE = 4000016;

    /**
     * An error that indicates the pagination token expired.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/paginationtokenexpirederror
     */
    public const int PAGINATION_TOKEN_EXPIRED = 4000017;

    /**
     * An error that indicates the notification type or subtype is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidnotificationtypeerror
     */
    public const int INVALID_NOTIFICATION_TYPE = 4000018;

    /**
     * An error that indicates the request is invalid because it has too many constraints applied.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/multiplefilterssuppliederror
     */
    public const int MULTIPLE_FILTERS_SUPPLIED = 4000019;

    /**
     * An error that indicates the test notification token is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidtestnotificationtokenerror
     */
    public const int INVALID_TEST_NOTIFICATION_TOKEN = 4000020;

    /**
     * An error that indicates an invalid sort parameter.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidsorterror
     */
    public const int INVALID_SORT = 4000021;

    /**
     * An error that indicates an invalid product type parameter.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidproducttypeerror
     */
    public const int INVALID_PRODUCT_TYPE = 4000022;

    /**
     * An error that indicates the product ID parameter is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidproductiderror
     */
    public const int INVALID_PRODUCT_ID = 4000023;

    /**
     * An error that indicates an invalid subscription group identifier.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidsubscriptiongroupidentifiererror
     */
    public const int INVALID_SUBSCRIPTION_GROUP_IDENTIFIER = 4000024;

    /**
     * An error that indicates the query parameter exclude-revoked is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidexcluderevokederror
     */
    public const int INVALID_EXCLUDE_REVOKED = 4000025;

    /**
     * An error that indicates an invalid in-app ownership type parameter.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidinappownershiptypeerror
     */
    public const int INVALID_IN_APP_OWNERSHIP_TYPE = 4000026;

    /**
     * An error that indicates a required storefront country code is empty.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidemptystorefrontcountrycodelisterror
     */
    public const int INVALID_EMPTY_STOREFRONT_COUNTRY_CODE_LIST = 4000027;

    /**
     * An error that indicates a storefront code is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidstorefrontcountrycodeerror
     */
    public const int INVALID_STOREFRONT_COUNTRY_CODE = 4000028;

    /**
     * An error that indicates the revoked parameter contains an invalid value.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidrevokederror
     */
    public const int INVALID_REVOKED = 4000030;

    /**
     * An error that indicates the status parameter is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidstatuserror
     */
    public const int INVALID_STATUS = 4000031;

    /**
     * An error that indicates the value of the account tenure field is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidaccounttenureerror
     */
    public const int INVALID_ACCOUNT_TENURE = 4000032;

    /**
     * An error that indicates the value of the app account token field is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidappaccounttokenerror
     */
    public const int INVALID_APP_ACCOUNT_TOKEN = 4000033;

    /**
     * An error that indicates the value of the consumption status field is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidconsumptionstatuserror
     */
    public const int INVALID_CONSUMPTION_STATUS = 4000034;

    /**
     * An error that indicates the customer consented field is invalid or doesn’t indicate that the customer consented.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidcustomerconsentederror
     */
    public const int INVALID_CUSTOMER_CONSENTED = 4000035;

    /**
     * An error that indicates the value in the delivery status field is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invaliddeliverystatuserror
     */
    public const int INVALID_DELIVERY_STATUS = 4000036;

    /**
     * An error that indicates the value in the lifetime dollars purchased field is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidlifetimedollarspurchasederror
     */
    public const int INVALID_LIFETIME_DOLLARS_PURCHASED = 4000037;

    /**
     * An error that indicates the value in the lifetime dollars refunded field is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidlifetimedollarsrefundederror
     */
    public const int INVALID_LIFETIME_DOLLARS_REFUNDED = 4000038;

    /**
     * An error that indicates the value in the platform field is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidplatformerror
     */
    public const int INVALID_PLATFORM = 4000039;

    /**
     * An error that indicates the value in the playtime field is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidplaytimeerror
     */
    public const int INVALID_PLAY_TIME = 4000040;

    /**
     * An error that indicates the value in the sample content provided field is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidsamplecontentprovidederror
     */
    public const int INVALID_SAMPLE_CONTENT_PROVIDED = 4000041;

    /**
     * An error that indicates the value in the user status field is invalid.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invaliduserstatuserror
     */
    public const int INVALID_USER_STATUS = 4000042;

    /**
     * An error that indicates the transaction identifier doesn’t represent a consumable in-app purchase.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidtransactionnotconsumableerror
     */
    public const int INVALID_TRANSACTION_NOT_CONSUMABLE = 4000043;

    /**
     * An error that indicates the transaction identifier represents an unsupported in-app purchase type.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/invalidtransactiontypenotsupportederror
     */
    public const int INVALID_TRANSACTION_TYPE_NOT_SUPPORTED = 4000047;

    /**
     * An error that indicates the endpoint doesn't support an app transaction ID.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/apptransactionidnotsupportederror
     */
    public const int APP_TRANSACTION_ID_NOT_SUPPORTED = 4000048;

    /**
     * An error that indicates the subscription doesn't qualify for a renewal-date extension due to its subscription state.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/subscriptionextensionineligibleerror
     */
    public const int SUBSCRIPTION_EXTENSION_INELIGIBLE = 4030004;

    /**
     * An error that indicates the subscription doesn’t qualify for a renewal-date extension because it has already received the maximum extensions.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/subscriptionmaxextensionerror
     */
    public const int SUBSCRIPTION_MAX_EXTENSION = 4030005;

    /**
     * An error that indicates a subscription isn't directly eligible for a renewal date extension because the user obtained it through Family Sharing.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/familysharedsubscriptionextensionineligibleerror
     */
    public const int FAMILY_SHARED_SUBSCRIPTION_EXTENSION_INELIGIBLE = 4030007;

    /**
     * An error that indicates the App Store account wasn’t found.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/accountnotfounderror
     */
    public const int ACCOUNT_NOT_FOUND = 4040001;

    /**
     * An error response that indicates the App Store account wasn’t found, but you can try again.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/accountnotfoundretryableerror
     */
    public const int ACCOUNT_NOT_FOUND_RETRYABLE = 4040002;

    /**
     * An error that indicates the app wasn’t found.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/appnotfounderror
     */
    public const int APP_NOT_FOUND = 4040003;

    /**
     * An error response that indicates the app wasn’t found, but you can try again.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/appnotfoundretryableerror
     */
    public const int APP_NOT_FOUND_RETRYABLE = 4040004;

    /**
     * An error that indicates an original transaction identifier wasn't found.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/originaltransactionidnotfounderror
     */
    public const int ORIGINAL_TRANSACTION_ID_NOT_FOUND = 4040005;

    /**
     * An error response that indicates the original transaction identifier wasn’t found, but you can try again.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/originaltransactionidnotfoundretryableerror
     */
    public const int ORIGINAL_TRANSACTION_ID_NOT_FOUND_RETRYABLE = 4040006;

    /**
     * An error that indicates that the App Store server couldn’t find a notifications URL for your app in this environment.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/servernotificationurlnotfounderror
     */
    public const int SERVER_NOTIFICATION_URL_NOT_FOUND = 4040007;

    /**
     * An error that indicates that the test notification token is expired or the test notification status isn’t available.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/testnotificationnotfounderror
     */
    public const int TEST_NOTIFICATION_NOT_FOUND = 4040008;

    /**
     * An error that indicates the server didn't find a subscription-renewal-date extension request for the request identifier and product identifier you provided.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/statusrequestnotfounderror
     */
    public const int STATUS_REQUEST_NOT_FOUND = 4040009;

    /**
     * An error that indicates a transaction identifier wasn't found.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/transactionidnotfounderror
     */
    public const int TRANSACTION_ID_NOT_FOUND = 4040010;

    /**
     * An error that indicates that the request exceeded the rate limit.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/ratelimitexceedederror
     */
    public const int RATE_LIMIT_EXCEEDED = 4290000;

    /**
     * An error that indicates a general internal error.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/generalinternalerror
     */
    public const int GENERAL_INTERNAL = 5000000;

    /**
     * An error response that indicates an unknown error occurred, but you can try again.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/generalinternalretryableerror
     */
    public const int GENERAL_INTERNAL_RETRYABLE = 5000001;
}
