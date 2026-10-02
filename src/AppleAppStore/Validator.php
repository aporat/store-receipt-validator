<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use InvalidArgumentException;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain as Token;
use Psr\Http\Client\ClientExceptionInterface;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenGeneratorConfig;
use ReceiptValidator\AppleAppStore\JWT\TokenIssuer;
use ReceiptValidator\AppleAppStore\JWT\TokenKey;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use Throwable;

/**
 * App Store Server API Validator.
 */
class Validator extends AbstractValidator
{
    /** Sandbox endpoint URL. */
    public const string ENDPOINT_SANDBOX = 'https://api.storekit-sandbox.itunes.apple.com';

    /** Production endpoint URL. */
    public const string ENDPOINT_PRODUCTION = 'https://api.storekit.itunes.apple.com';

    /** @return array{production:string, sandbox:string} */
    protected function endpointMap(): array
    {
        return [
            Environment::PRODUCTION->value => self::ENDPOINT_PRODUCTION,
            Environment::SANDBOX->value    => self::ENDPOINT_SANDBOX,
        ];
    }

    /** Transaction ID to validate. */
    protected ?string $transactionId = null;

    /** App Store Connect's private key (PEM or raw .p8 contents). */
    protected string $signingKey;

    /** Key ID for the private key. */
    protected string $keyId;

    /** Issuer ID (App Store Connect API key issuer). */
    protected string $issuerId;

    /** App bundle ID. */
    protected string $bundleId;

    /** Verifier for Apple-signed JWS payloads. */
    protected ?TokenVerifier $tokenVerifier = null;

    /** The app's numeric App Store identifier, checked on production payloads when set. */
    protected ?int $appAppleId = null;

    /**
     * @param string $signingKey The contents of your .p8 key
     * @param string $keyId      The Key ID from App Store Connect
     * @param string $issuerId   Your Issuer ID
     * @param string $bundleId   Your app's bundle identifier
     * @param Environment $environment Target environment (defaults to PRODUCTION)
     * @param int|null $appAppleId Your app's numeric Apple ID from App Store Connect. Recommended in
     *                             production: when set, signed app transactions and notifications must
     *                             carry it. Apple omits it from sandbox payloads, so it is not checked there.
     */
    public function __construct(
        string $signingKey,
        string $keyId,
        string $issuerId,
        string $bundleId,
        Environment $environment = Environment::PRODUCTION,
        ?int $appAppleId = null,
    ) {
        parent::__construct();
        $this->signingKey   = $signingKey;
        $this->keyId        = $keyId;
        $this->issuerId     = $issuerId;
        $this->bundleId     = $bundleId;
        $this->environment  = $environment;
        $this->appAppleId   = $appAppleId;
    }

    /**
     * Set the app's numeric Apple ID to enforce on production payloads.
     */
    public function setAppAppleId(?int $appAppleId): static
    {
        $this->appAppleId = $appAppleId;

        return $this;
    }

    public function getAppAppleId(): ?int
    {
        return $this->appAppleId;
    }

    /**
     * Get the transaction history for a given transaction ID.
     *
     * Returns up to 20 transactions per call. When {@see Response::$hasMore} is true,
     * pass the returned {@see Response::$revision} back as
     * {@see TransactionHistoryParams::$revision} to fetch the next page.
     *
     * @param string $transactionId Any transaction ID associated with the customer.
     * @param TransactionHistoryParams|null $params Optional filters and sort order.
     *                                              Defaults to DESCENDING sort with no filters.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/get-transaction-history
     *
     * @throws ValidationException
     */
    public function getTransactionHistory(
        string $transactionId,
        ?TransactionHistoryParams $params = null,
    ): Response {
        $uri = sprintf('/inApps/v2/history/%s', $transactionId);

        return $this->makeRequest('GET', $uri, ($params ?? new TransactionHistoryParams())->toQueryParams());
    }

