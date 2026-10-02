# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/).

Entries for releases before 7.0.0 are reconstructed from commit history and the
[GitHub releases page](https://github.com/aporat/store-receipt-validator/releases),
so they summarise rather than enumerate every change.

## [Unreleased]

### Added
- `AppleAppStore\Validator::verifySignedTransaction()`, `verifySignedRenewalInfo()` and
  `verifySignedAppTransaction()` verify StoreKit 2 JWS payloads offline, checking the
  signature, Apple certificate chain, bundle ID and environment ([#230](https://github.com/aporat/store-receipt-validator/pull/230), closes [#229](https://github.com/aporat/store-receipt-validator/issues/229)).
- `AppleAppStore\Validator::verifyNotification()` verifies an App Store Server Notification
  and requires its bundle ID and environment to match the validator. Constructing
  `ServerNotification` directly still verifies the signature only ([#239](https://github.com/aporat/store-receipt-validator/pull/239)).
- Optional `appAppleId` on the `AppleAppStore\Validator` constructor, with
  `setAppAppleId()` and `getAppAppleId()`. When set, production app transactions and
  notifications must carry the matching app Apple ID, as Apple's official verifier
  requires ([#239](https://github.com/aporat/store-receipt-validator/pull/239)).
- `AppleAppStore\Signature` namespace with the four signing helpers Apple's official
  libraries provide: `PromotionalOfferV2SignatureCreator`,
  `IntroductoryOfferEligibilitySignatureCreator`, `AdvancedCommerceInAppSignatureCreator`
  (JWS for StoreKit 2) and `PromotionalOfferSignatureCreator` (StoreKit 1 ECDSA signature) ([#237](https://github.com/aporat/store-receipt-validator/pull/237)).
- `AppleAppStore\ConsumptionRequestV1` for the deprecated v1 Send Consumption Information
  endpoint, and `DeliveryStatus` / `RefundPreference` enums for v2 ([#236](https://github.com/aporat/store-receipt-validator/pull/236)).
- `ConsumptionRequest::setConsumptionPercent()` converts a plain percentage to the
  milliunits Apple expects ([#236](https://github.com/aporat/store-receipt-validator/pull/236)).
- Optional status filter on `getAllSubscriptionStatuses()`, sent as repeated `status`
  query parameters ([#240](https://github.com/aporat/store-receipt-validator/pull/240)).
- `User-Agent: store-receipt-validator/php/<version>` on App Store Server API requests,
  with the version read from Composer. `AbstractValidator::userAgent()` is public for
  reuse ([#238](https://github.com/aporat/store-receipt-validator/pull/238)).
- `ServerNotification::getAppAppleId()`, and an optional `TokenVerifier` constructor
  argument for tests that sign with their own chain ([#239](https://github.com/aporat/store-receipt-validator/pull/239)).
- `EndpointPathsTest`, a single table of every App Store Server API endpoint with its
  verb, documented path and Apple docs link, plus a guard that fails when an endpoint
  method has no row ([#241](https://github.com/aporat/store-receipt-validator/pull/241)).

### Changed
- App Store Server API requests go to Apple's documented hosts, `api.storekit.apple.com`
  and `api.storekit-sandbox.apple.com`, instead of the legacy `*.itunes.apple.com` names.
  **Add the new hosts to any egress allowlist before upgrading** ([#242](https://github.com/aporat/store-receipt-validator/pull/242)).
- `sendConsumptionInformation()` targets the v2 endpoint
  (`/inApps/v2/transactions/consumption`), aligning with App Store Server API 1.19 and
  Apple's official libraries. `ConsumptionRequest` is the v2 model: `deliveryStatus` and
  `refundPreference` take the new enums, and legacy v1 integers are still accepted and
  mapped. `deliveryStatus` is required by v2, so a request without one now throws before
  any HTTP call. Pass a `ConsumptionRequestV1` to keep using the v1 endpoint ([#236](https://github.com/aporat/store-receipt-validator/pull/236)).
- `ServerNotification` reads the bundle ID, environment and app Apple ID from whichever
  payload section Apple populated (`data`, `summary`, `appData` or
  `externalPurchaseToken`). Summary and external purchase notifications previously
  reported an empty bundle ID and defaulted to sandbox. A notification with no
  environment now throws instead of being treated as sandbox ([#239](https://github.com/aporat/store-receipt-validator/pull/239)).
- `TokenVerifier` now requires the App Store signing marker on the leaf certificate, the
  WWDR marker on the intermediate, an `x5c` chain of exactly three certificates, and
  every certificate to be valid at the payload's `signedDate` ([#230](https://github.com/aporat/store-receipt-validator/pull/230)).
- `phpseclib/phpseclib` constraint is `^3.0 || ^4.0`. `ReceiptUtility` selects the
  matching ASN.1 decoder at runtime ([#234](https://github.com/aporat/store-receipt-validator/pull/234), fixes [#232](https://github.com/aporat/store-receipt-validator/issues/232)).

### Fixed
- `getAppTransactionInfo()` called `/inApps/v1/transactions/appTransaction/{id}`; the
  documented path is the plural `appTransactions` ([#235](https://github.com/aporat/store-receipt-validator/pull/235), fixes [#231](https://github.com/aporat/store-receipt-validator/issues/231)).

## [10.0.0] - 2026-09-05

### Added
- `GooglePlay` namespace mirroring the App Store one. `GooglePlay\Validator` covers the
  Android Publisher v3 purchases endpoints: `getSubscriptionPurchaseV2()`,
  `acknowledgeSubscription()`, `revokeSubscription()`, `getProductPurchase()`,
  `acknowledgeProduct()`, `consumeProduct()` and `getVoidedPurchases()`.
- Google service account authentication through the OAuth 2.0 JWT bearer flow with
  in-memory token caching, or a `google/auth` client via the `AccessTokenProvider`
  interface.
- `GooglePlay\ServerNotification` parses Real-time Developer Notifications, including the
  Pub/Sub push envelope.

### Changed
- **Breaking:** `AppleAppStore\ReceiptUtility` was ported to phpseclib 4 and the
  `phpseclib/phpseclib` requirement became `^4.0`. The public API of
  `extractTransactionIdFromAppReceipt()` was unchanged. (Relaxed again to
  `^3.0 || ^4.0` in the next release.)
- `guzzlehttp/psr7` constraint widened to `^2.6 || ^3.0`.

## [9.0.0] - 2026-05-15

### Added
- `AppleAppStore\Validator` covers the App Store Server API: `getTransactionHistory()`,
  `getTransactionInfo()`, `getAppTransactionInfo()`, `finishTransaction()`,
  `setAppAccountToken()`, `sendConsumptionInformation()`, `lookUpOrderId()`,
  `getRefundHistory()`, `getAllSubscriptionStatuses()`, `extendSubscriptionRenewalDate()`,
  `extendSubscriptionRenewalDatesForAllActiveSubscribers()`,
  `getStatusOfSubscriptionRenewalDateExtensions()`, `getTestNotificationStatus()` and
  `getNotificationHistory()`, each with typed request and response objects.
- PSR-3 logging on every App Store Server API call.

### Deprecated
- `AppleAppStore\Validator::validate()`. Use `getTransactionHistory()` for paginated
  history or `getTransactionInfo()` for a single transaction.

### Changed
- PHPUnit requirement raised to `^13.0`.

## [8.0.0] - 2026-03-26

### Added
- PSR-3 logging support on all validators.
- Test coverage for the Apple App Store enums and the iTunes `APIError` enum.

### Changed
- **Breaking:** HTTP transport moved from Guzzle to the PSR-18 client and PSR-17 factory
  interfaces. Guzzle remains the default implementation; any PSR-18 client can be injected.

## [7.1.0] - 2026-01-29

### Changed
- Strict types and `readonly` classes across the library.
- `squizlabs/php_codesniffer` constraint widened to `^3.10 || ^4.0`.

### Deprecated
- The iTunes verifyReceipt API classes, in favour of the App Store Server API.

## [7.0.0] - 2025-09-17

### Changed
- **Breaking:** all date fields return `Carbon\CarbonInterface|null` instead of
  `Carbon\Carbon|null`.
- Validators, response objects, `Transaction` and `RenewalInfo` classes, JWT handling,
  `APIError` handling and the `Environment` enum were refactored.

## [6.1.4] - 2025-09-16

### Fixed
- iTunes sandbox receipts were sent to the production endpoint ([#199](https://github.com/aporat/store-receipt-validator/pull/199)).

## [6.1.2] - 2025-08-06

### Fixed
- Endpoint string concatenation in the validators.

## [6.1.0] - 2025-05-04

### Added
- `AppleAppStore\Validator::requestTestNotification()`.
- `APIError` enums for the App Store Server API, iTunes and Amazon responses.

## [6.0.6] - 2025-05-02

### Added
- `Environment::fromString()`.

### Changed
- Validators override `getTransactions()`.
- `phpstan/phpstan` constraint widened to `^1.11 || ^2.0`.

## [6.0.4] - 2025-04-19

### Added
- Descending sort order on the App Store validator's transaction history.

## [6.0.2] - 2025-04-19

### Fixed
- `ServerNotificationSubtype` values and namespace.
- `Transaction` now extends `AbstractTransaction`.

## [6.0.0] - 2025-04-19

### Added
- `AppleAppStore\Validator` for the App Store Server API, with transaction history and
  signed JWS token verification.
- App Store Server Notifications: `AppleAppStore\ServerNotification` (V2) and
  `iTunes\ServerNotification` (V1).
- `AppleAppStore\ReceiptUtility::extractTransactionIdFromAppReceipt()`.
- PHPStan static analysis in CI.

### Changed
- **Breaking:** PHP 8.3 is the minimum version.
- iTunes and Amazon validators refactored for structure and testability.
- "Purchases" renamed to "transactions" across the API.

### Removed
- **Breaking:** the Google Play validator, due to lack of testability. (Reintroduced in
  10.0.0 with a new implementation.)

## [5.0.1] - 2025-03-11

### Fixed
- A property accessed before initialization.

## [5.0.0] - 2025-02-27

### Changed
- **Breaking:** PHP 8.2 is the minimum version.
- Code style cleanup; Dependabot and Codecov configured.

### Removed
- **Breaking:** the Windows Store validator.

## [4.4.3] - 2024-03-06

### Added
- `base_uri` may be passed through `request_options` on the iTunes validator, for test
  setups. Thanks to @aliozkan.

## [4.4.2] - 2024-02-02

### Changed
- PHP 8.2 and 8.4 added to the supported versions.
- `nesbot/carbon` v3 is allowed. Thanks to @gjuric.

## [4.4.1] - 2023-04-21

### Fixed
- Switching Google Play validation to the Subscriptions V2 endpoint.

## [4.4.0] - 2023-04-20

### Added
- Google Play `purchases.subscriptionsv2` endpoint support ([#171](https://github.com/aporat/store-receipt-validator/pull/171)). Thanks to @yankers.

## [4.3.0] - 2022-11-10

### Added
- `getExternalAccountId()` on the Google Play validation response. Thanks to Marija
  Predarska.

### Fixed
- `json_decode` deprecation warning on PHP 8 for the Google Play `developerPayload`.
  Thanks to @sica07.

## [4.2.0] - 2021-12-29

### Fixed
- PHP 8.1 deprecations in classes implementing `ArrayAccess`.

## [4.1.0] - 2021-10-13

### Changed
- Google classes migrated to the namespaced `google/apiclient` API ([#153](https://github.com/aporat/store-receipt-validator/pull/153)). Thanks to @yyeltsyn.
- `google/apiclient` constraint is now `^2.10`.

### Removed
- PHP 7.2 support.

## [4.0.3] - 2020-12-02

### Changed
- `setEndpoint()` validates the endpoint, the same as the constructor.

## [4.0.2] - 2020-12-02

### Added
- PHP 8 support ([#141](https://github.com/aporat/store-receipt-validator/pull/141)).
- iTunes validation tests using Guzzle's mock handler.

## [4.0.1] - 2020-08-18

### Fixed
- Guzzle did not recognise the full `base_uri` because of a missing trailing slash ([#126](https://github.com/aporat/store-receipt-validator/issues/126)).

## [4.0.0] - 2020-08-04

### Changed
- **Breaking:** PHP 7.2 is the minimum version.
- Guzzle 6.3 or 7.0 is supported.
- Amazon validator cleanup; unused `product_id` removed.

## [3.5.0] - 2020-08-03

### Added
- `explicit` and `implicit` strategies for the Google Play `Acknowledger`. Implicit
  acknowledges only when the purchase was not acknowledged before; explicit is the
  default ([#110](https://github.com/aporat/store-receipt-validator/pull/110)). Thanks to @Orkin.

### Changed
- `robrichards/xmlseclibs` raised to 3.0.4 for a security advisory.
- CI moved from Travis to GitHub Actions.

## [3.4.1] - 2020-03-31

### Fixed
- `Acknowledger` runtime exception arguments.

## [3.4.0] - 2019-11-19

### Added
- Google Play `Acknowledger` for the Billing Library v2 acknowledgement requirement.
  Thanks to @Orkin.

## [3.3.0] - 2019-10-19

### Added
- `PendingRenewalInfo::getGracePeriodExpiresDate()` and `isInGracePeriod()`.
- Request options for the HTTP client that calls the iTunes validation service.
  Thanks to @0hr.

### Changed
- `strtotime` replaced with Carbon for date handling.

## [3.2.0] - 2019-05-29

### Added
- Promotional offer ID on iTunes purchase items.
- Carbon 2 support.

## [3.1.0] - 2019-02-27

### Changed
- `iTunes\PurchaseItem::getWebOrderLineItemId()` and the latest receipt may be null.
- `.editorconfig` and code style checks added.

## [3.0.0] - 2018-12-26

### Added
- Detection of the response environment.
- The HTTP client configuration can be overridden.
- `isRetryable()` on the iTunes response.

### Changed
- **Breaking:** refactoring with strict comparisons and type casting throughout.
- Line items are optional.

## [2.2.0] - 2018-11-07

### Added
- Getter for the raw iTunes response data ([#80](https://github.com/aporat/store-receipt-validator/issues/80)).
- Missing iTunes receipt fields, including `app_item_id`.

### Fixed
- iTunes purchases, `pending_renewal_info` and raw data parsing.

### Removed
- PHP 7.0 support.

## [2.1.0] - 2018-04-13

### Added
- `PendingRenewalInfo` class.
- `PurchaseItem` conversion for `latest_receipt_info` objects.
- Getter and setter for the Amazon validator endpoint.

### Changed
- Amazon renewal dates are returned as Carbon instances.

## [2.0.6] - 2018-02-09

### Added
- `exclude-old-transactions` support for iTunes validation.
- Return types and type hints.

### Removed
- PHP 5.5 and 5.6 support.

## [2.0.4] - 2017-11-21

### Fixed
- iTunes `PurchaseItem` when `expires_date` is a UNIX timestamp.

### Changed
- `robrichards/xmlseclibs` `^3.0` allowed.

## [2.0.2] - 2017-09-18

### Added
- The new iTunes `pending_renewal_info` fields.

### Removed
- HHVM support.

## [2.0.0] - 2017-04-19

### Added
- `PurchaseItem` model for the iTunes validator.
- Access to the Google `AndroidPublisherService` for voided purchases, refunds and
  subscription revocation.
- `getTransactionId()` on the Amazon purchase item.
- A method giving access to the raw JSON response.

### Changed
- **Breaking:** Amazon response refactored; `SubscriptionInterface` removed.
- Empty receipts without purchases return valid responses.
- `google/apiclient` version 2 support.

### Deprecated
- `getExpiresDate()`.

## [1.5.0] - 2016-12-14

### Added
- `google/apiclient` version 2 support.
- PHP 7.1 added to CI.

## [1.4.1] - 2016-05-11

### Fixed
- Google service account key loading.

## [1.4.0] - 2016-04-28

### Added
- Google Play validator using service accounts.

## [1.3.1] - 2016-03-24

### Changed
- PSR-4 autoloading.

### Fixed
- iTunes response return types; test added for the latest receipt.

## [1.3.0] - 2015-12-20

### Changed
- Guzzle 6; PHP 5.5 is the minimum version.

### Fixed
- Amazon Appstore validator.

## [1.2.1] - 2015-11-20

### Fixed
- SSL certificates are now verified, so receipt validation cannot be trivially
  intercepted.

## [1.2.0] - 2015-10-15

### Added
- Windows Store validator with an integration test.
- The certificate received from the server can be cached.

## [1.1.1] - 2015-09-17

### Changed
- PHP 5.3 compatibility restored and added to CI.

## [1.1.0] - 2015-04-19

### Added
- Amazon Receipt Verification Service (RVS) validator.

## [1.0.13] - 2015-03-03

### Fixed
- `Response` returned the wrong status code.

## [1.0.12] - 2015-02-25

### Added
- `latest_receipt` field handling.

### Fixed
- Method chaining.

## [1.0.11] - 2015-02-25

### Added
- Google Play subscription validation.

## [1.0.10] - 2015-02-04

### Changed
- Updated to the new Android Publisher API.

### Fixed
- Use of an undefined constant.

## [1.0.9] - 2015-01-30

### Fixed
- Passing the iTunes shared secret to `validate()`.

### Changed
- Guzzle constraint `~3.8`.

## [1.0.8] - 2014-12-09

### Fixed
- Access token cache file path.

## [1.0.7] - 2014-11-21

### Added
- PHP 5.6 support.
- Comments documenting the iTunes response codes.

## [1.0.6] - 2014-10-31

### Added
- iTunes shared secret as a validator option.
- `bundle_id` on the response object.

## [1.0.5] - 2014-08-13

### Fixed
- iOS 7 and later receipt validation.

## [1.0.4] - 2014-08-13

### Added
- Support for iOS 7 and later receipts.

## [1.0.2] - 2014-08-07

### Fixed
- iTunes validator.

## [1.0.0] - 2014-08-07

### Added
- iTunes receipt validator (`ReceiptValidator\iTunes\Validator`) with sandbox support.
- Initial Google Play validator.
- Composer autoloading and Packagist publication.

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
