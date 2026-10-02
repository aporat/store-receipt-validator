<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use DateInterval;
use Psr\Http\Client\ClientExceptionInterface;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\GooglePlay\JWT\AccessTokenProvider;
use ReceiptValidator\GooglePlay\JWT\ServiceAccountCredentials;
use ReceiptValidator\GooglePlay\JWT\ServiceAccountTokenProvider;
use Throwable;

/**
 * Google Play Developer API (Android Publisher v3) purchases client.
 *
 * Authenticates with a Google Cloud service account that has been granted access
 * to the app in the Play Console. Google has no sandbox endpoint: licence-tester
 * purchases come back from the production API flagged as test purchases, so the
 * {@see Environment} passed here only affects logging and the responses derive
 * their own environment from the payload.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest
 */
class Validator extends AbstractValidator
{
    /** Android Publisher v3 base URL. */
    public const string ENDPOINT = 'https://androidpublisher.googleapis.com/androidpublisher/v3';

    /** @return array{production:string, sandbox:string} */
    protected function endpointMap(): array
    {
        return [
            Environment::PRODUCTION->value => self::ENDPOINT,
            Environment::SANDBOX->value    => self::ENDPOINT,
        ];
    }

    /** The app's package name, e.g. "com.example.app". */
    protected string $packageName;

    /** Service account credentials used by the default token provider. */
    protected ?ServiceAccountCredentials $credentials;

    /** Source of bearer tokens; defaults to {@see ServiceAccountTokenProvider}. */
    protected ?AccessTokenProvider $tokenProvider = null;

    /** Purchase token used by {@see validate()}. */
    protected ?string $purchaseToken = null;