    /**
     * Fetch transaction history from the App Store Server API.
     *
     * @deprecated Use {@see getTransactionHistory()} if you need paginated history with
     *             {@see TransactionHistoryParams} filters, or {@see getTransactionInfo()}
     *             if you only need a single transaction by ID.
     *
     * @param string|null $transactionId       Overrides the transaction ID set via
     *                                         {@see setTransactionId()} for this call.
     * @param TransactionHistoryParams|null $params  Optional filters and sort order.
     *                                               Defaults to DESCENDING sort with no filters.
     * @throws ValidationException
     */
    public function validate(
        ?string $transactionId = null,
        ?TransactionHistoryParams $params = null,
    ): Response {
        if ($transactionId !== null) {
            $this->setTransactionId($transactionId);
        }

        if ($this->transactionId === null || $this->transactionId === '') {
            throw new ValidationException('Missing transaction ID for App Store Server API validation.');
        }

        return $this->getTransactionHistory($this->transactionId, $params);
    }

    /**
     * Set the transaction ID (fluent).
     */
    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * Perform the HTTP request to the App Store API and return the decoded JSON array.
     *
     * This is the low-level transport method. Use {@see makeRequest()} for the
     * transaction-history endpoint, or call this directly when you need to build
     * a different response type (e.g. {@see getAllSubscriptionStatuses()}).
     *
     * When $requestBody is provided it is serialised as JSON and sent as the request
     * body with a Content-Type: application/json header (used by write endpoints such
     * as {@see setAppAccountToken()}).
     *
     * Endpoints that return 200 with an empty body (write-only operations) return an
     * empty array rather than throwing.
     *
     * @param array<string,mixed> $queryParams
     * @param array<string,mixed>|null $requestBody
     * @return array<string, mixed>
     * @throws ValidationException
     */
    protected function makeRawRequest(
        string $method,
        string $uri = '',
        array $queryParams = [],
        ?array $requestBody = null,
    ): array {
        $endpoint = $this->endpointForEnvironment();

        $this->logger->debug('App Store API request', [
            'method'      => $method,
            'uri'         => $uri,
            'environment' => $this->environment->value,
            'query'       => $queryParams,
        ]);

        $token = $this->generateToken();

        $url = $endpoint . $uri;
        if (!empty($queryParams)) {
            $url .= '?' . $this->buildQueryString($queryParams);
        }

        $request = $this->getRequestFactory()->createRequest($method, $url);
        foreach ($this->buildHeaders($token) as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($requestBody !== null) {
            $jsonBody = json_encode($requestBody, JSON_THROW_ON_ERROR);
            $stream   = $this->getStreamFactory()->createStream($jsonBody);
            $request  = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($stream);
        }

        try {
            $httpResponse = $this->getClient()->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('App Store API connection failed', [
                'method'      => $method,
                'uri'         => $uri,
                'environment' => $this->environment->value,
                'error'       => $e->getMessage(),
            ]);
            throw new ValidationException('Unable to connect to App Store Server API - ' . $e->getMessage(), 0, $e);
        }

        $statusCode = $httpResponse->getStatusCode();
        $body       = (string) $httpResponse->getBody();

        // Decode JSON (keep parse error message generic to satisfy strict tests)
        $decoded = null;
        $isJson  = false;
        if ($body !== '') {
            try {
                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                $isJson  = is_array($decoded);
            } catch (Throwable) {
                if ($statusCode === 200) {
                    throw new ValidationException('Invalid response format from App Store Server API.');
                }
            }
        }

        if ($statusCode !== 200) {
            // If Apple returns an errorCode, prefer enum-based message/code
            $apiCase = ($isJson && isset($decoded['errorCode']))
                ? APIError::tryFrom((int) $decoded['errorCode'])
                : null;

            if ($apiCase !== null) {
                $errorCode    = $apiCase->value;
                $errorMessage = $apiCase->message();
            } else {
                // Friendly defaults for common auth/not-found responses
                $errorMessage = match ($statusCode) {
                    401     => 'Unauthenticated',
                    404     => 'Not Found',
                    default => ($isJson ? ($decoded['errorMessage'] ?? null) : null) ?? ($body !== '' ? $body : 'Unexpected error'),
                };
                $errorCode = $statusCode;
            }

            $this->logger->warning('App Store API error response', [
                'method'      => $method,
                'uri'         => $uri,
                'environment' => $this->environment->value,
                'status_code' => $statusCode,
                'error_code'  => $errorCode,
                'error'       => $errorMessage,
            ]);

            throw new ValidationException("App Store API error [$errorCode]: $errorMessage", $errorCode);
        }

        // Some write endpoints return 200 with an empty body — treat as success.
        if ($body === '') {
            $this->logger->info('App Store API request successful', [
                'method'      => $method,
                'uri'         => $uri,
                'environment' => $this->environment->value,
                'query'       => $queryParams,
            ]);

            return [];
        }

        if (!$isJson || !is_array($decoded)) {
            throw new ValidationException('Invalid response format from App Store Server API.');
        }

        $this->logger->info('App Store API request successful', [
            'method'      => $method,
            'uri'         => $uri,
            'environment' => $this->environment->value,
            'query'       => $queryParams,
        ]);

        return $decoded;
    }

    /**
     * Perform the HTTP request to the App Store API and wrap the result in a
     * transaction-history {@see Response}.
     *
     * @param array<string,mixed> $queryParams
     * @throws ValidationException
     */
    protected function makeRequest(string $method, string $uri = '', array $queryParams = []): Response
    {
        return new Response($this->makeRawRequest($method, $uri, $queryParams));
    }

    /**
     * Generate a JWT for authenticating with the App Store Server API.
     *
     * @throws ValidationException
     */
    private function generateToken(): Token
    {
        try {
            if ($this->signingKey === '') {
                throw new ValidationException('Cannot generate a token without a signing key.');
            }

            $issuer = new TokenIssuer(
                $this->issuerId,
                $this->bundleId,
                new TokenKey($this->keyId, InMemory::plainText($this->signingKey)),
                new Sha256()
            );

            $config = TokenGeneratorConfig::forAppStore($issuer);

            return (new TokenGenerator($config))->generate();
        } catch (Throwable $e) {
            throw new ValidationException('JWT generation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Request a test notification from the App Store Server API.
     *
     * @throws ValidationException
     */
    public function requestTestNotification(): string
    {
        $data = $this->makeRawRequest('POST', '/inApps/v1/notifications/test');

        if (!isset($data['testNotificationToken'])) {
            throw new ValidationException('Missing testNotificationToken in response.');
        }

        return (string) $data['testNotificationToken'];
    }

    /**
     * Check the delivery status of a test notification.
     *
     * Use the token returned by {@see requestTestNotification()} to poll whether
     * the App Store successfully delivered the test notification to your server,
     * and to inspect each send attempt's result.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/get-test-notification-status
     *
     * @throws ValidationException
     */
    public function getTestNotificationStatus(string $testNotificationToken): CheckTestNotificationResponse
    {
        $uri = sprintf('/inApps/v1/notifications/test/%s', $testNotificationToken);

        return new CheckTestNotificationResponse($this->makeRawRequest('GET', $uri));
    }

    /**
     * Fetch up to six months of App Store Server Notification history for your app.
     *
     * Returns up to 20 items per page. When {@see NotificationHistoryResponse::$hasMore}
     * is true, pass the returned {@see NotificationHistoryResponse::$paginationToken}
     * back as $paginationToken to fetch the next page.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/get-notification-history
     *
     * @throws ValidationException
     */
    public function getNotificationHistory(
        NotificationHistoryRequest $request,
        ?string $paginationToken = null,
    ): NotificationHistoryResponse {
        $queryParams = $paginationToken !== null ? ['paginationToken' => $paginationToken] : [];

        return new NotificationHistoryResponse(
            $this->makeRawRequest('POST', '/inApps/v1/notifications/history', $queryParams, $request->toArray())
        );
    }

    /**
     * Look up all transactions associated with a customer's App Store order ID.
     *
     * An order ID appears on the customer's purchase receipt and can be used to
     * resolve customer support issues. The response status indicates whether the
     * order ID is valid; check {@see OrderLookupResponse::isValid()} before
     * accessing the transactions.
     *
     * Note: This endpoint is not available in the sandbox environment.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/look-up-order-id
     *
     * @throws ValidationException
     */
    public function lookUpOrderId(string $orderId): OrderLookupResponse
    {
        $uri = sprintf('/inApps/v1/lookup/%s', $orderId);

        return new OrderLookupResponse($this->makeRawRequest('GET', $uri));
    }

    /**
     * Get the transaction info for a single transaction.
     *
     * Fetches a signed transaction record for the given transaction ID and returns
     * the decoded {@see Transaction} object. Use this to look up a specific purchase
     * without paginating through the full history.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/get-transaction-info
     *
     * @throws ValidationException
     */
    public function getTransactionInfo(string $transactionId): Transaction
    {
        $uri  = sprintf('/inApps/v1/transactions/%s', $transactionId);
        $data = $this->makeRawRequest('GET', $uri);

        if (empty($data['signedTransactionInfo']) || !is_string($data['signedTransactionInfo'])) {
            throw new ValidationException('Missing or invalid signedTransactionInfo in transaction info response.');
        }

        $token    = TokenGenerator::decodeToken($data['signedTransactionInfo']);
        $verifier = $this->getTokenVerifier();

        if (!$verifier->verify($token)) {
            throw new ValidationException('Transaction info JWS signature verification failed.');
        }

        return new Transaction($token->claims()->all());
    }

    /**
     * Get the app transaction info for the given transaction.
     *
     * Returns a signed app-level transaction that records the customer's original
     * purchase of the app, including the first-install version and purchase date.
     * This is useful for grandfathering users or verifying device ownership.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/get-app-transaction-info
     *
     * @param string $transactionId The transactionId, originalTransactionId, or appTransactionId.
     * @throws ValidationException
     */
    public function getAppTransactionInfo(string $transactionId): AppTransaction
    {
        $uri  = sprintf('/inApps/v1/transactions/appTransactions/%s', $transactionId);
        $data = $this->makeRawRequest('GET', $uri);

        if (empty($data['signedAppTransactionInfo']) || !is_string($data['signedAppTransactionInfo'])) {
            throw new ValidationException('Missing or invalid signedAppTransactionInfo in app transaction response.');
        }

        $token    = TokenGenerator::decodeToken($data['signedAppTransactionInfo']);
        $verifier = $this->getTokenVerifier();

        if (!$verifier->verify($token)) {
            throw new ValidationException('App transaction JWS signature verification failed.');
        }

        return new AppTransaction($token->claims()->all());
    }

    /**
     * Verify and decode a signed transaction (JWS) without calling Apple.
     *
     * Use this for StoreKit 2's `VerificationResult.jwsRepresentation` sent up by
     * your app, or any `signedTransactionInfo` you already hold. The signature and
     * Apple certificate chain are verified, and the transaction's `bundleId` and
     * `environment` must match this validator's.
     *
     * The payload reflects the transaction at the time it was signed. For the
     * current state (e.g. a later refund), call {@see getTransactionInfo()} or
     * {@see getAllSubscriptionStatuses()}.
     *
     * @see https://developer.apple.com/documentation/storekit/verificationresult/jwsrepresentation-21vgo
     *
     * @throws ValidationException
     */
    public function verifySignedTransaction(string $signedTransaction): Transaction
    {
        $claims = $this->verifySignedPayload($signedTransaction);

        $this->assertClaimMatches($claims, 'bundleId', $this->bundleId, 'bundle ID');
        $this->assertEnvironmentMatches($claims, 'environment');

        return new Transaction($claims);
    }

    /**
     * Verify and decode a signed subscription renewal info (JWS) without calling Apple.
     *
     * Use this for StoreKit 2's `Product.SubscriptionInfo.RenewalInfo` JWS, or any
     * `signedRenewalInfo` you already hold. Renewal info carries no bundle ID, so
     * only the signature, certificate chain and `environment` are checked.
     *
     * @throws ValidationException
     */
    public function verifySignedRenewalInfo(string $signedRenewalInfo): RenewalInfo
    {
        $claims = $this->verifySignedPayload($signedRenewalInfo);

        $this->assertEnvironmentMatches($claims, 'environment');

        return new RenewalInfo($claims);
    }

    /**
     * Verify and decode a signed app transaction (JWS) without calling Apple.
     *
     * Use this for StoreKit 2's `AppTransaction.shared` JWS representation. The
     * signature and Apple certificate chain are verified, and the payload's
     * `bundleId` and `receiptType` must match this validator's.
     *
     * @throws ValidationException
     */
    public function verifySignedAppTransaction(string $signedAppTransaction): AppTransaction
    {
        $claims = $this->verifySignedPayload($signedAppTransaction);

        $this->assertClaimMatches($claims, 'bundleId', $this->bundleId, 'bundle ID');
        $this->assertEnvironmentMatches($claims, 'receiptType');
        $this->assertAppAppleIdMatches($claims['appAppleId'] ?? null);

        return new AppTransaction($claims);
    }

    /**
     * Verify an App Store Server Notification V2 and confirm it belongs to this app.
     *
     * Beyond the signature and certificate chain check that {@see ServerNotification}
     * performs on its own, this requires the notification's bundle ID and environment
     * to match this validator, and in production its app Apple ID when one is configured.
     * A correctly signed notification for another app or environment is rejected.
     *
     * @param array<string, mixed>|string $payload The request body containing `signedPayload`,
     *                                             or the `signedPayload` JWS string itself.
     *
     * @see https://developer.apple.com/documentation/appstoreservernotifications/receiving-app-store-server-notifications
     *
     * @throws ValidationException
     */
    public function verifyNotification(array|string $payload): ServerNotification
    {
        $data = is_string($payload) ? ['signedPayload' => $payload] : $payload;

        $notification = new ServerNotification($data, $this->getTokenVerifier());

        if ($notification->getBundleId() !== $this->bundleId) {
            throw new ValidationException(sprintf(
                'Server notification bundle ID does not match: expected "%s", got "%s".',
                $this->bundleId,
                $notification->getBundleId()
            ));
        }

        if ($notification->getEnvironment() !== $this->environment) {
            throw new ValidationException(sprintf(
                'Server notification environment does not match: expected "%s", got "%s".',
                $this->environment->value,
                $notification->getEnvironment()->value
            ));
        }

        $this->assertAppAppleIdMatches($notification->getAppAppleId());

        return $notification;
    }

    /**
     * Replace the verifier used for Apple-signed JWS payloads.
     *
     * Intended for tests that sign payloads with their own certificate chain.
     */
    public function setTokenVerifier(TokenVerifier $tokenVerifier): static
    {
        $this->tokenVerifier = $tokenVerifier;

        return $this;
    }

    protected function getTokenVerifier(): TokenVerifier
    {
        return $this->tokenVerifier ??= new TokenVerifier();
    }

    /**
     * Decode a JWS, verify it was signed by Apple, and return its claims.
     *
     * @return array<string, mixed>
     * @throws ValidationException
     */
    private function verifySignedPayload(string $jws): array
    {
        $token = TokenGenerator::decodeToken($jws);

        if (!$this->getTokenVerifier()->verify($token)) {
            throw new ValidationException('JWS signature verification failed.');
        }

        return $token->claims()->all();
    }

    /**
     * @param array<string, mixed> $claims
     * @throws ValidationException
     */
    private function assertClaimMatches(array $claims, string $key, string $expected, string $label): void
    {
        $actual = $claims[$key] ?? null;

        if (!is_string($actual) || $actual !== $expected) {
            throw new ValidationException(sprintf(
                'Signed payload %s does not match: expected "%s", got "%s".',
                $label,
                $expected,
                is_scalar($actual) ? (string) $actual : ''
            ));
        }
    }

    /**
     * In production, require the payload's app Apple ID to match the configured one.
     *
     * Skipped when no app Apple ID is configured, and in sandbox, where Apple omits it.
     *
     * @throws ValidationException
     */
    private function assertAppAppleIdMatches(mixed $actual): void
    {
        if ($this->appAppleId === null || $this->environment !== Environment::PRODUCTION) {
            return;
        }

        if (!is_numeric($actual) || (int) $actual !== $this->appAppleId) {
            throw new ValidationException(sprintf(
                'Signed payload app Apple ID does not match: expected %d, got "%s".',
                $this->appAppleId,
                is_scalar($actual) ? (string) $actual : ''
            ));
        }
    }

    /**
     * @param array<string, mixed> $claims
     * @throws ValidationException
     */
    private function assertEnvironmentMatches(array $claims, string $key): void
    {
        $raw = $claims[$key] ?? null;

        try {
            $environment = is_string($raw) ? Environment::fromString($raw) : null;
        } catch (InvalidArgumentException) {
            $environment = null;
        }

        if ($environment !== $this->environment) {
            throw new ValidationException(sprintf(
                'Signed payload environment does not match: expected "%s", got "%s".',
                $this->environment->value,
                is_scalar($raw) ? (string) $raw : ''
            ));
        }
    }

    /**
     * Mark a transaction as finished.
     *
     * Use this for non-consumable, non-renewing subscription, or auto-renewable subscription
     * transactions that StoreKit hasn't already finished. Consumable transactions are finished
     * automatically by Apple once you call {@see sendConsumptionInformation()}.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/finish-a-transaction
     *
     * @throws ValidationException
     */
    public function finishTransaction(string $transactionId): void
    {
        $uri = sprintf('/inApps/v1/transactions/%s/finish', $transactionId);

        $this->makeRawRequest('POST', $uri);
    }

    /**
     * Set or update the app account token for a subscription.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/set-app-account-token
     *
     * @throws ValidationException
     */
    public function setAppAccountToken(string $originalTransactionId, string $appAccountToken): void
    {
        if (!$this->isValidUuidV4($appAccountToken)) {
            throw new ValidationException(
                sprintf('appAccountToken must be a valid UUID v4; "%s" given.', $appAccountToken)
            );
        }

        $uri = sprintf('/inApps/v1/transactions/%s/appAccountToken', $originalTransactionId);

        $this->makeRawRequest('PUT', $uri, [], ['appAccountToken' => $appAccountToken]);
    }

    /**
     * Validate that a string is a canonical UUID v4.
     */
    private function isValidUuidV4(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value
        );
    }

    /**
     * Get all subscription statuses for a customer identified by their
     * original transaction ID.
     *
     * Returns the status for every auto-renewable subscription the customer
     * holds in your app, grouped by subscription group.
     *
     * Pass one or more {@see SubscriptionStatus} values to have Apple return only
     * subscriptions in any of those states, for example active plus billing grace period.
     *
     * @param string $originalTransactionId Any transaction ID that belongs to the customer.
     * @param list<SubscriptionStatus|int> $statuses Optional status filter; empty means all.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/get-all-subscription-statuses
     *
     * @throws ValidationException
     */
    public function getAllSubscriptionStatuses(
        string $originalTransactionId,
        array $statuses = [],
    ): SubscriptionStatusResponse {
        $uri = sprintf('/inApps/v1/subscriptions/%s', $originalTransactionId);

        $queryParams = [];
        if ($statuses !== []) {
            $queryParams['status'] = array_values(array_unique(array_map(
                static fn (SubscriptionStatus|int $status): int => $status instanceof SubscriptionStatus
                    ? $status->value
                    : SubscriptionStatus::from($status)->value,
                $statuses
            )));
        }

        return new SubscriptionStatusResponse($this->makeRawRequest('GET', $uri, $queryParams));
    }

    /**
     * Get the refund history for a customer identified by a transaction ID.
     *
     * Returns up to 20 refunded or revoked transactions per call, sorted by revocation
     * date ascending. When {@see RefundHistoryResponse::$hasMore} is true, pass the
     * returned {@see RefundHistoryResponse::$revision} back as the $revision parameter
     * to fetch the next page.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/get-refund-history
     *
     * @throws ValidationException
     */
    public function getRefundHistory(string $transactionId, ?string $revision = null): RefundHistoryResponse
    {
        $uri         = sprintf('/inApps/v2/refund/lookup/%s', $transactionId);
        $queryParams = $revision !== null ? ['revision' => $revision] : [];

        return new RefundHistoryResponse($this->makeRawRequest('GET', $uri, $queryParams));
    }

    /**
     * Extend the renewal date for a single auto-renewable subscription.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/extend-a-subscription-renewal-date
     *
     * @throws ValidationException
     */
    public function extendSubscriptionRenewalDate(
        string $originalTransactionId,
        ExtendRenewalDateRequest $request,
    ): ExtendRenewalDateResponse {
        $uri = sprintf('/inApps/v1/subscriptions/extend/%s', $originalTransactionId);

        return new ExtendRenewalDateResponse($this->makeRawRequest('PUT', $uri, [], $request->toArray()));
    }

    /**
     * Extend subscription renewal dates for all active subscribers of a product.
     *
     * Returns the requestIdentifier that you can use with
     * {@see getStatusOfSubscriptionRenewalDateExtensions()} to track progress.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/extend-subscription-renewal-dates-for-all-active-subscribers
     *
     * @throws ValidationException
     */
    public function extendSubscriptionRenewalDatesForAllActiveSubscribers(
        MassExtendRenewalDateRequest $request,
    ): string {
        $data = $this->makeRawRequest('POST', '/inApps/v1/subscriptions/extend/mass', [], $request->toArray());

        return (string) ($data['requestIdentifier'] ?? '');
    }

    /**
     * Get the status of a previously requested mass subscription renewal date extension.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/get-status-of-subscription-renewal-date-extensions
     *
     * @throws ValidationException
     */
    public function getStatusOfSubscriptionRenewalDateExtensions(
        string $productId,
        string $requestIdentifier,
    ): MassExtendRenewalDateStatusResponse {
        $uri = sprintf('/inApps/v1/subscriptions/extend/mass/%s/%s', $productId, $requestIdentifier);

        return new MassExtendRenewalDateStatusResponse($this->makeRawRequest('GET', $uri));
    }

    /**
     * Send consumption information for an in-app purchase to the App Store.
     *
     * This informs Apple about how much of a purchase a customer has used and
     * whether it was delivered, which Apple considers when deciding a refund request.
     *
     * A {@see ConsumptionRequest} targets the current v2 endpoint. Passing the
     * deprecated {@see ConsumptionRequestV1} targets the v1 endpoint instead.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/send-consumption-information
     *
     * @throws ValidationException
     */
    public function sendConsumptionInformation(
        string $transactionId,
        ConsumptionRequest|ConsumptionRequestV1 $request,
    ): void {
        if ($request instanceof ConsumptionRequestV1) {
            $uri = sprintf('/inApps/v1/transactions/consumption/%s', $transactionId);
        } else {
            if ($request->getDeliveryStatus() === null) {
                throw new ValidationException(
                    'deliveryStatus is required by the Send Consumption Information endpoint; set it to a DeliveryStatus case.'
                );
            }

            $uri = sprintf('/inApps/v2/transactions/consumption/%s', $transactionId);
        }

        $this->makeRawRequest('PUT', $uri, [], $request->toArray());
    }

    /**
     * Build a query string that repeats array-valued keys rather than using PHP's default
     * bracket notation (e.g. productId=x&productId=y instead of productId[0]=x&...).
     * Apple's API requires the repeated-key form for multi-value parameters.
     *
     * @param array<string, mixed> $params
     */
    private function buildQueryString(array $params): string
    {
        $parts = [];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $parts[] = urlencode($key) . '=' . urlencode((string) $v);
                }
            } else {
                $parts[] = urlencode($key) . '=' . urlencode((string) $value);
            }
        }

        return implode('&', $parts);
    }

    /**
     * Build request headers with the given token.
     *
     * @return array<string,string>
     */
    private function buildHeaders(Token $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token->toString(),
            'Accept'        => 'application/json',
            'User-Agent'    => self::userAgent(),
        ];
    }
}
