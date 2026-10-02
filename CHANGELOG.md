# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/).

Entries for released versions were written from the code diff between each tag and
the one before it, with the original GitHub release notes used for context. Where the
two disagreed, the diff was followed and the discrepancy is noted in the entry.

## [Unreleased]

### Added
- `AppleAppStore\Validator::verifySignedTransaction()`, `verifySignedRenewalInfo()` and
  `verifySignedAppTransaction()` verify StoreKit 2 JWS payloads offline, checking the signature,
  Apple certificate chain, bundle ID and environment
  ([#230](https://github.com/aporat/store-receipt-validator/pull/230), closes
  [#229](https://github.com/aporat/store-receipt-validator/issues/229)).
- `AppleAppStore\Validator::verifyNotification()` verifies an App Store Server Notification and
  requires its bundle ID and environment to match the validator. Constructing `ServerNotification`
  directly still verifies the signature only
  ([#239](https://github.com/aporat/store-receipt-validator/pull/239)).
- Optional `appAppleId` on the `AppleAppStore\Validator` constructor, with `setAppAppleId()` and
  `getAppAppleId()`. When set, production app transactions and notifications must carry the matching
  app Apple ID, as Apple's official verifier requires
  ([#239](https://github.com/aporat/store-receipt-validator/pull/239)).
- `AppleAppStore\Signature` namespace with the four signing helpers Apple's official libraries
  provide: `PromotionalOfferV2SignatureCreator`, `IntroductoryOfferEligibilitySignatureCreator`,
  `AdvancedCommerceInAppSignatureCreator` (JWS for StoreKit 2) and
  `PromotionalOfferSignatureCreator` (StoreKit 1 ECDSA signature)
  ([#237](https://github.com/aporat/store-receipt-validator/pull/237)).
- `AppleAppStore\ConsumptionRequestV1` for the deprecated v1 Send Consumption Information endpoint,
  and `DeliveryStatus` / `RefundPreference` enums for v2
  ([#236](https://github.com/aporat/store-receipt-validator/pull/236)).
- `ConsumptionRequest::setConsumptionPercent()` converts a plain percentage to the milliunits Apple
  expects ([#236](https://github.com/aporat/store-receipt-validator/pull/236)).
- Optional status filter on `getAllSubscriptionStatuses()`, sent as repeated `status` query
  parameters ([#240](https://github.com/aporat/store-receipt-validator/pull/240)).
- `User-Agent: store-receipt-validator/php/<version>` on App Store Server API requests, with the
  version read from Composer. `AbstractValidator::userAgent()` is public for reuse
  ([#238](https://github.com/aporat/store-receipt-validator/pull/238)).
- `ServerNotification::getAppAppleId()`, and an optional `TokenVerifier` constructor argument for
  tests that sign with their own chain
  ([#239](https://github.com/aporat/store-receipt-validator/pull/239)).
- `EndpointPathsTest`, a single table of every App Store Server API endpoint with its verb,
  documented path and Apple docs link, plus a guard that fails when an endpoint method has no row
  ([#241](https://github.com/aporat/store-receipt-validator/pull/241)).

### Changed
- App Store Server API requests go to Apple's documented hosts, `api.storekit.apple.com` and
  `api.storekit-sandbox.apple.com`, instead of the legacy `*.itunes.apple.com` names. **Add the new
  hosts to any egress allowlist before upgrading**
  ([#242](https://github.com/aporat/store-receipt-validator/pull/242)).
- `sendConsumptionInformation()` targets the v2 endpoint (`/inApps/v2/transactions/consumption`)
  again, as it did in 9.0.0 before 10.0.0 reverted it to v1, aligning with App Store Server API 1.19
  and Apple's official libraries. `ConsumptionRequest` is the v2 model: `deliveryStatus` and
  `refundPreference` take the new enums, and legacy v1 integers are still accepted and mapped.
  `deliveryStatus` is required by v2, so a request without one now throws before any HTTP call. Pass
  a `ConsumptionRequestV1` to keep using the v1 endpoint
  ([#236](https://github.com/aporat/store-receipt-validator/pull/236)).
- `ServerNotification` reads the bundle ID, environment and app Apple ID from whichever payload
  section Apple populated (`data`, `summary`, `appData` or `externalPurchaseToken`). Summary and
  external purchase notifications previously reported an empty bundle ID and defaulted to sandbox. A
  notification with no environment now throws instead of being treated as sandbox
  ([#239](https://github.com/aporat/store-receipt-validator/pull/239)).
- `TokenVerifier` now requires the App Store signing marker on the leaf certificate, the WWDR marker
  on the intermediate, an `x5c` chain of exactly three certificates, and every certificate to be
  valid at the payload's `signedDate`
  ([#230](https://github.com/aporat/store-receipt-validator/pull/230)).
- `phpseclib/phpseclib` constraint is `^3.0 || ^4.0`. `ReceiptUtility` selects the matching ASN.1
  decoder at runtime ([#234](https://github.com/aporat/store-receipt-validator/pull/234), fixes
  [#232](https://github.com/aporat/store-receipt-validator/issues/232)).

### Fixed
- `getAppTransactionInfo()` called `/inApps/v1/transactions/appTransaction/{id}` since 10.0.0; the
  documented path is the plural `appTransactions`
  ([#235](https://github.com/aporat/store-receipt-validator/pull/235), fixes
  [#231](https://github.com/aporat/store-receipt-validator/issues/231)).

## [10.0.0] - 2026-09-05

### Added
- New `ReceiptValidator\GooglePlay` namespace covering the Google Play Developer API (Android
  Publisher v3). `GooglePlay\Validator` is constructed with `(string $packageName,
  ServiceAccountCredentials|string|null $credentials = null, Environment $environment =
  Environment::PRODUCTION)` and exposes `getSubscriptionPurchaseV2()`, `acknowledgeSubscription()`,
  `revokeSubscription()`, `getProductPurchase()`, `acknowledgeProduct()`, `consumeProduct()` and
  `getVoidedPurchases()`, plus `validate(?string $purchaseToken = null)` (a
  `getSubscriptionPurchaseV2()` alias), `setPurchaseToken()`, `getPackageName()`,
  `setAccessTokenProvider()` and `getAccessTokenProvider()`.
  ([#227](https://github.com/aporat/store-receipt-validator/pull/227),
  [#228](https://github.com/aporat/store-receipt-validator/pull/228))
- Google authentication via `GooglePlay\JWT\AccessTokenProvider` (interface),
  `ServiceAccountCredentials` (`fromJson()`, `fromArray()`), `ServiceAccountTokenProvider` (OAuth
  2.0 JWT bearer flow with in-memory token caching; `getAccessToken()`, `clearCache()`,
  `createAssertion()`) and `CallbackAccessTokenProvider` for plugging in an external library such as
  `google/auth`. ([#227](https://github.com/aporat/store-receipt-validator/pull/227))
- Google Play models: `SubscriptionPurchase` (extends `AbstractResponse`; `getLineItems()`,
  `getLatestLineItem()`, `getExpiryTime()`, `getProductIds()`, `isEntitled()`, `isAutoRenewing()`,
  ...), `SubscriptionLineItem`, `ProductPurchase` and `VoidedPurchase` (extend
  `AbstractTransaction`), `VoidedPurchasesResponse`, `VoidedPurchasesParams`, `RevocationContext`
  (`fullRefund()`, `proratedRefund()`, `itemBasedRefund()`), and a string-backed
  `GooglePlay\APIError` enum with `message()`, `isRetryable()` and `fromString()`.
  ([#227](https://github.com/aporat/store-receipt-validator/pull/227))
- Google Play enums: `SubscriptionState`, `AcknowledgementState`, `ConsumptionState`,
  `ProductPurchaseState`, `PurchaseType`, `RefundType`, `VoidedProductType`, `VoidedPurchaseType`,
  `VoidedReason`, `VoidedSource`, `SubscriptionNotificationType` and
  `OneTimeProductNotificationType`.
  ([#227](https://github.com/aporat/store-receipt-validator/pull/227))
- Real-time Developer Notifications: `GooglePlay\ServerNotification` (with `fromPubSubMessage()` to
  unwrap the Pub/Sub push envelope), `SubscriptionNotification`, `OneTimeProductNotification` and
  `VoidedPurchaseNotification`. ([#227](https://github.com/aporat/store-receipt-validator/pull/227))
- `Support\ValueCasting::toDateFromRfc3339()` protected helper for parsing Google's RFC 3339
  timestamps into UTC `CarbonImmutable`.

### Changed
- **Breaking:** `phpseclib/phpseclib` requirement raised from `^3.0` to `^4.0`, and
  `AppleAppStore\ReceiptUtility` was ported to phpseclib 4's typed ASN.1 maps. The public signatures
  of `extractTransactionIdFromAppReceipt()` and `extractTransactionIdFromTransactionReceipt()` are
  unchanged; malformed PKCS #7 input now consistently raises `ValueError` (wrapping the phpseclib
  exception) and the `id-signedData` OID name is accepted alongside the numeric OID.
  ([#226](https://github.com/aporat/store-receipt-validator/pull/226) widened to `^3.0 || ^4.0`,
  then [#228](https://github.com/aporat/store-receipt-validator/pull/228) required `^4.0`)
- `guzzlehttp/psr7` requirement widened from `^2.6` to `^2.6 || ^3.0`.
  ([#225](https://github.com/aporat/store-receipt-validator/pull/225))
- `AbstractValidator::validate()` is no longer abstract: the default implementation throws a
  `ValidationException` directing callers to the store-specific endpoint methods (used by
  `GooglePlay\Validator`). Custom subclasses are no longer forced to implement it.
- **Breaking:** `AppleAppStore\NotificationHistoryItem::wasDelivered()` now returns whether the most
  recent send attempt succeeded (previously whether the first attempt did), and returns `false` when
  there are no attempts. (commit b7e77ab "cleanup")
- **Breaking (regression):** `AppleAppStore\Validator::getAppTransactionInfo()` was changed to
  request `/inApps/v1/transactions/appTransaction/{transactionId}` instead of the plural
  `/inApps/v1/transactions/appTransactions/{transactionId}` used in 9.0.0. Apple does not serve the
  singular path, so this method is broken in 10.0.0; reported as issue
  [#231](https://github.com/aporat/store-receipt-validator/issues/231) and the plural path was
  restored in the following release. (commit b7e77ab "cleanup")
- **Breaking (regression):** `AppleAppStore\Validator::sendConsumptionInformation()` was changed to
  `PUT /inApps/v1/transactions/consumption/{transactionId}` instead of the v2 path used in 9.0.0,
  while `ConsumptionRequest` kept sending v2-only fields such as `consumptionPercentage`; the v2
  endpoint was reinstated after this release. (commit b7e77ab "cleanup")
- Note: the original release notes described only the phpseclib 4 requirement as breaking and did
  not mention the `NotificationHistoryItem` change or the two App Store endpoint path changes above.

### Removed
- **Breaking:** `AppleAppStore\NotificationHistoryItem::$firstSendAttemptResult` and
  `getFirstSendAttemptResult()` were removed; inspect `getSendAttempts()` instead. (commit b7e77ab
  "cleanup")

## [9.0.0] - 2026-05-15

### Added
- New App Store Server API methods on `AppleAppStore\Validator`: `getTransactionHistory(string
  $transactionId, ?TransactionHistoryParams $params = null): Response` (`GET
  /inApps/v2/history/{id}`), `getTransactionInfo(string $transactionId): Transaction` (`GET
  /inApps/v1/transactions/{id}`, JWS-verified), `getAppTransactionInfo(string $transactionId):
  AppTransaction` (`GET /inApps/v1/transactions/appTransactions/{id}`), `finishTransaction(string
  $transactionId): void` (`POST /inApps/v1/transactions/{id}/finish`) and `setAppAccountToken(string
  $originalTransactionId, string $appAccountToken): void` (`PUT
  /inApps/v1/transactions/{id}/appAccountToken`; the token must be a UUID v4).
  ([#219](https://github.com/aporat/store-receipt-validator/pull/219),
  [#220](https://github.com/aporat/store-receipt-validator/pull/220))
- Subscription, refund and consumption methods: `getAllSubscriptionStatuses(string
  $originalTransactionId): SubscriptionStatusResponse` (`GET /inApps/v1/subscriptions/{id}`),
  `getRefundHistory(string $transactionId, ?string $revision = null): RefundHistoryResponse` (`GET
  /inApps/v2/refund/lookup/{id}`), `sendConsumptionInformation(string $transactionId,
  ConsumptionRequest $request): void` (`PUT /inApps/v2/transactions/consumption/{id}`),
  `extendSubscriptionRenewalDate(string $originalTransactionId, ExtendRenewalDateRequest $request):
  ExtendRenewalDateResponse` (`PUT /inApps/v1/subscriptions/extend/{id}`),
  `extendSubscriptionRenewalDatesForAllActiveSubscribers(MassExtendRenewalDateRequest $request):
  string` (`POST /inApps/v1/subscriptions/extend/mass`, returns the request identifier) and
  `getStatusOfSubscriptionRenewalDateExtensions(string $productId, string $requestIdentifier):
  MassExtendRenewalDateStatusResponse`.
  ([#220](https://github.com/aporat/store-receipt-validator/pull/220))
- Order and notification methods: `lookUpOrderId(string $orderId): OrderLookupResponse` (`GET
  /inApps/v1/lookup/{orderId}`, not available in sandbox), `getTestNotificationStatus(string
  $testNotificationToken): CheckTestNotificationResponse` (`GET
  /inApps/v1/notifications/test/{token}`) and `getNotificationHistory(NotificationHistoryRequest
  $request, ?string $paginationToken = null): NotificationHistoryResponse` (`POST
  /inApps/v1/notifications/history`).
  ([#219](https://github.com/aporat/store-receipt-validator/pull/219))
- New request classes in `ReceiptValidator\AppleAppStore`: `TransactionHistoryParams`
  (`toQueryParams()`; revision, date range, productId/productType/subscriptionGroupIdentifier
  filters, sort, inAppOwnershipType, revoked), `NotificationHistoryRequest` (`toArray()`),
  `ConsumptionRequest`, `ExtendRenewalDateRequest` and `MassExtendRenewalDateRequest`.
- New response and model classes: `AppTransaction`, `CheckTestNotificationResponse`,
  `SendAttemptItem`, `NotificationHistoryResponse`, `NotificationHistoryItem` (including
  `decodeNotification(): ServerNotification`), `OrderLookupResponse`, `RefundHistoryResponse`,
  `SubscriptionStatusResponse` (with `getAllLastTransactions()`), `SubscriptionGroupStatusItem`,
  `LastTransactionItem`, `ExtendRenewalDateResponse`, `MassExtendRenewalDateStatusResponse`, and the
  int-backed `SubscriptionStatus` enum with `label()`.
- Protected `AppleAppStore\Validator::makeRawRequest(string $method, string $uri = '', array
  $queryParams = [], ?array $requestBody = null): array` sends JSON request bodies and treats a
  `200` with an empty body as success; `makeRequest()` is now a thin wrapper that builds a
  `Response`.

### Changed
- `AppleAppStore\Validator::validate()` signature is now `validate(?string $transactionId = null,
  ?TransactionHistoryParams $params = null): Response` and delegates to `getTransactionHistory()`;
  the default sort remains `DESCENDING`.
- Array-valued query parameters are now serialised as repeated keys (`productId=a&productId=b`)
  rather than PHP's bracket notation, as Apple's API requires; the `query` array is also included in
  the success log context.
- `phpunit/phpunit` dev requirement raised from `^12.0` to `^13.0`.
  ([#218](https://github.com/aporat/store-receipt-validator/pull/218))
- Note on the "correct API versions" commit (f5ca106,
  [#220](https://github.com/aporat/store-receipt-validator/pull/220)): it moved
  `getAppTransactionInfo()`, `setAppAccountToken()` and `getAllSubscriptionStatuses()` from
  `/inApps/v2/...` to `/inApps/v1/...`, changed the app-transaction path to
  `/inApps/v1/transactions/appTransactions/{transactionId}` with a required `$transactionId`
  argument, and read `signedAppTransactionInfo` instead of `signedTransactionInfo`. All three
  methods were first introduced earlier in this same release cycle
  ([#219](https://github.com/aporat/store-receipt-validator/pull/219)), so no endpoint that shipped
  in 8.0.0 changed: `validate()` still calls `/inApps/v2/history/{id}` and
  `requestTestNotification()` still calls `POST /inApps/v1/notifications/test`.

### Deprecated
- `AppleAppStore\Validator::validate()` is deprecated in favour of `getTransactionHistory()`
  (paginated history with filters) or `getTransactionInfo()` (a single transaction). It still works.

## [8.0.0] - 2026-03-26

### Added
- `AbstractValidator::setLogger(LoggerInterface $logger): static` adds PSR-3 logging to all
  validators; the default is `NullLogger`. The Apple App Store, iTunes and Amazon validators log
  `debug` on each request, `info` on success (and on the iTunes sandbox/production retry), `warning`
  on API error responses and `error` on connection failures.
  ([#214](https://github.com/aporat/store-receipt-validator/pull/214))
- `AbstractValidator::setHttpClient(Psr\Http\Client\ClientInterface $client): static` injects any
  PSR-18 client. ([#215](https://github.com/aporat/store-receipt-validator/pull/215); builds on
  [#209](https://github.com/aporat/store-receipt-validator/pull/209) by @erickskrauch, which first
  loosened the parameter from `GuzzleHttp\Client` to `GuzzleHttp\ClientInterface`)
- Protected `getRequestFactory(): RequestFactoryInterface` and `getStreamFactory():
  StreamFactoryInterface` (PSR-17) with protected `$requestFactory`/`$streamFactory` properties,
  defaulting to `GuzzleHttp\Psr7\HttpFactory`. There are no public setters for the factories;
  subclasses can assign the properties.
  ([#215](https://github.com/aporat/store-receipt-validator/pull/215))

### Changed
- **Breaking:** `setHttpClient()` dropped its `?string $baseUri` second parameter and now requires a
  PSR-18 `ClientInterface` (Guzzle's `Client` still qualifies). Requests are now sent with absolute
  URLs, so a `base_uri` configured on an injected Guzzle client is ignored.
  ([#215](https://github.com/aporat/store-receipt-validator/pull/215))
- **Breaking:** `AbstractValidator` now declares a constructor (it initialises the logger); custom
  subclasses must call `parent::__construct()`.
  ([#214](https://github.com/aporat/store-receipt-validator/pull/214))
- **Breaking:** Transport failures are caught as `Psr\Http\Client\ClientExceptionInterface` instead
  of `GuzzleHttp\Exception\GuzzleException`; they are still rethrown as `ValidationException` with
  the original exception as `previous`.
  ([#215](https://github.com/aporat/store-receipt-validator/pull/215))
- `setEnvironment()` and `setHttpClient()` now return `static` instead of `self`.
- Note: the original release notes say "Migrate from Guzzle to PSR-18/PSR-17 HTTP interfaces", but
  `guzzlehttp/guzzle` and `guzzlehttp/psr7` remain required dependencies (composer.json is unchanged
  in this release) and Guzzle is still the default client when none is injected; what changed is the
  injection type.

### Removed
- **Breaking:** `AbstractValidator::getBaseUri()` and the protected `$baseUri` property were
  removed. ([#215](https://github.com/aporat/store-receipt-validator/pull/215))
- **Breaking:** The protected `$client_options` array (Guzzle `timeout`, `connect_timeout`,
  `http_errors`) was removed; the 30-second timeouts and `http_errors => false` are now hardcoded in
  the default client, so inject your own PSR-18 client to customise them.
  ([#215](https://github.com/aporat/store-receipt-validator/pull/215))

## [7.1.0] - 2026-01-29

### Changed
- **Breaking:** Minimum PHP version raised from `^8.3` to `^8.4`. (commit 830c893)
- **Breaking:** `AppleAppStore\RenewalInfo` and `iTunes\RenewalInfo` are now `final readonly`
  classes with public readonly properties (previously `AppleAppStore\RenewalInfo` was a non-final
  class with protected properties and could be subclassed), and `AbstractRenewalInfo` is now
  `abstract readonly`. `$rawData` on both is a non-nullable `array`, and
  `AppleAppStore\RenewalInfo::$eligibleWinBackOfferIds` is normalised to a `list<string>`.
  ([#208](https://github.com/aporat/store-receipt-validator/pull/208))
- `declare(strict_types=1)` added to the remaining source files (enums, JWT classes,
  `ValidationException`, `ValueCasting`).
  ([#208](https://github.com/aporat/store-receipt-validator/pull/208))
- Dev tooling: `phpstan/phpstan-phpunit` now allows `^1.4 || ^2.0`, `squizlabs/php_codesniffer`
  allows `^3.10 || ^4.0`, and a `phpstan.neon` (level 8) was added.
  ([#203](https://github.com/aporat/store-receipt-validator/pull/203),
  [#206](https://github.com/aporat/store-receipt-validator/pull/206)) Note: the original release
  notes said "upgrade to PHPStan v2", but the `phpstan/phpstan` constraint has been `^1.11 || ^2.0`
  since 7.0.0; only the phpstan-phpunit extension constraint was widened in this release.
- composer.json metadata: description changed to "PHP receipt validator for Apple App Store and
  Amazon Appstore" (Google Play keyword dropped), `allow-plugins` set for `php-http/discovery`, and
  scripts renamed (`lint`, `lint:fix`, `test:coverage`, `ci`).
  ([#206](https://github.com/aporat/store-receipt-validator/pull/206))

### Deprecated
- The legacy verifyReceipt / V1 notification classes are marked `@deprecated` in favour of their
  `AppleAppStore` counterparts: `iTunes\Validator`, `iTunes\Response`, `iTunes\Transaction`,
  `iTunes\RenewalInfo`, `iTunes\APIError`, `iTunes\ServerNotification` and
  `iTunes\ServerNotificationType`.
  ([#207](https://github.com/aporat/store-receipt-validator/pull/207)) Note: the annotations read
  "@deprecated since version 2.0", which does not match this release (7.1.0).

## [7.0.0] - 2025-09-17

### Added
- `AbstractValidator::setHttpClient(GuzzleHttp\Client $client, ?string $baseUri = null): self` and
  `getBaseUri(): ?string` for injecting a preconfigured HTTP client.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- `Support\ValueCasting` trait (`toString()`, `toInt()`, `toBool()`, `toDateFromMs()`) and the
  `AbstractRenewalInfo` base class shared by both `RenewalInfo` models.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- `APIError` enums gain `message()` on each case; `AppleAppStore\APIError` adds `isRetryable()` and
  `fromInt()`, `iTunes\APIError` adds `fromInt()`, `Amazon\APIError` adds `fromString()`.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- `AppleAppStore\ServerNotificationType` adds an `UNKNOWN` case, `fromString()` (returns `UNKNOWN`
  for unrecognised values instead of throwing), `isSubscriptionLifecycle()`, `isRefundRelated()` and
  `isOfferOrPriceEvent()`; `ServerNotificationSubtype` adds `UNKNOWN`, `fromString()`,
  `isSubscriptionChange()`, `isBillingRelated()`, `isPriceChange()` and `isRefundReversal()`.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- `Amazon\Response` gains `getReceiptId()`, `getProductId()`, `getUserId()`, `getProductType()`,
  `getPurchaseDate()`, `getCancellationDate()` and `isTestTransaction()`; `iTunes\RenewalInfo` gains
  `getExpirationReason(): ?string` and `hasExpirationIntent(): bool`; `AppleAppStore\Transaction`
  gains `getEnvironment()`. ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- Response, Transaction and RenewalInfo models now expose their parsed values as public readonly
  properties (for example `AppleAppStore\Transaction::$purchaseDate`,
  `AppleAppStore\Response::$revision`, `iTunes\Response::$latestReceiptInfo`) in addition to
  getters. ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- `Environment::fromString()` now trims input and accepts `'prod'` as an alias for production.
- composer.json now declares `psr/http-client ^1.0`, `psr/http-factory ^1.0`, `psr/http-message
  ^2.0`, `psr/log ^3.0` and `guzzlehttp/psr7 ^2.6`; `guzzlehttp/guzzle` allows `^7.9|^8.0`;
  `minimum-stability` changed from `dev` to `stable`.

### Changed
- **Breaking:** Every date getter returns `Carbon\CarbonInterface|null` (backed by UTC
  `CarbonImmutable`) instead of `Carbon\Carbon|null`. Affected methods:
  `AppleAppStore\Transaction::getPurchaseDate()`, `getOriginalPurchaseDate()`, `getExpiresDate()`,
  `getSignedDate()`, `getRevocationDate()`; `AppleAppStore\RenewalInfo::getExpirationIntentDate()`,
  `getGracePeriodExpiresDate()`, `getSignedDate()`, `getRecentSubscriptionStartDate()`,
  `getRenewalDate()`; `AppleAppStore\ServerNotification::getSignedDate()` (non-null
  `CarbonInterface`); `iTunes\Transaction::getPurchaseDate()`, `getOriginalPurchaseDate()`,
  `getExpiresDate()`, `getCancellationDate()`; `iTunes\Response::getOriginalPurchaseDate()`,
  `getRequestDate()`, `getReceiptCreationDate()`; `iTunes\RenewalInfo::getGracePeriodExpiresDate()`;
  `iTunes\ServerNotification::getAutoRenewStatusChangeDate()`;
  `Amazon\Transaction::getPurchaseDate()` (now nullable; was non-null `Carbon`),
  `getCancellationDate()`, `getRenewalDate()`, `getGracePeriodEndDate()`, `getFreeTrialEndDate()`.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- **Breaking:** Models are immutable value objects built in their constructors:
  `AbstractTransaction` is `abstract readonly`; `AppleAppStore\Transaction`, `iTunes\Transaction`
  and `Amazon\Transaction` are `final readonly`; `AppleAppStore\Response`, `iTunes\Response`,
  `Amazon\Response`, `iTunes\RenewalInfo` and `Amazon\Validator` are `final` and can no longer be
  extended. ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- **Breaking:** Constructor signatures: `AbstractResponse::__construct(array $data = [], Environment
  $environment = Environment::PRODUCTION)` no longer accepts `null`;
  `AppleAppStore\Response::__construct(array $data = [])` derives the environment from the payload's
  `environment` field; `AbstractTransaction::__construct(array $rawData = [], int $quantity = 1,
  ?string $productId = null, ?string $transactionId = null)` with quantity defaulting to `1` (was
  `0`). ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- **Breaking:** Nullable booleans tightened to `bool`:
  `AppleAppStore\RenewalInfo::getAutoRenewStatus()`, `isInBillingRetryPeriod()`, `isUpgraded()`;
  `AppleAppStore\Response::hasMore()`; `iTunes\Transaction::isTrialPeriod()`,
  `isInIntroOfferPeriod()`; `Amazon\Transaction::isAutoRenewing()`;
  `iTunes\RenewalInfo::isInBillingRetryPeriod()` (was `?int`) and `getStatus()` (now non-null
  `string`). ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- **Breaking:** Other nullability changes: `AbstractTransaction::getProductId()` and
  `getTransactionId()` return `?string` (were `string`); `getRawData()` on responses and
  transactions returns a non-null `array`; `iTunes\Transaction::getOriginalTransactionId()`,
  `iTunes\RenewalInfo::getAutoRenewProductId()` and `getOriginalTransactionId()` return `?string`.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- **Breaking:** The `APIError` classes are now backed enums (`Amazon\APIError: string`,
  `AppleAppStore\APIError: int`, `iTunes\APIError: int`): former constants are enum cases (use
  `->value` for the raw code) and the static `messages()` arrays were replaced by `message()`.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- **Breaking:** `Environment` is now a string-backed enum (`SANDBOX = 'sandbox'`, `PRODUCTION =
  'production'`), so `Environment::from()`/`tryFrom()` and `->value` are available.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- **Breaking:** `AbstractValidator::$client` is `protected` (was `public`);
  `Amazon\Validator::__construct()` now defaults `$environment` to `Environment::PRODUCTION`.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- `AppleAppStore\ServerNotification` throws `ValidationException("Unknown notificationType: ...")`
  for unrecognised notification types instead of letting the enum's `ValueError` escape.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- `AppleAppStore\JWT\TokenGenerator::EXPIRATION_MINUTES` reduced from `60` to `20`, so generated App
  Store Server API tokens expire after 20 minutes. `TokenGenerator`, `TokenVerifier` and
  `ReceiptUtility` are now `final`; `TokenGeneratorConfig`, `TokenIssuer` and `TokenKey` are `final
  readonly`. ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- Note: the original release notes listed only the `CarbonInterface` return-type change.

### Removed
- **Breaking:** `ArrayAccess` (`offsetGet()`, `offsetSet()`, `offsetUnset()`, `offsetExists()`)
  removed from `AppleAppStore\Transaction`, `AppleAppStore\Response`, `AppleAppStore\RenewalInfo`,
  `iTunes\Transaction`, `iTunes\RenewalInfo` and `Amazon\Transaction`.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- **Breaking:** `parse()` on all Response and Transaction classes and `parseData()` on both
  `RenewalInfo` classes were removed; parsing happens in the constructor.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- **Breaking:** Setters removed: `AbstractResponse::setEnvironment()`,
  `AbstractTransaction::setQuantity()`, `setProductId()`, `setTransactionId()`,
  `AppleAppStore\Transaction::setEnvironment()` and `Amazon\Validator::setDeveloperSecret()`.
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- **Breaking:** `AppleAppStore\Response::getSignedTransactions()` removed (the raw JWS strings
  remain available via `getRawData()['signedTransactions']`).
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- **Breaking:** `AppleAppStore\Transaction::getIsUpgraded(): ?bool` replaced by `isUpgraded():
  bool`. ([#200](https://github.com/aporat/store-receipt-validator/pull/200))
- **Breaking:** `iTunes\RenewalInfo` constants `RETRY_PERIOD_ACTIVE`, `RETRY_PERIOD_INACTIVE`,
  `AUTO_RENEW_ACTIVE` and `AUTO_RENEW_INACTIVE` removed (the getters now return `bool`).
  ([#200](https://github.com/aporat/store-receipt-validator/pull/200))

## [6.1.4] - 2025-09-16

### Fixed
- Sandbox receipts sent to a production `iTunes\Validator` no longer fail.
  `AbstractValidator::getClient()` built the Guzzle client once with the first `base_uri` and
  returned that same client on every later call, so when Apple answered status 21007 and the
  validator retried via `makeRequest(Environment::SANDBOX)`, the retry was still POSTed to
  `https://buy.itunes.apple.com`, got 21007 again and surfaced as `ValidationException` "iTunes API
  error [21007]". The client is now rebuilt whenever `getClient()` is called with a different base
  URI (tracked in a new `protected ?string $baseUri`), so the retry really goes to
  `https://sandbox.itunes.apple.com`. The same fix makes `setEnvironment()` take effect on any
  validator that has already made a request. Reported in issue
  [#199](https://github.com/aporat/store-receipt-validator/issues/199) by @VladymyrKarpov.

## [6.1.2] - 2025-08-06

### Changed
- `AppleAppStore\Validator` now passes the relative path (for example `/inApps/v2/history/{id}`) to
  Guzzle and relies on the client's `base_uri`, instead of concatenating the endpoint into an
  absolute URL. Requests go to the same hosts as before, so there is no user-visible change; this is
  a cleanup that removed a redundant concatenation. Thanks to @amo-mykyta
  ([#196](https://github.com/aporat/store-receipt-validator/pull/196)).

## [6.1.0] - 2025-05-04

### Added
- `AppleAppStore\Validator::requestTestNotification(): string` calls `POST
  /inApps/v1/notifications/test` on the App Store Server API and returns the
  `testNotificationToken`; throws `ValidationException` if the token is missing from the response.
- `AppleAppStore\APIError`: a `final` class of `int` constants for App Store Server API error codes,
  from `GENERAL_BAD_REQUEST` (4000000) through `INVALID_TRANSACTION_ID` (4000006),
  `TRANSACTION_ID_NOT_FOUND` (4040010), `RATE_LIMIT_EXCEEDED` (4290000) to
  `GENERAL_INTERNAL_RETRYABLE` (5000001). Constants only; there is no `messages()` helper on this
  class.
- `iTunes\APIError`: a `final` class with `int` constants for the verifyReceipt status codes
  (`VALID` = 0, `JSON_INVALID` = 21000, `RECEIPT_DATA_MALFORMED` = 21002,
  `RECEIPT_AUTHENTICATION_FAILED` = 21003, `SHARED_SECRET_INVALID` = 21004, `SERVER_UNAVAILABLE` =
  21005, `SUBSCRIPTION_EXPIRED` = 21006, `SANDBOX_RECEIPT_ON_PRODUCTION` = 21007,
  `PRODUCTION_RECEIPT_ON_SANDBOX` = 21008, `INTERNAL_DATA_ACCESS_ERROR` = 21009,
  `USER_ACCOUNT_NOT_FOUND` = 21010, `INTERNAL_ERROR` = 21100) and a static `messages(): array` map
  of code to description.
- `Amazon\APIError`: a `final` class with `string` constants for Amazon RVS error names
  (`INVALID_RECEIPT_ID`, `INVALID_USER_ID`, `INVALID_DEVELOPER_SECRET`, `INVALID_JSON`,
  `INTERNAL_ERROR`) and a static `messages(): array`.

### Changed
- `iTunes\Validator` now throws `ValidationException` with the message `iTunes API error [<status>]:
  <description>` and with `getCode()` set to Apple's status code; previously the message was the
  bare description and the code was 0. The 21007 sandbox retry and the 21006 (expired subscription)
  pass-through are unchanged.
- `AppleAppStore\Validator` error handling: on a non-200 response the exception message is `App
  Store API error [<code>]: <message>` where the code is the body's `errorCode` (falling back to the
  HTTP status) and is also exposed via `getCode()`; HTTP 401 and 404 produce the fixed messages
  "Unauthenticated" and "Not Found". The transaction-history request logic moved from
  `makeRequest()` into `validate()`; `makeRequest()` is now a generic `protected function
  makeRequest(string $method, string $uri = '', array $queryParams = []): Response`.
- `Amazon\Validator` error handling: on a non-200 response the exception message is `Amazon API
  error [<http status>]: <description>` with `getCode()` set to the HTTP status. The description is
  taken from the response body's `message` field when present, otherwise a generic text. Note that
  the lookup into `Amazon\APIError::messages()` is keyed by the integer HTTP status while that map
  is keyed by Amazon's string error names, so it never matches; the fixed per-status messages from
  6.0.x ("Invalid receipt ID.", "Invalid developer secret.", ...) are effectively gone.

### Removed
- **Breaking:** the `iTunes\Validator::RESULT_*` constants (`RESULT_OK`, `RESULT_VALID_NO_PURCHASE`,
  `RESULT_APPSTORE_CANNOT_READ`, `RESULT_DATA_MALFORMED`, `RESULT_RECEIPT_NOT_AUTHENTICATED`,
  `RESULT_SHARED_SECRET_NOT_MATCH`, `RESULT_RECEIPT_SERVER_UNAVAILABLE`,
  `RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED`, `RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION`,
  `RESULT_PRODUCTION_RECEIPT_SENT_TO_SANDBOX`, `RESULT_RECEIPT_WITHOUT_PURCHASE`) were removed in
  this minor release; use the equivalents on `iTunes\APIError`.
- **Breaking:** `Amazon\Validator::RESULT_INVALID_RECEIPT`, `RESULT_INVALID_DEVELOPER_SECRET`,
  `RESULT_INVALID_USER_ID` and `RESULT_INTERNAL_ERROR` were removed; `Amazon\APIError` holds string
  error names rather than these HTTP status codes, so there is no direct replacement for the numeric
  values.
- The abstract `protected function makeRequest(): mixed` declaration was dropped from
  `AbstractValidator`; each validator now declares its own `makeRequest()` signature. Only relevant
  if you subclass `AbstractValidator`.

## [6.0.6] - 2025-05-02

### Added
- `Environment::fromString(string $value): Environment` parses `'sandbox'` / `'production'`
  case-insensitively and throws `InvalidArgumentException` for anything else.
- `AppleAppStore\Response::getTransactions()` and `Amazon\Response::getTransactions()` are now
  declared on the concrete classes with `@return array<Transaction>` so static analysers and IDEs
  know the element type; runtime behaviour is unchanged.

### Fixed
- `AppleAppStore\JWT\TokenGenerator::generate()` throws `ValidationException` ("Issuer ID must not
  be empty") instead of producing a JWT with an empty `iss`, and `TokenGenerator::decodeToken('')`
  throws `ValidationException` ("Cannot parse empty JWT payload") instead of a parser error.
- `AppleAppStore\Validator` throws `ValidationException` ("JWT generation failed: Cannot generate a
  token without a signing key.") when constructed with an empty signing key, instead of a low-level
  key error.
- `iTunes\Validator` throws `ValidationException` ("Unable to encode data to iTunes server") if the
  request body cannot be JSON-encoded, instead of sending the string "false".

### Changed
- composer.json: dev dependency `phpstan/phpstan` widened from `^1.11` to `^1.11 || ^2.0`; the
  leftover `extra.google/apiclient-services` entry from the removed Google Play integration was
  deleted. No runtime dependency changes.

## [6.0.4] - 2025-04-19

### Changed
- `AppleAppStore\Validator::validate()` now sends `?sort=DESCENDING` on the `GET
  /inApps/v2/history/{transactionId}` request, so `Response::getTransactions()` returns the newest
  transactions first (the API default is ascending).

## [6.0.2] - 2025-04-19

### Fixed
- `AppleAppStore\ServerNotificationSubtype` could not be autoloaded in 6.0.0: the enum declared
  namespace `ReceiptValidator\AppleAppStore` but lived at
  `src/AppleAppStore/JWT/ServerNotificationSubtype.php`, so any App Store Server Notification V2
  carrying a `subtype` failed with a class-not-found error inside
  `ServerNotification::__construct()`. The file now lives at
  `src/AppleAppStore/ServerNotificationSubtype.php`; the fully qualified name is unchanged.

### Changed
- `AppleAppStore\Transaction` now extends `AbstractTransaction` instead of `AbstractResponse`. As a
  result `getTransactionId()`, `getProductId()` and `getQuantity()` are inherited and now return
  non-nullable `string`, `string` and `int` (previously `?string`, `?string`, `?int`), the
  `setTransactionId()`, `setProductId()` and `setQuantity()` setters became available, and the class
  no longer exposes `AbstractResponse::getTransactions()`. `getEnvironment()` / `setEnvironment()`
  are kept.

## [6.0.0] - 2025-04-19

### Added
- New `ReceiptValidator\AppleAppStore` namespace for Apple's App Store Server API and App Store
  Server Notifications V2 (merged through
  [#192](https://github.com/aporat/store-receipt-validator/pull/192) and
  [#193](https://github.com/aporat/store-receipt-validator/pull/193)):
  - `AppleAppStore\Validator` with `__construct(string $signingKey, string $keyId, string $issuerId,
    string $bundleId, Environment $environment = Environment::PRODUCTION)`, `setTransactionId(string
    $transactionId): self` and `validate(?string $transactionId = null): AppleAppStore\Response`. It
    signs an ES256 JWT with your App Store Connect API key and calls `GET
    /inApps/v2/history/{transactionId}` on `ENDPOINT_PRODUCTION`
    (`https://api.storekit.itunes.apple.com`) or `ENDPOINT_SANDBOX`
    (`https://api.storekit-sandbox.itunes.apple.com`); a missing transaction ID, transport failure,
    non-200 status or JWT generation failure throws `ValidationException`.
  - `AppleAppStore\Response` (`ArrayAccess`) with `getRevision()`, `getBundleId()`,
    `getAppAppleId()`, `hasMore()`, `getSignedTransactions()` plus the inherited
    `getTransactions()`, `getRawData()` and `getEnvironment()`. Each entry of `signedTransactions`
    is decoded and its signature and x5c certificate chain verified before it becomes a
    `Transaction`.
  - `AppleAppStore\Transaction` (`ArrayAccess`) exposing the decoded JWS transaction payload:
    `getOriginalTransactionId()`, `getTransactionId()`, `getWebOrderLineItemId()`, `getBundleId()`,
    `getProductId()`, `getSubscriptionGroupIdentifier()`, `getPurchaseDate()`,
    `getOriginalPurchaseDate()`, `getExpiresDate()`, `getQuantity()`, `getType()`,
    `getAppAccountToken()`, `getInAppOwnershipType()`, `getSignedDate()`, `getRevocationReason()`,
    `getRevocationDate()`, `getIsUpgraded()`, `getOfferType()`, `getOfferIdentifier()`,
    `getStorefront()`, `getStorefrontId()`, `getTransactionReason()`, `getCurrency()`, `getPrice()`,
    `getOfferDiscountType()`, `getAppTransactionId()`, `getOfferPeriod()`.
  - `AppleAppStore\RenewalInfo` (`ArrayAccess`) exposing the decoded JWS renewal-info payload:
    `getAutoRenewProductId()`, `getAutoRenewStatus()`, `getExpirationIntentDate()`,
    `isInBillingRetryPeriod()`, `isUpgraded()`, `getOriginalTransactionId()`,
    `getPriceConsentStatus()`, `getGracePeriodExpiresDate()`, `getRenewalPrice()`, `getCurrency()`,
    `getOfferIdentifier()`, `getOfferType()`, `getOfferDiscountType()`, `getOfferPeriod()`,
    `getAppTransactionId()`, `getAppAccountToken()`, `getEligibleWinBackOfferIds()`,
    `getSignedDate()`, `getRecentSubscriptionStartDate()`, `getRenewalDate()`.
  - `AppleAppStore\ServerNotification` for Server Notifications V2: `__construct(array $data)` takes
    the decoded request body, requires a `signedPayload` key, verifies its signature (throwing
    `ValidationException` otherwise) and exposes `getNotificationType(): ServerNotificationType`,
    `getSubtype(): ?ServerNotificationSubtype`, `getNotificationUUID()`, `getSignedDate()`,
    `getBundleId()`, `getEnvironment()`, `getTransaction(): ?Transaction` and `getRenewalInfo():
    ?RenewalInfo`.
  - String-backed enums `AppleAppStore\ServerNotificationType` (`SUBSCRIBED`,
    `DID_CHANGE_RENEWAL_PREF`, `DID_CHANGE_RENEWAL_STATUS`, `OFFER_REDEEMED`, `DID_RENEW`,
    `EXPIRED`, `DID_FAIL_TO_RENEW`, `GRACE_PERIOD_EXPIRED`, `PRICE_INCREASE`, `REFUND`,
    `REFUND_DECLINED`, `CONSUMPTION_REQUEST`, `RENEWAL_EXTENDED`, `REVOKE`, `TEST`,
    `RENEWAL_EXTENSION`, `REFUND_REVERSED`, `EXTERNAL_PURCHASE_TOKEN`, `ONE_TIME_CHARGE`) and
    `AppleAppStore\ServerNotificationSubtype` (`INITIAL_BUY`, `RESUBSCRIBE`, `DOWNGRADE`, `UPGRADE`,
    `AUTO_RENEW_ENABLED`, `AUTO_RENEW_DISABLED`, `VOLUNTARY`, `BILLING_RETRY`, `PRICE_INCREASE`,
    `GRACE_PERIOD`, `PENDING`, `ACCEPTED`, `BILLING_RECOVERY`, `PRODUCT_NOT_FOR_SALE`, `SUMMARY`,
    `FAILURE`, `UNREPORTED`). In this release the subtype enum's file was misplaced under `JWT/` and
    could not be autoloaded; see 6.0.2.
  - `AppleAppStore\ReceiptUtility` with static `extractTransactionIdFromAppReceipt(string
    $appReceipt): ?string` and `extractTransactionIdFromTransactionReceipt(string
    $transactionReceipt): ?string`, for pulling a transaction ID out of a legacy receipt so it can
    be looked up through the new API.
  - `AppleAppStore\JWT\TokenGenerator` (`generate(): Token`, static `decodeToken(string
    $signedPayload): Token`), `TokenGeneratorConfig` (`forAppStore(TokenIssuer, ?Clock)`,
    `config()`, `issuer()`, `clock()`), `TokenIssuer` (`id()`, `bundle()`, `key()`, `signer()`),
    `TokenKey` (`kid()`, `contents()`, `passphrase()`) and `TokenVerifier::verify(Token): bool`,
    which requires `alg` ES256, a three-certificate `x5c` chain whose intermediate and root SHA-1
    fingerprints match Apple's, a valid chain, and a signature that verifies against the leaf
    certificate.
- `iTunes\ServerNotification` for the legacy (V1) server-to-server notifications: `__construct(array
  $data, ?string $sharedSecret = null)` throws `ValidationException` unless the payload's `password`
  equals the shared secret, then exposes `getNotificationType(): iTunes\ServerNotificationType`,
  `getEnvironment()`, `getAutoRenewProductId()`, `getAutoRenewStatus()`,
  `getAutoRenewStatusChangeDate()`, `getBundleId()`, `getBvrs()`, `getOriginalTransactionId()`,
  `getPassword()`, `getLatestReceipt(): iTunes\Response` and `getPendingRenewalInfo():
  ?iTunes\RenewalInfo`. String-backed enum `iTunes\ServerNotificationType` (`CANCEL`,
  `CONSUMPTION_REQUEST`, `DID_CHANGE_RENEWAL_PREF`, `DID_CHANGE_RENEWAL_STATUS`,
  `DID_FAIL_TO_RENEW`, `DID_RECOVER`, `DID_RENEW`, `INITIAL_BUY`, `INTERACTIVE_RENEWAL`,
  `PRICE_INCREASE_CONSENT`, `REFUND`, `REVOKE`, `TEST`).
- `ReceiptValidator\Environment` enum (`SANDBOX`, `PRODUCTION`), used by every validator, response
  and notification instead of endpoint strings.
- `ReceiptValidator\Exceptions\ValidationException`, the single exception type thrown by all
  validators and parsers.
- Shared base classes: `AbstractValidator` (`getEnvironment()`, `setEnvironment()`, abstract
  `validate()`, and a public `$client` property for injecting a Guzzle client), `AbstractResponse`
  (`getTransactions()`, `getRawData()`, `getEnvironment()`, `setEnvironment()`, abstract `parse()`)
  and `AbstractTransaction` (`getRawData()`, `getQuantity()`/`setQuantity()`,
  `getProductId()`/`setProductId()`, `getTransactionId()`/`setTransactionId()`, abstract `parse()`).
- `iTunes\Transaction::hasExpired(): bool` and `wasCanceled(): bool` convenience checks.
- `Amazon\Transaction::getGracePeriodEndDate()`, `getFreeTrialEndDate()`, `isAutoRenewing()`,
  `getTerm()` and `getTermSku()`, exposing the remaining fields of Amazon's RVS response.

### Changed
- **Breaking:** composer.json: PHP requirement raised from `^8.2` to `^8.3`; `guzzlehttp/guzzle`
  narrowed from `^6.3|^7.0` to `^7.0` (Guzzle 6 dropped); `nesbot/carbon` narrowed from
  `^1.0|^2.0|^3.0` to `^2.72.6|^3.0`; new requirements `lcobucci/jwt ^5.5`, `lcobucci/clock ^3.3`,
  `phpseclib/phpseclib ^3.0` and `ext-openssl`; `google/apiclient ^2.10` and
  `google/apiclient-services ~0.249` removed.
- **Breaking:** `iTunes\Validator` constructor is now `__construct(?string $sharedSecret = null,
  Environment $environment = Environment::PRODUCTION)` (was `__construct(string $endpoint =
  self::ENDPOINT_PRODUCTION)`), and `validate()` is now `validate(?string $receiptData = null):
  iTunes\Response` (was `validate(?string $receipt_data = null, ?string $shared_secret = null):
  ResponseInterface`). `setReceiptData(string $receiptData = '')` no longer accepts `null`. The
  `RESULT_*` status constants moved from `iTunes\ResponseInterface` onto `iTunes\Validator`, with
  `RESULT_VALID_NO_PURCHASE` (2) added.
- **Breaking:** `iTunes\Validator` now throws `ValidationException` for every Apple status other
  than 0 and 21006 (expired subscription) instead of returning a response whose `isValid()` is
  false. When running against production and Apple returns 21007 (sandbox receipt sent to
  production) it transparently retries against the sandbox endpoint and returns a `Response` whose
  `getEnvironment()` is `Environment::SANDBOX`. The `exclude-old-transactions` flag is no longer
  sent (see Removed).
- **Breaking:** the iTunes response hierarchy is collapsed into a single `iTunes\Response` class
  (constructor `(?array $data = [], Environment $environment = Environment::PRODUCTION)`). Compared
  with the old `AbstractResponse`/`ResponseInterface`: `getPurchases()` is now `getTransactions()`
  (returning `iTunes\Transaction[]`), `isSandbox()`/`isProduction()` are replaced by
  `getEnvironment(): Environment`, `parseData()` is `parse()`, and `getBundleId()`/`getAppItemId()`
  now return `?string` instead of `string`. `isRetryable()`, `getLatestReceipt()`,
  `getLatestReceiptInfo()`, `getPendingRenewalInfo()`, `getOriginalPurchaseDate()`,
  `getRequestDate()`, `getReceiptCreationDate()` and `getRawData()` are kept.
- **Breaking:** `iTunes\PurchaseItem` is renamed `iTunes\Transaction` (now extends
  `AbstractTransaction`); `getRawResponse()` is now `getRawData()` and `parseData()` is `parse()`.
  All other getters keep their names.
- **Breaking:** `iTunes\PendingRenewalInfo` is renamed `iTunes\RenewalInfo`; the getters and the
  `EXPIRATION_INTENT_*`, `RETRY_PERIOD_*`, `AUTO_RENEW_*` and `STATUS_*` constants are unchanged.
- **Breaking:** `Amazon\Validator` constructor is now `__construct(string $developerSecret,
  Environment $environment)` (was `__construct(string $endpoint = self::ENDPOINT_PRODUCTION)`).
  `ENDPOINT_SANDBOX` changed from `http://localhost:8080/RVSSandbox/` to
  `https://appstore-sdk.amazon.com/sandbox` and `ENDPOINT_PRODUCTION` from
  `https://appstore-sdk.amazon.com/version/1.0/verifyReceiptId/` to
  `https://appstore-sdk.amazon.com` (the
  `/version/1.0/verifyReceiptId/developer/{secret}/user/{user}/receiptId/{id}` path is built
  internally). `validate(): Amazon\Response` now throws `ValidationException` (code = HTTP status)
  on any non-200 reply instead of returning a response with a result code.
- **Breaking:** `Amazon\PurchaseItem` is renamed `Amazon\Transaction` (extends
  `AbstractTransaction`, implements `ArrayAccess`); `getRawResponse()` is now `getRawData()` and
  `parseJsonResponse()` is `parse()`.
- **Breaking:** `Amazon\Response` now extends `AbstractResponse` with constructor `(?array $data =
  [], Environment $environment = Environment::PRODUCTION)` (was `(int $httpStatusCode = 200, ?array
  $jsonResponse = [])`). `getPurchases()` is now `getTransactions()` (a single
  `Amazon\Transaction`), `parseJsonResponse()` is `parse()`, and the receipt fields are available
  through `getRawData()`.

### Removed
- **Breaking:** Google Play support is gone: `GooglePlay\Validator`, `GooglePlay\Acknowledger`,
  `GooglePlay\AbstractResponse`, `GooglePlay\PurchaseResponse`, `GooglePlay\SubscriptionResponse`
  and `GooglePlay\SubscriptionV2Response` were deleted along with the `google/apiclient`
  dependencies.
- **Breaking:** `iTunes\AbstractResponse`, `iTunes\ProductionResponse`, `iTunes\SandboxResponse`,
  `iTunes\ResponseInterface` and `iTunes\EnvironmentResponseInterface` were removed in favour of
  `iTunes\Response`; with them went `getResultCode()`, `setResultCode()`, `isValid()`,
  `getReceipt()`, `getPurchases()`, `isSandbox()` and `isProduction()`.
- **Breaking:** `iTunes\Validator::getEndpoint()`, `setEndpoint()`, `getExcludeOldTransactions()`,
  `setExcludeOldTransactions()`, `getRequestOptions()` and `setRequestOptions()` were removed; the
  environment is chosen via `Environment`, and custom Guzzle options can only be applied by
  assigning your own client to the public `$client` property.
- **Breaking:** `Amazon\Validator::getEndpoint()`/`setEndpoint()` and `Amazon\Response::RESULT_OK`,
  `RESULT_INVALID_RECEIPT`, `RESULT_INVALID_DEVELOPER_SECRET`, `RESULT_INVALID_USER_ID`,
  `RESULT_INTERNAL_ERROR`, `getResultCode()`, `getReceipt()`, `getPurchases()` and `isValid()` were
  removed (the four error codes live on `Amazon\Validator` as `RESULT_INVALID_RECEIPT`,
  `RESULT_INVALID_DEVELOPER_SECRET`, `RESULT_INVALID_USER_ID`, `RESULT_INTERNAL_ERROR` until 6.1.0).
- **Breaking:** `ReceiptValidator\RunTimeException` was removed; all errors are now
  `ReceiptValidator\Exceptions\ValidationException`.

## [5.0.1] - 2025-03-11

### Fixed
- `iTunes\PendingRenewalInfo::$grace_period_expires_date` and `$is_in_billing_retry_period` now
  default to `null`; in 5.0.0 they were typed properties without a default, so `isInGracePeriod()`
  (and the related getters) threw "must not be accessed before initialization" whenever Apple's
  `pending_renewal_info` entry lacked `grace_period_expires_date_ms` or
  `is_in_billing_retry_period`. Thanks to @mpoiriert
  ([#191](https://github.com/aporat/store-receipt-validator/pull/191))
- Note: the original release notes for 5.0.1 were empty.

## [5.0.0] - 2025-02-27

### Removed
- **Breaking:** Removed the Windows Store validator: `ReceiptValidator\WindowsStore\Validator` and
  `ReceiptValidator\WindowsStore\CacheInterface` are gone, and the `robrichards/xmlseclibs`
  dependency was dropped with them.

### Changed
- **Breaking:** Minimum PHP version raised from `^7.3|^8.0` to `^8.2`.
- **Breaking:** Constructor arguments that used to be optional are now required (still nullable):
  `Amazon\PurchaseItem::__construct(?array $jsonResponse)`,
  `Amazon\Response::parseJsonResponse(?array $jsonResponse)`,
  `iTunes\AbstractResponse::__construct(?array $data)`, `iTunes\PurchaseItem::__construct(?array
  $data)`, `iTunes\PendingRenewalInfo::__construct(?array $data)`. Passing a non-array (e.g. a
  string) to these now throws `TypeError` instead of `RuntimeException('Response must be a scalar
  value')`.
- **Breaking:** `Amazon\Response::__construct(int $httpStatusCode = 200, ?array $jsonResponse = [])`
  — the second parameter's default changed from `null` to `[]`, so `new Response(200)` now parses an
  empty array and `getPurchases()` returns one empty `PurchaseItem` instead of an empty array; pass
  `null` explicitly to keep the old behaviour.
- **Breaking:** Scalar parameter types and `static` return types added across
  `GooglePlay\Validator`: `__construct(AndroidPublisher $service, bool $validationModePurchase =
  true)`, `setPackageName(string)`, `setPurchaseToken(string)`, `setProductId(string)`,
  `setValidationModePurchase(bool)`, `setValidationSubscriptionV2(bool)`; `validatePurchase():
  PurchaseResponse`, `validateSubscription(): SubscriptionResponse`, `validateSubscriptionV2():
  SubscriptionV2Response`, `getPublisherService(): ?AndroidPublisher`.
- **Breaking:** `GooglePlay\Acknowledger::__construct()` now requires `string` for `$packageName`,
  `$productId`, `$purchaseToken` and `$strategy`; `acknowledge()` is declared `: bool`.
- **Breaking:** Return types declared on the Google Play response classes, so a missing field from
  Google now raises `TypeError` instead of returning `null`:
  `AbstractResponse::getDeveloperPayload(): array|string`, `getAcknowledgementState(): int`,
  `getKind(): string`; `PurchaseResponse::getConsumptionState(): int`, `getPurchaseTimeMillis():
  string`, `getDeveloperPayloadElement(string $key): string`, `getPurchaseState(): string`;
  `SubscriptionResponse::getAutoRenewing(): bool`, `getCancelReason(): ?int`, `getCountryCode():
  string`, `getPriceAmountMicros(): string`, `getPriceCurrencyCode(): string`,
  `getStartTimeMillis(): string`, `getExpiryTimeMillis(): int`, `getUserCancellationTimeMillis():
  ?int`, `getPaymentState(): int`, `getExpiresDate(): string`, `getExternalAccountId(): string`.
- `GooglePlay\SubscriptionResponse::getPriceAmountMicros()` is now documented and typed as `string`
  (Google returns the int64 as a string); it was previously documented as `int`.
- `iTunes\PurchaseItem` and `iTunes\PendingRenewalInfo` `ArrayAccess` methods now carry native
  return types (`offsetSet(): void`, `offsetGet(): mixed`, `offsetUnset(): void`, `offsetExists():
  bool`); subclasses overriding them must match.
- All classes now use typed properties (`Amazon\*`, `GooglePlay\*`, `iTunes\*`); subclasses that
  redeclare these protected properties must use compatible types.
- `composer.json`: `phpunit/phpunit` dev dependency raised to `^11.0`; `minimum-stability: dev` with
  `prefer-stable: true` added (affects the repository only, not consumers).
- Note: the original release notes said only "PHP 8.2 minimum required, Removed Windows Store
  validator, Code style improvements"; the signature/return-type changes above are user-visible and
  the `Amazon\Response` default change is a behaviour change.

## [4.4.3] - 2024-03-06

### Added
- `iTunes\Validator` now honours a `base_uri` key passed via `setRequestOptions()`; previously
  `getClientConfig()` always overwrote it with the selected Apple endpoint. This lets you point the
  validator at a mock server. Thanks to @aliozkan (Teknasyon-Teknoloji)
  ([#181](https://github.com/aporat/store-receipt-validator/pull/181))

## [4.4.2] - 2024-02-02

### Changed
- `composer.json`: `nesbot/carbon` constraint widened from `^1.0|^2.0` to `^1.0|^2.0|^3.0`. Thanks
  to @gjuric ([#179](https://github.com/aporat/store-receipt-validator/pull/179))
- Note: the original release notes said "PHP 8.2, 8.4 were added as allowed dependencies"; the diff
  only adds PHP 8.2 and 8.3 to the CI test matrix
  ([#177](https://github.com/aporat/store-receipt-validator/pull/177)) and the `php` constraint in
  `composer.json` is unchanged (`^7.3|^8.0.0`, which already allowed every 8.x).

## [4.4.1] - 2023-04-21

### Fixed
- `GooglePlay\Validator::setValidationSubscriptionV2()` now sets the `validationSubscriptionV2`
  flag; in 4.4.0 it mistakenly wrote to `validationModePurchase`, so
  `setValidationSubscriptionV2(true)` actually switched the validator into purchase mode. Thanks to
  @yankers ([#172](https://github.com/aporat/store-receipt-validator/pull/172))
- `GooglePlay\Validator::validate()` now checks the `validationSubscriptionV2` flag; in 4.4.0 the
  `elseif` condition called `validateSubscriptionV2()` itself, so any non-purchase validation
  performed a Subscriptions V2 API call (twice when it succeeded) regardless of the flag.
  ([#172](https://github.com/aporat/store-receipt-validator/pull/172))

## [4.4.0] - 2023-04-20

### Added
- `GooglePlay\Validator::validateSubscriptionV2()` and
  `GooglePlay\Validator::setValidationSubscriptionV2(bool)` call Google's
  `purchases.subscriptionsv2.get` endpoint; the result is wrapped in the new
  `GooglePlay\SubscriptionV2Response` class, which exposes the raw
  `Google\Service\AndroidPublisher\SubscriptionPurchaseV2` via `getRawResponse()` and adds no
  V2-specific getters of its own. Thanks to @yankers
  ([#171](https://github.com/aporat/store-receipt-validator/pull/171))

### Changed
- `composer.json`: new explicit dependency `google/apiclient-services: ~0.249` (needed for the
  `SubscriptionPurchaseV2` model and the `purchases_subscriptionsv2` resource).
- Note: as shipped in 4.4.0, `setValidationSubscriptionV2()` and `validate()` were wired incorrectly
  (see 4.4.1), so the V2 flow only works correctly from 4.4.1 onward; calling
  `validateSubscriptionV2()` directly worked in 4.4.0.

## [4.3.0] - 2022-11-10

### Added
- `GooglePlay\SubscriptionResponse::getExternalAccountId()` returns the subscription's
  `externalAccountId`. Thanks to @marijap93
  ([#165](https://github.com/aporat/store-receipt-validator/pull/165))
- Note: the original release notes spelled the method `getExternalAccounId()` and the commit subject
  says `addExternalAccountId`; the method actually added is `getExternalAccountId()`.

### Fixed
- `GooglePlay\PurchaseResponse::__construct()` only calls `json_decode()` on `developerPayload` when
  the field is set, avoiding the PHP 8.1 "passing null to parameter #1 ($json) of type string is
  deprecated" notice for purchases with no developer payload. Thanks to @sica07
  ([#168](https://github.com/aporat/store-receipt-validator/pull/168))

## [4.2.0] - 2021-12-29

### Fixed
- Added `#[\ReturnTypeWillChange]` to the `ArrayAccess` methods (`offsetSet`, `offsetGet`,
  `offsetUnset`, `offsetExists`) of `iTunes\PurchaseItem` and `iTunes\PendingRenewalInfo`, silencing
  the PHP 8.1 "Return type should be compatible" deprecation notices. Thanks to @srjlewis
  ([#154](https://github.com/aporat/store-receipt-validator/pull/154),
  [#155](https://github.com/aporat/store-receipt-validator/pull/155))

## [4.1.0] - 2021-10-13

### Changed
- **Breaking:** Minimum PHP version raised from `^7.2.5` to `^7.3` (PHP 7.2 is end of life).
  ([#144](https://github.com/aporat/store-receipt-validator/pull/144))
- **Breaking:** `google/apiclient` constraint raised from `^2.0` to `^2.10`.
  ([#153](https://github.com/aporat/store-receipt-validator/pull/153))
- Google Play classes migrated from the legacy underscore class names to the namespaced ones:
  `GooglePlay\Validator::__construct()` and `GooglePlay\Acknowledger::__construct()` now type-hint
  `Google\Service\AndroidPublisher` instead of `Google_Service_AndroidPublisher`, and the
  acknowledge requests use `Google\Service\AndroidPublisher\ProductPurchasesAcknowledgeRequest` /
  `SubscriptionPurchasesAcknowledgeRequest`. The legacy names remain class aliases in
  `google/apiclient-services` >= 0.200, so existing code constructing
  `Google_Service_AndroidPublisher` keeps working. Thanks to @yyeltsyn
  ([#153](https://github.com/aporat/store-receipt-validator/pull/153))
- **Breaking:** Native return types added: `Amazon\Response::getReceipt(): array`,
  `Amazon\Response::getPurchases(): array`, `Amazon\Validator::validate(): Response`,
  `iTunes\AbstractResponse::getPurchases(): array`, `getLatestReceiptInfo(): array`,
  `getPendingRenewalInfo(): array` (and the same three on `iTunes\ResponseInterface`, so custom
  implementations must declare them), `iTunes\PendingRenewalInfo::isInGracePeriod(): bool` and
  `offsetExists($key): bool`.
- `composer.json`: `phpunit/phpunit` dev dependency raised from `^8.0` to `^9.0`.
- Note: the original release notes mention only the Google namespace migration and the
  `google/apiclient:^2.10` requirement; the PHP 7.2 drop and the new return types were not listed.

## [4.0.3] - 2020-12-02

### Fixed
- `iTunes\Validator::setEndpoint()` now validates the endpoint and throws
  `\InvalidArgumentException` for anything other than `ENDPOINT_PRODUCTION` or `ENDPOINT_SANDBOX`;
  previously only the constructor validated it, so an invalid endpoint could be set after
  construction. The constructor now delegates to `setEndpoint()`. Thanks to @pwellingelastique
  ([#142](https://github.com/aporat/store-receipt-validator/pull/142))

## [4.0.2] - 2020-12-02

### Added
- PHP 8.0 support: composer `php` constraint widened from `^7.2.5` to `^7.2.5|^8.0.0`. No source
  changes were needed. Thanks to @mewm
  ([#141](https://github.com/aporat/store-receipt-validator/pull/141))

### Changed
- README documents how to shrink the `google/apiclient-services` dependency via
  `Google_Task_Composer::cleanup` (documentation only). Thanks to @passions-app
  ([#129](https://github.com/aporat/store-receipt-validator/pull/129))

## [4.0.1] - 2020-08-18

### Fixed
- `Amazon\Validator::validate()` requested a leading-slash path (`/developer/...`), which made
  Guzzle discard the `/version/1.0/verifyReceiptId/` path of the base URI, so every Amazon request
  in 4.0.0 went to the wrong URL. The trailing slash was restored on
  `Amazon\Validator::ENDPOINT_PRODUCTION` and `ENDPOINT_SANDBOX` and the request path is relative
  again. Thanks to @calbro7 ([#126](https://github.com/aporat/store-receipt-validator/pull/126))

## [4.0.0] - 2020-08-04

### Changed
- **Breaking:** Minimum PHP version raised from `^7.1` to `^7.2.5`. Thanks to @leemcd56
  ([#125](https://github.com/aporat/store-receipt-validator/pull/125))
- Guzzle 7 is now supported: `guzzlehttp/guzzle` constraint changed from `^6.3` to `^6.3|^7.0`.
  ([#125](https://github.com/aporat/store-receipt-validator/pull/125))
- **Breaking:** `iTunes\Validator::ENDPOINT_PRODUCTION` and `ENDPOINT_SANDBOX` no longer include the
  `/verifyReceipt` path (now `https://buy.itunes.apple.com` and `https://sandbox.itunes.apple.com`);
  the path is passed on the POST request instead (needed because Guzzle 7 rejects a `null` request
  URI). Code that compares `getEndpoint()` against the old full URLs or passes the full URL to the
  constructor will break.
- **Breaking:** `iTunes\Validator::setReceiptData()` and `setSharedSecret()` now declare `?string`
  parameter types.
- **Breaking:** `Amazon\Validator::setUserId()`, `setReceiptId()` and `setDeveloperSecret()` now
  declare `?string` parameter types, and `getDeveloperSecret()` declares a `?string` return type.
- **Breaking:** `Amazon\PurchaseItem` getters gained return types: `getRawResponse(): ?array`,
  `getQuantity(): int`, `getProductId(): string`, `getTransactionId(): string`, `getPurchaseDate():
  Carbon`, `getCancellationDate(): ?Carbon`, `getRenewalDate(): ?Carbon`.
- **Breaking:** Protected properties in `Amazon\Validator`, `Amazon\Response` and
  `Amazon\PurchaseItem` lost their leading underscore (e.g. `$_endpoint` is now `$endpoint`,
  `$_response` is now `$raw_data`); subclasses referencing the old names must be updated. The unused
  `Amazon\Validator::$_product_id` property was removed.
- Note: the original release notes said "PHP 7.2+ is now required"; the actual composer constraint
  is `^7.2.5`. They did not mention the endpoint-constant or type-hint changes.

### Fixed
- Amazon endpoint constants lost their trailing slash and the request path gained a leading slash in
  this release, which broke Amazon validation; see 4.0.1.

## [3.5.0] - 2020-08-03

### Added
- `GooglePlay\Acknowledger` constructor accepts a fifth `$strategy` argument with new constants
  `ACKNOWLEDGE_STRATEGY_EXPLICIT` (default; calls acknowledge unconditionally, as before) and
  `ACKNOWLEDGE_STRATEGY_IMPLICIT` (first fetches the purchase and only acknowledges if
  `acknowledgementState` is not already done). An unknown strategy throws
  `ReceiptValidator\RunTimeException`. Thanks to @passions-app
  ([#110](https://github.com/aporat/store-receipt-validator/pull/110))

### Changed
- **Breaking:** `robrichards/xmlseclibs` constraint tightened from `^2.0|^3.0` to `^3.0.4` (drops
  xmlseclibs 2.x) to clear security-advisory warnings. Other constraints were rewritten from `~` to
  `^` form (`php: ^7.1`, `guzzlehttp/guzzle: ^6.3`, `google/apiclient: ^2.0`) with no effective
  change.
- `GooglePlay\Acknowledger::acknowledge()` now re-throws wrapped exceptions with `$e->getCode()` as
  the message instead of `$e->getMessage()`, so the original error text is lost (looks
  unintentional). ([#110](https://github.com/aporat/store-receipt-validator/pull/110))
- Note: the original release notes credit @Orkin; the merge commit shows the PR came from the
  `passions-app` fork (author Florent Blaison).

## [3.4.1] - 2020-03-31

### Fixed
- `GooglePlay\Acknowledger::acknowledge()` passed the caught exception as the second (`$code`)
  argument of `\RuntimeException`, which is a `TypeError`; it now passes `$e->getCode()` and the
  previous exception correctly.

## [3.4.0] - 2019-11-19

### Added
- New `GooglePlay\Acknowledger` class (`__construct(\Google_Service_AndroidPublisher $service,
  $packageName, $productId, $purchaseToken)`, `acknowledge(string $type = self::SUBSCRIPTION, string
  $developerPayload = ''): bool`) with constants `SUBSCRIPTION` and `PRODUCT`, for acknowledging
  purchases as required by Google Play Billing Library v2. Failures are wrapped in
  `\RuntimeException`. Thanks to @passions-app
  ([#108](https://github.com/aporat/store-receipt-validator/pull/108))
- `GooglePlay\AbstractResponse::getAcknowledgementState(): int` and `isAcknowledged(): bool`, plus
  constants `ACKNOWLEDGEMENT_STATE_YET_TO_BE = 0` and `ACKNOWLEDGEMENT_STATE_DONE = 1`, available on
  both `PurchaseResponse` and `SubscriptionResponse`.
  ([#108](https://github.com/aporat/store-receipt-validator/pull/108))
- Note: the original release notes credit @Orkin; the merge commit shows the PR came from the
  `passions-app` fork (author Florent Blaison).

## [3.3.0] - 2019-10-19

### Added
- `iTunes\PendingRenewalInfo::getGracePeriodExpiresDate(): ?Carbon`, parsed from
  `grace_period_expires_date_ms`. Thanks to @Teknasyon-Teknoloji
  ([#106](https://github.com/aporat/store-receipt-validator/pull/106))
- `iTunes\PendingRenewalInfo::isInGracePeriod(): bool`, true when the subscription is in the billing
  retry period and the grace period expiry is in the future.
  ([#106](https://github.com/aporat/store-receipt-validator/pull/106))
- `iTunes\Validator::setRequestOptions(array $options): self` and `getRequestOptions(): array` to
  pass extra Guzzle client options (e.g. timeouts, proxies); they are merged into the client config
  under the `base_uri`. ([#106](https://github.com/aporat/store-receipt-validator/pull/106))
- Note: the original release notes credit @0hr; the merge commit shows the PR came from the
  `Teknasyon-Teknoloji` fork (author Harun Pekacar).

## [3.2.0] - 2019-05-29

### Added
- `iTunes\PurchaseItem::getPromotionalOfferId(): ?string`, parsed from `promotional_offer_id`.
  Thanks to @Stafox ([#98](https://github.com/aporat/store-receipt-validator/pull/98))
- Carbon 2 support: `nesbot/carbon` constraint changed from `~1` to `^1.0|^2.0`.

## [3.1.0] - 2019-02-27

### Fixed
- `iTunes\PurchaseItem::getWebOrderLineItemId()` return type changed from `string` to `?string`; it
  previously raised a `TypeError` for purchases without `web_order_line_item_id` (non-subscription
  items). Thanks to @lancasterSano
  ([#95](https://github.com/aporat/store-receipt-validator/pull/95))
- `iTunes\ResponseInterface::getLatestReceipt()` and `AbstractResponse::getLatestReceipt()` return
  type changed from `string` to `?string`; it previously raised a `TypeError` when the response had
  no `latest_receipt`. Thanks to @Stafox
  ([#90](https://github.com/aporat/store-receipt-validator/pull/90))

## [3.0.0] - 2018-12-26

### Added
- `iTunes\ResponseInterface` (declares all response getters and the `RESULT_*` constants) and
  `iTunes\EnvironmentResponseInterface` (`isSandbox(): bool`, `isProduction(): bool`). Thanks to
  @Stafox ([#87](https://github.com/aporat/store-receipt-validator/pull/87))
- `iTunes\ProductionResponse` and `iTunes\SandboxResponse`, both extending the new
  `iTunes\AbstractResponse`; `Validator::validate()` returns the one matching the environment that
  actually answered, so a 21007 sandbox retry yields a `SandboxResponse`.
  ([#87](https://github.com/aporat/store-receipt-validator/pull/87))
- `iTunes\ResponseInterface::isRetryable(): bool`, true when Apple's response contains the
  `is-retryable` key (status codes 21100-21199). Thanks to @amasok
  ([#84](https://github.com/aporat/store-receipt-validator/pull/84))
- New constant `iTunes\ResponseInterface::RESULT_RECEIPT_WITHOUT_PURCHASE = 21010`.
  ([#87](https://github.com/aporat/store-receipt-validator/pull/87))
- `iTunes\Validator::getClientConfig(): array` (protected) so subclasses can override the Guzzle
  client configuration; the sandbox retry client now reuses this config instead of a bare
  `base_uri`. ([#87](https://github.com/aporat/store-receipt-validator/pull/87))

### Changed
- **Breaking:** `iTunes\Response` was removed and replaced by the abstract `iTunes\AbstractResponse`
  plus `ProductionResponse`/`SandboxResponse`. Code that instantiates `new Response(...)` or
  type-hints `Response` must switch to `ResponseInterface` (or one of the concrete classes).
  ([#87](https://github.com/aporat/store-receipt-validator/pull/87))
- **Breaking:** The `RESULT_*` constants moved from `iTunes\Response` to `iTunes\ResponseInterface`
  (e.g. `Response::RESULT_OK` is now `ResponseInterface::RESULT_OK`).
  ([#87](https://github.com/aporat/store-receipt-validator/pull/87))
- **Breaking:** `iTunes\Validator::validate()` return type changed from `Response` to
  `ResponseInterface`. ([#87](https://github.com/aporat/store-receipt-validator/pull/87))
- **Breaking:** `iTunes\AbstractResponse::setResultCode(int $code)` now returns `void` instead of
  `self`, so it can no longer be chained.
  ([#87](https://github.com/aporat/store-receipt-validator/pull/87))
- **Breaking:** `isValid()` on iTunes responses now also returns `true` for status 21006
  (`RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED`), not only for 0; code relying on `isValid()` to mean
  "active subscription" must also check the result code.
  ([#87](https://github.com/aporat/store-receipt-validator/pull/87))
- `iTunes\AbstractResponse::getRawData()` now declares a `?array` return type.
  ([#87](https://github.com/aporat/store-receipt-validator/pull/87))
- `iTunes\PurchaseItem` casts `quantity` to `int` and parses
  `is_trial_period`/`is_in_intro_offer_period` with `FILTER_VALIDATE_BOOLEAN` instead of comparing
  to the string `"true"`. ([#87](https://github.com/aporat/store-receipt-validator/pull/87))
- `iTunes\Validator`: the private `encodeRequest()` became protected `prepareRequestData()`, and the
  sandbox retry is now recursive through the same request path (a non-200 status still throws
  `RunTimeException`). ([#87](https://github.com/aporat/store-receipt-validator/pull/87))
- Amazon, GooglePlay and WindowsStore sources were reformatted (PSR-2 indentation) with no
  functional change.
- Note: PR #83 (@sumbria, "Set Line Item Optional") changed
  `iTunes\PurchaseItem::getWebOrderLineItemId()` to return `?string`, but the subsequent refactor in
  #87 reverted it to `string` in the tagged 3.0.0; the nullable return only shipped in 3.1.0.

## [2.2.0] - 2018-11-07

### Added
- `iTunes\Response::getAppItemId()`, `getOriginalPurchaseDate()`, `getRequestDate()` and
  `getReceiptCreationDate()` expose the `app_item_id`, `original_purchase_date_ms`,
  `request_date_ms` and `receipt_creation_date_ms` receipt fields (dates as `Carbon`).
- `iTunes\Response::getRawData()` returns the decoded JSON array the response was built from. Thanks
  to @amasok ([#82](https://github.com/aporat/store-receipt-validator/pull/82))
- `iTunes\PurchaseItem::isTrialPeriod()` and `isInIntroOfferPeriod()` read the `is_trial_period` and
  `is_in_intro_offer_period` fields.
- `iTunes\Response::getLatestReceiptInfo()` and `getPendingRenewalInfo()` now return an empty array
  instead of `null` when the receipt has no such data.

### Changed
- **Breaking:** composer now requires PHP >= 7.1 (was 7.0), `ext-json`, and `guzzlehttp/guzzle` ~6.3
  (was ~6.2).
- **Breaking:** `iTunes\Response::parseJsonResponse()` and
  `iTunes\PurchaseItem::parseJsonResponse()` were renamed to `parseData()`;
  `iTunes\PendingRenewalInfo` gained a public `parseData()` as well.
- **Breaking:** `iTunes\PendingRenewalInfo::getIsInBillingRetryPeriod()` was renamed to
  `isInBillingRetryPeriod()`, and `getAutoRenewStatus()` now returns `bool` instead of `int`.
- **Breaking:** `iTunes\Validator::__construct()` throws `\InvalidArgumentException` instead of
  `ReceiptValidator\RunTimeException` for an unknown endpoint.
- **Breaking:** `iTunes\Response`, `iTunes\PurchaseItem` and `iTunes\PendingRenewalInfo`
  constructors are typed `?array $data = null` and always parse; constructing one with `null` now
  throws `RunTimeException` ("Response must be a scalar value") instead of producing an empty
  object.
- `iTunes\Validator::validate(?string $receipt_data = null, ?string $shared_secret = null):
  Response` and most iTunes getters gained scalar/nullable return types (for example
  `getResultCode(): int`, `getBundleId(): string`, `getPurchaseDate(): ?Carbon`).
  `getLatestReceipt(): string` and `getBundleId(): string` are non-nullable, so a receipt lacking
  those fields will raise a `TypeError`.
- `Amazon\Validator::setEndpoint()` is now explicitly `public` (it had no visibility keyword).
  Thanks to @gsingh1 ([#75](https://github.com/aporat/store-receipt-validator/pull/75))

### Fixed
- `iTunes\PurchaseItem` trial/intro-offer flags are compared against the string `"true"` instead of
  `boolval()`, which returned `true` for the string `"false"`. Thanks to @cumhuronat
  ([#77](https://github.com/aporat/store-receipt-validator/pull/77))
- `Amazon\PurchaseItem::getRenewalDate()` now uses `Carbon::createFromTimestampUTC()` on the
  millisecond value divided by 1000; 2.1.0 passed that seconds value to `createFromTimestampMs()`,
  yielding a date near 1970.
- All millisecond-to-`Carbon` conversions (iTunes and Amazon) cast the rounded timestamp to `int`
  before calling Carbon.

## [2.1.0] - 2018-04-13

### Added
- `iTunes\PendingRenewalInfo` class with `getProductId()`, `getAutoRenewProductId()`,
  `getAutoRenewStatus()`, `getOriginalTransactionId()`, `getExpirationIntent()`,
  `getIsInBillingRetryPeriod()`, `getStatus()` (returns
  `STATUS_ACTIVE`/`STATUS_PENDING`/`STATUS_EXPIRED`), `EXPIRATION_INTENT_*` constants, and
  `ArrayAccess` to the raw fields. Thanks to @leesherwood
  ([#67](https://github.com/aporat/store-receipt-validator/pull/67))
- `iTunes\PurchaseItem` now implements `ArrayAccess` over the raw response fields; `offsetSet()`
  re-parses the item. Thanks to @leesherwood
  ([#66](https://github.com/aporat/store-receipt-validator/pull/66))
- `Amazon\Validator::getEndpoint()` and `setEndpoint(string $endpoint)`. Thanks to @gsingh1
  ([#69](https://github.com/aporat/store-receipt-validator/pull/69))
- `Amazon\PurchaseItem::getRenewalDate()` reads the `renewalDate` field as `Carbon`. Thanks to
  @gsingh1 ([#70](https://github.com/aporat/store-receipt-validator/pull/70))

### Changed
- **Breaking:** `iTunes\Response::getLatestReceiptInfo()` returns `iTunes\PurchaseItem[]` sorted by
  purchase date descending instead of the raw `latest_receipt_info` arrays. Thanks to @leesherwood
  ([#66](https://github.com/aporat/store-receipt-validator/pull/66))
- **Breaking:** `iTunes\Response::getPendingRenewalInfo()` returns `iTunes\PendingRenewalInfo[]`
  instead of the raw `pending_renewal_info` arrays. Thanks to @leesherwood
  ([#67](https://github.com/aporat/store-receipt-validator/pull/67))

## [2.0.6] - 2018-02-09

### Added
- `iTunes\Validator::setExcludeOldTransactions(bool $exclude)` and `getExcludeOldTransactions()`;
  the `exclude-old-transactions` flag is now always sent in the verifyReceipt request body (default
  `false`). Thanks to @leesherwood
  ([#65](https://github.com/aporat/store-receipt-validator/pull/65))

### Changed
- **Breaking:** composer now requires PHP >= 7.0 (was 5.5); `phpunit` dev dependency moved to ^6.0.
- **Breaking:** `iTunes\Validator::__construct(string $endpoint)` and
  `Amazon\Validator::__construct(string $endpoint)` gained a `string` type hint, and
  `setEndpoint(string $endpoint)` likewise, so passing `null` now raises a `TypeError`.
- Return types declared across the API: `isValid(): bool`, `getResultCode(): int` (Amazon),
  `parseJsonResponse(): self`, `getEndpoint(): string`, and `self` on fluent setters (`setUserId`,
  `setReceiptId`, `setDeveloperSecret`, `setReceiptData`, `setSharedSecret`).

## [2.0.4] - 2017-11-21

### Changed
- composer now accepts `robrichards/xmlseclibs` `^2.0|^3.0`. Thanks to @sbacelic
  ([#63](https://github.com/aporat/store-receipt-validator/pull/63))

### Fixed
- `iTunes\PurchaseItem::getExpiresDate()` falls back to a numeric `expires_date` field (treated as
  milliseconds) when `expires_date_ms` is absent. Thanks to @renekliment
  ([#60](https://github.com/aporat/store-receipt-validator/pull/60))

## [2.0.2] - 2017-09-18

### Added
- `iTunes\Response::getPendingRenewalInfo()` returns the raw `pending_renewal_info` array from the
  verifyReceipt response. Thanks to @nielsmouthaan
  ([#59](https://github.com/aporat/store-receipt-validator/pull/59))

## [2.0.0] - 2017-04-19

### Added
- `iTunes\PurchaseItem` class with `getQuantity()`, `getProductId()`, `getWebOrderLineItemId()`,
  `getTransactionId()`, `getOriginalTransactionId()`, `getPurchaseDate()`,
  `getOriginalPurchaseDate()`, `getExpiresDate()`, `getCancellationDate()` (dates as `Carbon`) and
  `getRawResponse()`. The raw-data accessor was originally named `getData()` and read an undefined
  property; fixed before release. Thanks to @tehmaestro
  ([#41](https://github.com/aporat/store-receipt-validator/pull/41))
- `Amazon\PurchaseItem` class with `getQuantity()`, `getProductId()`, `getTransactionId()`,
  `getPurchaseDate()`, `getCancellationDate()` and `getRawResponse()`, and
  `Amazon\Response::getPurchases()` returning one such item.
- `GooglePlay\Validator::__construct(\Google_Service_AndroidPublisher $service,
  $validationModePurchase = true)`, `setValidationModePurchase()`, and `validate()` which dispatches
  to `validatePurchase()` or `validateSubscription()`. Thanks to @stanimir-kukudov
  ([#39](https://github.com/aporat/store-receipt-validator/pull/39))
- `GooglePlay\Validator::getPublisherService()` exposes the underlying
  `\Google_Service_AndroidPublisher` for other calls (voided purchases, refunds, revokes). Thanks to
  @Stafox ([#49](https://github.com/aporat/store-receipt-validator/pull/49))
- `GooglePlay\SubscriptionResponse::getExpiryTimeMillis()`, `getUserCancellationTimeMillis()`,
  `getPaymentState()`, and `GooglePlay\AbstractResponse::getRawResponse()`. Thanks to
  @stanimir-kukudov and @Stafox ([#39](https://github.com/aporat/store-receipt-validator/pull/39),
  [#49](https://github.com/aporat/store-receipt-validator/pull/49))

### Changed
- **Breaking:** `iTunes\Response::getPurchases()` returns `iTunes\PurchaseItem[]` instead of raw
  `in_app` arrays (both iOS 7+ and legacy receipt formats).
- **Breaking:** `iTunes\Response::parseJsonResponse()` no longer takes an argument; the JSON array
  is passed to the constructor only.
- **Breaking:** `GooglePlay\AbstractResponse::getConsumptionState()`, `getDeveloperPayloadElement()`
  and `getPurchaseState()` moved to `GooglePlay\PurchaseResponse`; they are no longer available on
  `SubscriptionResponse`. `AbstractResponse::getDeveloperPayload()` now returns the Google object's
  raw payload string, while `PurchaseResponse::getDeveloperPayload()` keeps returning the
  JSON-decoded array. ([#39](https://github.com/aporat/store-receipt-validator/pull/39))
- `GooglePlay\SubscriptionResponse` getters now call the
  `Google_Service_AndroidPublisher_SubscriptionPurchase` accessor methods, and `getAutoRenewing()`
  is cast to `bool`. ([#49](https://github.com/aporat/store-receipt-validator/pull/49))
- composer: added `nesbot/carbon` ~1; PHP minimum lowered back to >= 5.5 (1.5.0 had raised it to
  5.6); `homepage` and `support` restored to the aporat repository (1.5.0 pointed them at a fork).
  ([#49](https://github.com/aporat/store-receipt-validator/pull/49))

### Deprecated
- `GooglePlay\SubscriptionResponse::getExpiresDate()` is deprecated in favour of
  `getExpiryTimeMillis()`; it was introduced and deprecated within this release.
  ([#49](https://github.com/aporat/store-receipt-validator/pull/49))

## [1.5.0] - 2016-12-14

### Changed
- **Breaking:** `google/apiclient` requirement moved from ~1.1 to ~2.0; `guzzlehttp/guzzle` to ~6.2;
  PHP minimum raised to >= 5.6. Thanks to @stanimir-kukudov
  ([#35](https://github.com/aporat/store-receipt-validator/pull/35))
- **Breaking:** `GooglePlay\Validator::__construct()` now takes a configured
  `\Google_Service_AndroidPublisher` instead of an options array with
  `client_id`/`client_secret`/`refresh_token`; the library no longer builds a `Google_Client` or
  caches access tokens in the temp directory.
- **Breaking:** `GooglePlay\Validator::validate()`, `setPurchaseType()` and the
  `TYPE_PURCHASE`/`TYPE_SUBSCRIPTION` constants were replaced by `validatePurchase()` returning
  `GooglePlay\PurchaseResponse` and `validateSubscription()` returning
  `GooglePlay\SubscriptionResponse`, instead of raw Google API objects.
- composer `homepage` and `support` fields point to the contributor's fork
  (`stanimir-kukudov/store-receipt-validator`) in this release.

### Added
- `GooglePlay\AbstractResponse` (`getConsumptionState()`, `getDeveloperPayload()`,
  `getDeveloperPayloadElement($key)`, `getKind()`, `getPurchaseState()`, `CONSUMPTION_STATE_*` and
  `PURCHASE_STATE_*` constants), `GooglePlay\PurchaseResponse::getPurchaseTimeMillis()`, and
  `GooglePlay\SubscriptionResponse` (`getAutoRenewing()`, `getCancelReason()`, `getCountryCode()`,
  `getPriceAmountMicros()`, `getPriceCurrencyCode()`, `getStartTimeMillis()`).

### Removed
- **Breaking:** `GooglePlay\AbstractValidator` and `GooglePlay\ServiceAccountValidator` (added in
  1.4.0) were removed; service-account auth is now done by configuring the `Google_Client` yourself
  and passing the publisher service in.

## [1.4.1] - 2016-05-11

### Fixed
- `GooglePlay\ServiceAccountValidator` now passes the contents of the file at `p12_key_path` to
  `Google_Auth_AssertionCredentials` instead of the path string, so service-account validation
  actually authenticates. Thanks to @ball00n-
  ([#29](https://github.com/aporat/store-receipt-validator/pull/29))

## [1.4.0] - 2016-04-28

### Added
- `GooglePlay\ServiceAccountValidator` authenticates with a Google service account (`client_email`
  and `p12_key_path` options) instead of OAuth client credentials.
- `GooglePlay\AbstractValidator` extracted as the shared base for `GooglePlay\Validator` and
  `ServiceAccountValidator`, holding the setters, `TYPE_*` constants and `validate()`.

## [1.3.1] - 2016-03-24

### Changed
- Autoloading switched from PSR-0 to PSR-4 and source files moved from `src/ReceiptValidator/` to
  `src/`; class names and the `ReceiptValidator\` namespace are unchanged.
- `iTunes\Response::getLatestReceipt()` returns `null` instead of an empty array when the response
  has no `latest_receipt`; docblock return types for `getBundleId()`, `getLatestReceipt()` and
  `getLatestReceiptInfo()` corrected. Thanks to @kernio
  ([#26](https://github.com/aporat/store-receipt-validator/pull/26))

## [1.3.0] - 2015-12-20

### Changed
- **Breaking:** composer now requires PHP >= 5.5 (was 5.3) and `guzzlehttp/guzzle` ~6.1 instead of
  `guzzle/guzzle` ~3.8; the `satooshi/php-coveralls` dev dependency was dropped.
- **Breaking:** `iTunes\Validator::getIStoreSharedSecret()` and `setIStoreSharedSecret()` renamed to
  `getSharedSecret()` and `setSharedSecret()`; the second parameter of `validate($receiptData,
  $sharedSecret)` was renamed accordingly.
- `Amazon\Validator::validate()` now catches Guzzle `RequestException`: a non-2xx reply is wrapped
  in `Amazon\Response` with its status code and body, and a failure with no HTTP response returns a
  `Response` with `RESULT_INVALID_RECEIPT`.

### Added
- `Amazon\Validator::getDeveloperSecret()`.

## [1.2.1] - 2015-11-20

### Security
- `iTunes\Validator::validate()` now verifies Apple's TLS certificate: the `'verify' => false`
  Guzzle option was removed from both the primary request and the automatic sandbox retry.
  Certificate verification had been disabled since 1.0.0, leaving receipt validation open to
  man-in-the-middle attacks. Thanks to @lstrojny
  ([#21](https://github.com/aporat/store-receipt-validator/pull/21))

## [1.2.0] - 2015-10-15

### Added
- New `WindowsStore\Validator` class with `__construct(CacheInterface $cache = null)` and
  `validate($receipt)`, which returns `bool`. It parses the receipt XML, downloads the Microsoft
  signing certificate named by the receipt's `CertificateId` from `go.microsoft.com`, and verifies
  the XML signature with `robrichards/xmlseclibs`; it throws `ReceiptValidator\RunTimeException` on
  invalid XML, a missing `CertificateId`, or a missing signature/key. Thanks to @MaartenStaa
  ([#20](https://github.com/aporat/store-receipt-validator/pull/20))
- New `WindowsStore\CacheInterface` (`get($key)`, `put($key, $value, $minutes)`) so callers can
  cache downloaded certificates; when a cache is supplied, certificates are stored for 3600 minutes
  under `store-receipt-validate.windowsstore.<CertificateId>`.
  ([#20](https://github.com/aporat/store-receipt-validator/pull/20))

### Changed
- composer: new dependency `robrichards/xmlseclibs: ^2.0`.
  ([#20](https://github.com/aporat/store-receipt-validator/pull/20))

## [1.1.1] - 2015-09-17

### Changed
- composer: minimum PHP lowered from `>=5.4` to `>=5.3`; the short array syntax in
  `Amazon\Response`, `GooglePlay\Validator` and `iTunes\Response` was replaced with `array()` so the
  library actually runs on 5.3. Thanks to @AlexeyKupershtokh
  ([#17](https://github.com/aporat/store-receipt-validator/pull/17))
- Note: the GitHub release attached to tag 1.1.1 is titled "1.1.0"; its body is empty. PRs
  [#16](https://github.com/aporat/store-receipt-validator/pull/16) (README/example receipt fix) and
  [#19](https://github.com/aporat/store-receipt-validator/pull/19) (CI matrix) contain no library
  changes.

## [1.1.0] - 2015-04-19

### Added
- New `Amazon\Validator` class for Amazon's Receipt Verification Service: constants
  `ENDPOINT_PRODUCTION` (`https://appstore-sdk.amazon.com/version/1.0/verifyReceiptId/`) and
  `ENDPOINT_SANDBOX` (`http://localhost:8080/RVSSandbox/`); `__construct($endpoint =
  self::ENDPOINT_PRODUCTION)` throws `RunTimeException` for any other endpoint; chainable
  `setUserId()`, `setReceiptId()`, `setDeveloperSecret()`; and `validate()`, which GETs
  `developer/{secret}/user/{userId}/receiptId/{receiptId}` and returns an `Amazon\Response` (HTTP
  errors are not thrown; they surface as the result code).
- New `Amazon\Response` class whose result code is the HTTP status: constants `RESULT_OK` (200),
  `RESULT_INVALID_RECEIPT` (400), `RESULT_INVALID_DEVELOPER_SECRET` (496), `RESULT_INVALID_USER_ID`
  (497), `RESULT_INTERNAL_ERROR` (500); methods `__construct($httpStatusCode = 200, $jsonResponse =
  null)`, `getResultCode()`, `getReceipt()`, `isValid()`, `parseJsonResponse()`.

## [1.0.13] - 2015-03-03

### Fixed
- `iTunes\Response` now keeps the real `status` for responses that carry no `receipt` key (for
  example a bare `{"status": 21007}`). Since 1.0.4 such responses were reported as
  `RESULT_DATA_MALFORMED` (21002), which also kept `iTunes\Validator::validate()` from recognising
  21007 and retrying against the sandbox. Thanks to @grEvenX
  ([#15](https://github.com/aporat/store-receipt-validator/pull/15))

## [1.0.12] - 2015-02-25

### Added
- `iTunes\Response::getLatestReceipt()` and `iTunes\Response::getLatestReceiptInfo()` expose the
  top-level `latest_receipt` and `latest_receipt_info` fields Apple returns for auto-renewable
  subscriptions (populated for iOS 7+ style receipts only). Thanks to @chekalskiy
  ([#13](https://github.com/aporat/store-receipt-validator/pull/13))

### Fixed
- `iTunes\Validator::setIStoreSharedSecret()` now returns `$this`, so it can be chained like the
  other setters. Thanks to @chekalskiy
  ([#12](https://github.com/aporat/store-receipt-validator/pull/12))

## [1.0.11] - 2015-02-25

### Added
- Google Play subscription validation: new `GooglePlay\Validator::TYPE_PURCHASE` and
  `GooglePlay\Validator::TYPE_SUBSCRIPTION` constants and a chainable
  `setPurchaseType($purchase_type)`. `validate()` calls `purchases_subscriptions->get()` when the
  type is `TYPE_SUBSCRIPTION` and `purchases_products->get()` otherwise (the default). Thanks to
  @chekalskiy ([#11](https://github.com/aporat/store-receipt-validator/pull/11))

## [1.0.10] - 2015-02-04

### Fixed
- `iTunes\Validator::__construct()` defaulted its `$endpoint` parameter to the bare, undefined
  constant `ENDPOINT_PRODUCTION` instead of `self::ENDPOINT_PRODUCTION`, so `new Validator()` with
  no argument raised a notice and then threw `RunTimeException("Invalid endpoint
  'ENDPOINT_PRODUCTION'")`. Thanks to @grEvenX
  ([#6](https://github.com/aporat/store-receipt-validator/pull/6))

### Changed
- **Breaking:** `GooglePlay\Validator::validate()` now calls the Android Publisher API v2
  `purchases_products->get()` instead of the removed v1.1 `inapppurchases->get()`, so it returns a
  `Google_Service_AndroidPublisher_ProductPurchase` rather than an `InappPurchase`; composer
  `google/apiclient` was bumped from `1.0.4-beta` to `~1.1`. Thanks to @whs
  ([#7](https://github.com/aporat/store-receipt-validator/pull/7))
- The Google Play cached access-token file is created with mode `0770` instead of `0777`.
  ([#7](https://github.com/aporat/store-receipt-validator/pull/7))

## [1.0.9] - 2015-01-30

### Fixed
- `iTunes\Validator::validate($receiptData, $iStoreSharedSecret)` stored `$receiptData` as the
  shared secret; the second argument is now used, so passing the secret to `validate()` works for
  the first time since it was added in 1.0.6. Thanks to @grEvenX
  ([#5](https://github.com/aporat/store-receipt-validator/pull/5))

### Changed
- composer: `guzzle/guzzle` constraint relaxed from `3.8.*` to `~3.8`, allowing Guzzle 3.9. Thanks
  to @MaartenStaa ([#4](https://github.com/aporat/store-receipt-validator/pull/4))

## [1.0.8] - 2014-12-09

### Changed
- `GooglePlay\Validator` now caches the OAuth access token in a per-client file,
  `<sys_get_temp_dir()>/googleplay_access_token_<md5(client_id)>.txt`, instead of one shared
  `googleplay_access_token.txt`, so validators for different Google API clients on the same host no
  longer overwrite each other's token.

## [1.0.7] - 2014-11-21

### Changed
- **Breaking:** the misspelled constant `iTunes\Response::RESULT_PRODUCTION_RECEIPT_SENT_TO_SENDBOX`
  was renamed to `iTunes\Response::RESULT_PRODUCTION_RECEIPT_SENT_TO_SANDBOX` (value 21008
  unchanged).

### Fixed
- `iTunes\Response` only reads `receipt.bundle_id` (iOS 7+) or `receipt.bid` (iOS 6) when the key
  exists, so receipts without one no longer trigger an undefined-index notice; `getBundleId()`
  returns `null` in that case.

## [1.0.6] - 2014-10-31

### Added
- iTunes shared-secret support: `iTunes\Validator::setIStoreSharedSecret($secret)` and
  `getIStoreSharedSecret()`. When set, the secret is sent as the `password` field of the
  verifyReceipt request, as Apple requires for auto-renewable subscriptions. Thanks to @stokic
  ([#1](https://github.com/aporat/store-receipt-validator/pull/1))
- `iTunes\Response::getBundleId()`, returning `receipt.bundle_id` for iOS 7+ receipts or
  `receipt.bid` for iOS 6 receipts.

### Changed
- `iTunes\Validator::validate($receiptData = null)` gained a second parameter, `$iStoreSharedSecret
  = null`. Note that in this release the parameter is mishandled (the receipt data is stored as the
  secret); use `setIStoreSharedSecret()` instead until 1.0.9.
  ([#1](https://github.com/aporat/store-receipt-validator/pull/1))

## [1.0.5] - 2014-08-13

- No user-visible changes. The only edit replaces `is_array($receipt['in_app']) > 0` with
  `is_array($receipt['in_app'])` in `iTunes\Response::parseJsonResponse()`, which is functionally
  equivalent despite the commit message "fixed ios > 7 receipt validation".

## [1.0.4] - 2014-08-13

### Added
- Support for iOS 7+ unified app receipts in `iTunes\Response`: when the response contains
  `receipt.in_app`, the new `iTunes\Response::getPurchases()` returns that array of in-app
  purchases; for iOS 6-style transaction receipts it returns a one-element array wrapping the
  receipt itself.

### Changed
- `iTunes\Response::getReceipt()` now returns an empty array instead of `null` when the response has
  no receipt.
- A response without a `receipt` key is now reported as `RESULT_DATA_MALFORMED` (21002) regardless
  of the `status` Apple returned. This is a regression for status-only responses such as 21007 and
  was fixed in 1.0.13.

## [1.0.2] - 2014-08-07

### Changed
- `GooglePlay\Validator` no longer prints diagnostics to stdout. The constructor now throws
  `ReceiptValidator\RunTimeException` when refreshing the access token fails (previously it echoed
  the error and continued), and `validate()` lets Google API client exceptions propagate instead of
  echoing them and returning `null`.
- The Google Play access-token cache moved from the hardcoded `/tmp/google_access_token.txt` to
  `<sys_get_temp_dir()>/googleplay_access_token.txt`; the file is created (`touch`) and set to mode
  `0777` on construction.

## [1.0.0] - 2014-08-07

### Added
- `iTunes\Validator`: constants `ENDPOINT_PRODUCTION` and `ENDPOINT_SANDBOX`;
  `__construct($endpoint)` (throws `RunTimeException` for any other URL);
  `setReceiptData()`/`getReceiptData()`, accepting either base64 or raw JSON (JSON is base64-encoded
  for you); `setEndpoint()`/`getEndpoint()`; and `validate($receiptData = null)`, which POSTs to
  Apple's verifyReceipt endpoint with Guzzle 3 and returns an `iTunes\Response`. When the production
  endpoint answers 21007 (sandbox receipt sent to production, as during App Review) the request is
  automatically retried against the sandbox. A non-200 HTTP reply throws `RunTimeException`.
- `iTunes\Response`: constants `RESULT_OK` (0), `RESULT_APPSTORE_CANNOT_READ` (21000),
  `RESULT_DATA_MALFORMED` (21002), `RESULT_RECEIPT_NOT_AUTHENTICATED` (21003),
  `RESULT_SHARED_SECRET_NOT_MATCH` (21004), `RESULT_RECEIPT_SERVER_UNAVAILABLE` (21005),
  `RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED` (21006), `RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION`
  (21007), `RESULT_PRODUCTION_RECEIPT_SENT_TO_SENDBOX` (21008, sic); methods `getResultCode()`,
  `setResultCode()`, `getReceipt()`, `isValid()`, `parseJsonResponse()`.
- `GooglePlay\Validator`: `__construct(array $options)` taking `client_id`, `client_secret` and
  `refresh_token`, caching the OAuth access token in `/tmp/google_access_token.txt`; chainable
  `setPackageName()`, `setPurchaseToken()`, `setProductId()`; and `validate()`, returning the
  Android Publisher `inapppurchases->get()` result, or `null` (with the error echoed to stdout) on
  failure.
- `ReceiptValidator\RunTimeException`, extending `\Exception`.
- composer: package `aporat/store-receipt-validator` requiring PHP `>=5.4`, `guzzle/guzzle 3.8.*`
  and `google/apiclient 1.0.4-beta`, with PSR-0 autoloading of the `ReceiptValidator` namespace from
  `src/`.

[Unreleased]: https://github.com/aporat/store-receipt-validator/compare/10.0.0...HEAD
[10.0.0]: https://github.com/aporat/store-receipt-validator/compare/9.0.0...10.0.0
[9.0.0]: https://github.com/aporat/store-receipt-validator/compare/8.0.0...9.0.0
[8.0.0]: https://github.com/aporat/store-receipt-validator/compare/7.1.0...8.0.0
[7.1.0]: https://github.com/aporat/store-receipt-validator/compare/7.0.0...7.1.0
[7.0.0]: https://github.com/aporat/store-receipt-validator/compare/6.1.4...7.0.0
[6.1.4]: https://github.com/aporat/store-receipt-validator/compare/6.1.2...6.1.4
[6.1.2]: https://github.com/aporat/store-receipt-validator/compare/6.1.0...6.1.2
[6.1.0]: https://github.com/aporat/store-receipt-validator/compare/6.0.6...6.1.0
[6.0.6]: https://github.com/aporat/store-receipt-validator/compare/6.0.4...6.0.6
[6.0.4]: https://github.com/aporat/store-receipt-validator/compare/6.0.2...6.0.4
[6.0.2]: https://github.com/aporat/store-receipt-validator/compare/6.0.0...6.0.2
[6.0.0]: https://github.com/aporat/store-receipt-validator/compare/5.0.1...6.0.0
[5.0.1]: https://github.com/aporat/store-receipt-validator/compare/5.0.0...5.0.1
[5.0.0]: https://github.com/aporat/store-receipt-validator/compare/4.4.3...5.0.0
[4.4.3]: https://github.com/aporat/store-receipt-validator/compare/4.4.2...4.4.3
[4.4.2]: https://github.com/aporat/store-receipt-validator/compare/4.4.1...4.4.2
[4.4.1]: https://github.com/aporat/store-receipt-validator/compare/4.4.0...4.4.1
[4.4.0]: https://github.com/aporat/store-receipt-validator/compare/4.3.0...4.4.0
[4.3.0]: https://github.com/aporat/store-receipt-validator/compare/4.2.0...4.3.0
[4.2.0]: https://github.com/aporat/store-receipt-validator/compare/4.1.0...4.2.0
[4.1.0]: https://github.com/aporat/store-receipt-validator/compare/4.0.3...4.1.0
[4.0.3]: https://github.com/aporat/store-receipt-validator/compare/4.0.2...4.0.3
[4.0.2]: https://github.com/aporat/store-receipt-validator/compare/4.0.1...4.0.2
[4.0.1]: https://github.com/aporat/store-receipt-validator/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/aporat/store-receipt-validator/compare/3.5.0...4.0.0
[3.5.0]: https://github.com/aporat/store-receipt-validator/compare/3.4.1...3.5.0
[3.4.1]: https://github.com/aporat/store-receipt-validator/compare/3.4.0...3.4.1
[3.4.0]: https://github.com/aporat/store-receipt-validator/compare/3.3.0...3.4.0
[3.3.0]: https://github.com/aporat/store-receipt-validator/compare/3.2.0...3.3.0
[3.2.0]: https://github.com/aporat/store-receipt-validator/compare/3.1.0...3.2.0
[3.1.0]: https://github.com/aporat/store-receipt-validator/compare/3.0.0...3.1.0
[3.0.0]: https://github.com/aporat/store-receipt-validator/compare/2.2.0...3.0.0
[2.2.0]: https://github.com/aporat/store-receipt-validator/compare/2.1.0...2.2.0
[2.1.0]: https://github.com/aporat/store-receipt-validator/compare/2.0.6...2.1.0
[2.0.6]: https://github.com/aporat/store-receipt-validator/compare/2.0.4...2.0.6
[2.0.4]: https://github.com/aporat/store-receipt-validator/compare/2.0.2...2.0.4
[2.0.2]: https://github.com/aporat/store-receipt-validator/compare/2.0.0...2.0.2
[2.0.0]: https://github.com/aporat/store-receipt-validator/compare/1.5.0...2.0.0
[1.5.0]: https://github.com/aporat/store-receipt-validator/compare/1.4.1...1.5.0
[1.4.1]: https://github.com/aporat/store-receipt-validator/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/aporat/store-receipt-validator/compare/1.3.1...1.4.0
[1.3.1]: https://github.com/aporat/store-receipt-validator/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/aporat/store-receipt-validator/compare/1.2.1...1.3.0
[1.2.1]: https://github.com/aporat/store-receipt-validator/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/aporat/store-receipt-validator/compare/1.1.1...1.2.0
[1.1.1]: https://github.com/aporat/store-receipt-validator/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/aporat/store-receipt-validator/compare/1.0.13...1.1.0
[1.0.13]: https://github.com/aporat/store-receipt-validator/compare/1.0.12...1.0.13
[1.0.12]: https://github.com/aporat/store-receipt-validator/compare/1.0.11...1.0.12
[1.0.11]: https://github.com/aporat/store-receipt-validator/compare/1.0.10...1.0.11
[1.0.10]: https://github.com/aporat/store-receipt-validator/compare/1.0.9...1.0.10
[1.0.9]: https://github.com/aporat/store-receipt-validator/compare/1.0.8...1.0.9
[1.0.8]: https://github.com/aporat/store-receipt-validator/compare/1.0.7...1.0.8
[1.0.7]: https://github.com/aporat/store-receipt-validator/compare/1.0.6...1.0.7
[1.0.6]: https://github.com/aporat/store-receipt-validator/compare/1.0.5...1.0.6
[1.0.5]: https://github.com/aporat/store-receipt-validator/compare/1.0.4...1.0.5
[1.0.4]: https://github.com/aporat/store-receipt-validator/compare/1.0.2...1.0.4
[1.0.2]: https://github.com/aporat/store-receipt-validator/compare/1.0.0...1.0.2
[1.0.0]: https://github.com/aporat/store-receipt-validator/releases/tag/1.0.0