    /**
     * @param string $packageName The Android application ID.
     * @param ServiceAccountCredentials|string|null $credentials Service account credentials, or the
     *        raw JSON key file contents. May be null if you inject an {@see AccessTokenProvider}.
     * @param Environment $environment Informational only; see class description.
     *
     * @throws ValidationException If the JSON credentials cannot be parsed.
     */
    public function __construct(
        string $packageName,
        ServiceAccountCredentials|string|null $credentials = null,
        Environment $environment = Environment::PRODUCTION,
    ) {
        parent::__construct();

        if ($packageName === '') {
            throw new ValidationException('Google Play package name cannot be empty.');
        }

        $this->packageName = $packageName;
        $this->credentials = is_string($credentials) ? ServiceAccountCredentials::fromJson($credentials) : $credentials;
        $this->environment = $environment;
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    /**
     * Replace the token source, e.g. to reuse google/auth or a shared token cache.
     */
    public function setAccessTokenProvider(AccessTokenProvider $provider): static
    {
        $this->tokenProvider = $provider;
        return $this;
    }

    /**
     * The token source in use, creating the default service-account provider on first call.
     *
     * @throws ValidationException If no credentials and no provider are configured.
     */
    public function getAccessTokenProvider(): AccessTokenProvider
    {
        if ($this->tokenProvider !== null) {
            return $this->tokenProvider;
        }

        if ($this->credentials === null) {
            throw new ValidationException(
                'Google Play validator has no credentials: pass service account JSON or set an AccessTokenProvider.'
            );
        }

        return $this->tokenProvider = new ServiceAccountTokenProvider(
            $this->credentials,
            $this->getClient(),
            $this->getRequestFactory(),
            $this->getStreamFactory(),
        );
    }

    /**
     * Set the purchase token used by {@see validate()} (fluent).
     */
    public function setPurchaseToken(string $purchaseToken): self
    {
        $this->purchaseToken = $purchaseToken;
        return $this;
    }

    /**
     * Look up the subscription purchase for the token set via {@see setPurchaseToken()}.
     *
     * Convenience wrapper around {@see getSubscriptionPurchaseV2()} so the Play
     * validator satisfies the common validate() contract.
     *
     * @param string|null $purchaseToken Overrides the token set via {@see setPurchaseToken()}.
     *
     * @throws ValidationException
     */
    public function validate(?string $purchaseToken = null): SubscriptionPurchase
    {
        if ($purchaseToken !== null) {
            $this->setPurchaseToken($purchaseToken);
        }

        if ($this->purchaseToken === null || $this->purchaseToken === '') {
            throw new ValidationException('Missing purchase token for Google Play validation.');
        }

        return $this->getSubscriptionPurchaseV2($this->purchaseToken);
    }

    // ---------------------------------------------------------------------
    // Subscriptions
    // ---------------------------------------------------------------------

    /**
     * Get the current state of a subscription purchase.
     *
     * This is the primary verification call for subscriptions and the one to use
     * when handling a Real-time Developer Notification.
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2/get
     *
     * @throws ValidationException
     */
    public function getSubscriptionPurchaseV2(string $purchaseToken): SubscriptionPurchase
    {
        $this->assertNotEmpty($purchaseToken, 'purchase token');

        $uri = sprintf('/purchases/subscriptionsv2/tokens/%s', rawurlencode($purchaseToken));

        return new SubscriptionPurchase($this->makeRawRequest('GET', $uri));
    }

    /**
     * Acknowledge a subscription purchase.
     *
     * Google refunds and revokes subscriptions that are not acknowledged within three
     * days. Acknowledge either in the app via the billing client or here.
     *
     * Google notes that since 21 May 2025 the subscription ID is no longer required
     * and is not recommended for subscriptions with add-ons, but the REST path still
     * carries the segment, so this library keeps asking for it.
     *
     * @param string      $subscriptionId      The subscription product ID.
     * @param string|null $obfuscatedAccountId Obfuscated app account ID to attach (max 64 chars).
     * @param string|null $obfuscatedProfileId Obfuscated app profile ID to attach (max 64 chars).
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptions/acknowledge
     *
     * @throws ValidationException
     */
    public function acknowledgeSubscription(
        string $subscriptionId,
        string $purchaseToken,
        ?string $developerPayload = null,
        ?string $obfuscatedAccountId = null,
        ?string $obfuscatedProfileId = null,
    ): void {
        $this->assertNotEmpty($subscriptionId, 'subscription ID');
        $this->assertNotEmpty($purchaseToken, 'purchase token');

        $uri = sprintf(
            '/purchases/subscriptions/%s/tokens/%s:acknowledge',
            rawurlencode($subscriptionId),
            rawurlencode($purchaseToken)
        );

        $body = [];
        if ($developerPayload !== null) {
            $body['developerPayload'] = $developerPayload;
        }

        $externalIds = array_filter([
            'obfuscatedAccountId' => $obfuscatedAccountId,
            'obfuscatedProfileId' => $obfuscatedProfileId,
        ], static fn (?string $v): bool => $v !== null && $v !== '');
        if ($externalIds !== []) {
            $body['externalAccountIds'] = $externalIds;
        }

        $this->makeRawRequest('POST', $uri, [], $body === [] ? new \stdClass() : $body);
    }

    /**
     * Cancel a subscription without refunding it.
     *
     * USER_REQUESTED_STOP_RENEWALS stops the next renewal on the user's behalf and the
     * user can still restore the subscription; DEVELOPER_REQUESTED_STOP_PAYMENTS stops
     * the next payment and cannot be undone. Access continues until the current period
     * ends. To end access immediately with a refund use {@see revokeSubscription()}.
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2/cancel
     *
     * @throws ValidationException
     */
    public function cancelSubscription(string $purchaseToken, SubscriptionCancellationType $type): void
    {
        $this->assertNotEmpty($purchaseToken, 'purchase token');

        $uri = sprintf('/purchases/subscriptionsv2/tokens/%s:cancel', rawurlencode($purchaseToken));

        $this->makeRawRequest('POST', $uri, [], [
            'cancellationContext' => ['cancellationType' => $type->value],
        ]);
    }

    /**
     * Extend a subscription by a duration, pushing every line item's expiry out.
     *
     * The etag must match the subscription's current one (see
     * {@see SubscriptionPurchase::getEtag()}); Google rejects a stale etag so that two
     * deferrals cannot race. With $validateOnly the new expiry times are computed and
     * returned but nothing is changed.
     *
     * @param int|DateInterval $deferDuration How much to extend by, in seconds or as an interval.
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2/defer
     *
     * @throws ValidationException
     */
    public function deferSubscription(
        string $purchaseToken,
        string $etag,
        int|DateInterval $deferDuration,
        bool $validateOnly = false,
    ): DeferSubscriptionResponse {
        $this->assertNotEmpty($purchaseToken, 'purchase token');
        $this->assertNotEmpty($etag, 'etag');

        $seconds = $deferDuration instanceof DateInterval
            ? \Carbon\CarbonInterval::instance($deferDuration)->totalSeconds
            : $deferDuration;

        if ($seconds <= 0) {
            throw new ValidationException('Google Play defer duration must be positive.');
        }

        $context = [
            'etag'          => $etag,
            'deferDuration' => sprintf('%ds', (int) round($seconds)),
        ];
        if ($validateOnly) {
            $context['validateOnly'] = true;
        }

        $uri = sprintf('/purchases/subscriptionsv2/tokens/%s:defer', rawurlencode($purchaseToken));

        return new DeferSubscriptionResponse(
            $this->makeRawRequest('POST', $uri, [], ['deferralContext' => $context])
        );
    }

    /**
     * Revoke a subscription purchase immediately and issue a refund.
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2/revoke
     *
     * @throws ValidationException
     */
    public function revokeSubscription(string $purchaseToken, RevocationContext $context): void
    {
        $this->assertNotEmpty($purchaseToken, 'purchase token');

        $uri = sprintf('/purchases/subscriptionsv2/tokens/%s:revoke', rawurlencode($purchaseToken));

        $this->makeRawRequest('POST', $uri, [], $context->toArray());
    }

    // ---------------------------------------------------------------------
    // One-time products
    // ---------------------------------------------------------------------

    /**
     * Get the state of a one-time (in-app) product purchase by token alone.
     *
     * This is the current one-time product lookup and the one to use with purchase
     * options, multi-quantity, rentals and pre-orders. The v1 {@see getProductPurchase()}
     * needs the product ID as well.
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.productsv2/getproductpurchasev2
     *
     * @throws ValidationException
     */
    public function getProductPurchaseV2(string $purchaseToken): ProductPurchaseV2
    {
        $this->assertNotEmpty($purchaseToken, 'purchase token');

        $uri = sprintf('/purchases/productsv2/tokens/%s', rawurlencode($purchaseToken));

        return new ProductPurchaseV2($this->makeRawRequest('GET', $uri), $purchaseToken);
    }

    /**
     * Get the state of a one-time (in-app) product purchase (v1, requires the product ID).
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.products/get
     *
     * @throws ValidationException
     */
    public function getProductPurchase(string $productId, string $purchaseToken): ProductPurchase
    {
        $this->assertNotEmpty($productId, 'product ID');
        $this->assertNotEmpty($purchaseToken, 'purchase token');

        $uri = sprintf(
            '/purchases/products/%s/tokens/%s',
            rawurlencode($productId),
            rawurlencode($purchaseToken)
        );

        return new ProductPurchase($this->makeRawRequest('GET', $uri), $purchaseToken);
    }

    /**
     * Acknowledge a one-time product purchase.
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.products/acknowledge
     *
     * @throws ValidationException
     */
    public function acknowledgeProduct(string $productId, string $purchaseToken, ?string $developerPayload = null): void
    {
        $this->assertNotEmpty($productId, 'product ID');
        $this->assertNotEmpty($purchaseToken, 'purchase token');

        $uri = sprintf(
            '/purchases/products/%s/tokens/%s:acknowledge',
            rawurlencode($productId),
            rawurlencode($purchaseToken)
        );

        $body = $developerPayload !== null ? ['developerPayload' => $developerPayload] : new \stdClass();

        $this->makeRawRequest('POST', $uri, [], $body);
    }

    /**
     * Consume a one-time product purchase so it can be bought again.
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.products/consume
     *
     * @throws ValidationException
     */
    public function consumeProduct(string $productId, string $purchaseToken): void
    {
        $this->assertNotEmpty($productId, 'product ID');
        $this->assertNotEmpty($purchaseToken, 'purchase token');

        $uri = sprintf(
            '/purchases/products/%s/tokens/%s:consume',
            rawurlencode($productId),
            rawurlencode($purchaseToken)
        );

        $this->makeRawRequest('POST', $uri);
    }

    // ---------------------------------------------------------------------
    // Voided purchases
    // ---------------------------------------------------------------------

    /**
     * List purchases that were refunded, charged back or otherwise voided.
     *
     * Google's counterpart to the App Store refund history. Defaults to the last
     * 30 days and to one-time products only; pass {@see VoidedPurchasesParams} with
     * {@see VoidedPurchaseType::INCLUDE_SUBSCRIPTIONS} to include subscriptions.
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.voidedpurchases/list
     *
     * @throws ValidationException
     */
    public function getVoidedPurchases(?VoidedPurchasesParams $params = null): VoidedPurchasesResponse
    {
        return new VoidedPurchasesResponse(
            $this->makeRawRequest('GET', '/purchases/voidedpurchases', ($params ?? new VoidedPurchasesParams())->toQueryParams()),
            $this->environment
        );
    }

    // ---------------------------------------------------------------------
    // Orders
    // ---------------------------------------------------------------------

    /**
     * Get an order: the amount charged, tax, buyer country, the service period a
     * subscription payment covered, and its refund state.
     *
     * @param string $orderId An order ID such as "GPA.1234-5678-9012-34567".
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/orders/get
     *
     * @throws ValidationException
     */
    public function getOrder(string $orderId): Order
    {
        $this->assertNotEmpty($orderId, 'order ID');

        $uri = sprintf('/orders/%s', rawurlencode($orderId));

        return new Order($this->makeRawRequest('GET', $uri), $this->environment);
    }

    /**
     * Get up to 1000 orders in one call. Orders Google cannot find are left out of
     * the result rather than failing the request.
     *
     * @param array<int, string> $orderIds
     * @return array<int, Order>
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/orders/batchget
     *
     * @throws ValidationException
     */
    public function getOrders(array $orderIds): array
    {
        $orderIds = array_values(array_filter($orderIds, static fn (string $id): bool => $id !== ''));

        if ($orderIds === [] || count($orderIds) > 1000) {
            throw new ValidationException('Google Play batch order lookup takes between 1 and 1000 order IDs.');
        }

        $data = $this->makeRawRequest('GET', '/orders:batchGet', ['orderIds' => $orderIds]);

        $orders = [];
        foreach (is_array($data['orders'] ?? null) ? $data['orders'] : [] as $order) {
            if (is_array($order)) {
                $orders[] = new Order($order, $this->environment);
            }
        }

        return $orders;
    }

    /**
     * Refund an order. With $revoke the item or subscription is also taken away
     * immediately; without it the user keeps what they bought.
     *
     * Google recommends refunding with revoke when a purchase fails server-side
     * validation. Orders older than three years cannot be refunded.
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/orders/refund
     *
     * @throws ValidationException
     */
    public function refundOrder(string $orderId, bool $revoke = false): void
    {
        $this->assertNotEmpty($orderId, 'order ID');

        $uri = sprintf('/orders/%s:refund', rawurlencode($orderId));

        $this->makeRawRequest('POST', $uri, $revoke ? ['revoke' => 'true'] : []);
    }

    /**
     * Answer a chargeback review started by a pending refund review notification.
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/orders/reviewrefund
     *
     * @throws ValidationException
     */
    public function reviewRefund(string $orderId, ReviewRefundRequest $request): void
    {
        $this->assertNotEmpty($orderId, 'order ID');
        $this->assertNotEmpty($request->pendingRefundToken, 'pending refund token');

        $uri = sprintf('/orders/%s:reviewrefund', rawurlencode($orderId));

        $this->makeRawRequest('POST', $uri, [], $request->toArray());
    }

    // ---------------------------------------------------------------------
    // Transport
    // ---------------------------------------------------------------------

    /**
     * Perform an authenticated request against the Android Publisher API and return
     * the decoded JSON body.
     *
     * $uri is relative to `/applications/{packageName}`. Endpoints that return 200 or
     * 204 with an empty body (acknowledge, consume, revoke) return an empty array.
     *
     * An empty object (`new \stdClass()`) serialises as `{}`; an empty PHP array would
     * serialise as `[]`, which Google rejects because the body must be a JSON object.
     *
     * @param array<string, mixed>        $queryParams
     * @param array<string, mixed>|object|null $requestBody Serialised as JSON when not null.
     * @return array<string, mixed>
     *
     * @throws APIException        On a non-2xx response.
     * @throws ValidationException On connection failures and undecodable bodies.
     */
    protected function makeRawRequest(
        string $method,
        string $uri,
        array $queryParams = [],
        array|object|null $requestBody = null,
    ): array {
        $url = sprintf('%s/applications/%s%s', $this->endpointForEnvironment(), rawurlencode($this->packageName), $uri);
        if (!empty($queryParams)) {
            $url .= '?' . $this->buildQueryString($queryParams);
        }

        $this->logger->debug('Google Play API request', [
            'method'       => $method,
            'uri'          => $uri,
            'package_name' => $this->packageName,
            'environment'  => $this->environment->value,
            'query'        => $queryParams,
        ]);

        $token = $this->getAccessTokenProvider()->getAccessToken();

        $request = $this->getRequestFactory()->createRequest($method, $url)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('Accept', 'application/json');

        if ($requestBody !== null) {
            $jsonBody = json_encode($requestBody, JSON_THROW_ON_ERROR);
            $request  = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->getStreamFactory()->createStream($jsonBody));
        }

        try {
            $httpResponse = $this->getClient()->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Google Play API connection failed', [
                'method'      => $method,
                'uri'         => $uri,
                'environment' => $this->environment->value,
                'error'       => $e->getMessage(),
            ]);
            throw new ValidationException('Unable to connect to Google Play API - ' . $e->getMessage(), 0, $e);
        }

        $statusCode = $httpResponse->getStatusCode();
        $body       = (string) $httpResponse->getBody();

        $decoded = null;
        if ($body !== '') {
            try {
                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                $decoded = null;
            }
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            [$reason, $errorMessage] = $this->describeError($statusCode, is_array($decoded) ? $decoded : null, $body);

            $this->logger->warning('Google Play API error response', [
                'method'      => $method,
                'uri'         => $uri,
                'environment' => $this->environment->value,
                'status_code' => $statusCode,
                'reason'      => $reason,
                'error'       => $errorMessage,
            ]);

            $label = $reason !== null ? "$statusCode $reason" : (string) $statusCode;

            throw new APIException(
                "Google Play API error [$label]: $errorMessage",
                $statusCode,
                $reason,
                $reason !== null ? APIError::tryFrom($reason) : null,
            );
        }

        if ($body === '') {
            $this->logger->info('Google Play API request successful', [
                'method'      => $method,
                'uri'         => $uri,
                'environment' => $this->environment->value,
            ]);

            return [];
        }

        if (!is_array($decoded)) {
            throw new ValidationException('Invalid response format from Google Play API.');
        }

        $this->logger->info('Google Play API request successful', [
            'method'      => $method,
            'uri'         => $uri,
            'environment' => $this->environment->value,
        ]);

        return $decoded;
    }

    /**
     * Extract the reason and a human-readable message from a Google error envelope.
     *
     * @param array<string, mixed>|null $decoded
     * @return array{0: string|null, 1: string}
     */
    private function describeError(int $statusCode, ?array $decoded, string $body): array
    {
        $error  = is_array($decoded['error'] ?? null) ? $decoded['error'] : [];
        $errors = is_array($error['errors'] ?? null) ? $error['errors'] : [];
        $first  = is_array($errors[0] ?? null) ? $errors[0] : [];

        $reason = isset($first['reason']) ? (string) $first['reason'] : null;
        $known  = $reason !== null ? APIError::tryFrom($reason) : null;

        $message = $known?->message()
            ?? (isset($error['message']) ? (string) $error['message'] : null)
            ?? (isset($first['message']) ? (string) $first['message'] : null)
            ?? match ($statusCode) {
                401     => 'Unauthenticated',
                403     => 'Forbidden',
                404     => 'Not Found',
                410     => 'Gone',
                default => $body !== '' ? $body : 'Unexpected error',
            };

        return [$reason, $message];
    }

    /**
     * Build a query string that repeats array-valued keys (`orderIds=a&orderIds=b`)
     * rather than using PHP's bracket notation, as Google's APIs expect.
     *
     * @param array<string, mixed> $params
     */
    private function buildQueryString(array $params): string
    {
        $parts = [];

        foreach ($params as $key => $value) {
            foreach (is_array($value) ? $value : [$value] as $v) {
                $parts[] = rawurlencode($key) . '=' . rawurlencode((string) $v);
            }
        }

        return implode('&', $parts);
    }

    /**
     * @throws ValidationException
     */
    private function assertNotEmpty(string $value, string $label): void
    {
        if ($value === '') {
            throw new ValidationException(sprintf('Google Play %s cannot be empty.', $label));
        }
    }
}
