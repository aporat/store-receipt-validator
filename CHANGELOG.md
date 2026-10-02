# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/).

Releases before 7.0.0 are recorded on the
[GitHub releases page](https://github.com/aporat/store-receipt-validator/releases).

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

[Unreleased]: https://github.com/aporat/store-receipt-validator/compare/10.0.0...HEAD
[10.0.0]: https://github.com/aporat/store-receipt-validator/compare/9.0.0...10.0.0
[9.0.0]: https://github.com/aporat/store-receipt-validator/compare/8.0.0...9.0.0
[8.0.0]: https://github.com/aporat/store-receipt-validator/compare/7.1.0...8.0.0
[7.1.0]: https://github.com/aporat/store-receipt-validator/compare/7.0.0...7.1.0
[7.0.0]: https://github.com/aporat/store-receipt-validator/compare/6.1.4...7.0.0
