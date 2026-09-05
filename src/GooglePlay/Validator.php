<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

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
     * @param string $subscriptionId The subscription product ID.
     *
     * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptions/acknowledge
     *
     * @throws ValidationException
     */
    public function acknowledgeSubscription(
        string $subscriptionId,
        string $purchaseToken,
        ?string $developerPayload = null,
    ): void {
        $this->assertNotEmpty($subscriptionId, 'subscription ID');
        $this->assertNotEmpty($purchaseToken, 'purchase token');

        $uri = sprintf(
            '/purchases/subscriptions/%s/tokens/%s:acknowledge',
            rawurlencode($subscriptionId),
            rawurlencode($purchaseToken)
        );

        $body = $developerPayload !== null ? ['developerPayload' => $developerPayload] : [];

        $this->makeRawRequest('POST', $uri, [], $body);
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
     * Get the state of a one-time (in-app) product purchase.
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

        $body = $developerPayload !== null ? ['developerPayload' => $developerPayload] : [];

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
    // Transport
    // ---------------------------------------------------------------------

    /**
     * Perform an authenticated request against the Android Publisher API and return
     * the decoded JSON body.
     *
     * $uri is relative to `/applications/{packageName}`. Endpoints that return 200 or
     * 204 with an empty body (acknowledge, consume, revoke) return an empty array.
     *
     * @param array<string, mixed>      $queryParams
     * @param array<string, mixed>|null $requestBody Serialised as JSON when not null.
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function makeRawRequest(
        string $method,
        string $uri,
        array $queryParams = [],
        ?array $requestBody = null,
    ): array {
        $url = sprintf('%s/applications/%s%s', $this->endpointForEnvironment(), rawurlencode($this->packageName), $uri);
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
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

            throw new ValidationException("Google Play API error [$label]: $errorMessage", $statusCode);
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
     * @throws ValidationException
     */
    private function assertNotEmpty(string $value, string $label): void
    {
        if ($value === '') {
            throw new ValidationException(sprintf('Google Play %s cannot be empty.', $label));
        }
    }
}
