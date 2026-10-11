<?php

declare(strict_types=1);

namespace ReceiptValidator\iTunes;

use Psr\Http\Client\ClientExceptionInterface;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use Throwable;

/**
 * Legacy iTunes receipt validator using the verifyReceipt endpoint.
 *
 * @deprecated since version 2.0. Use {@see \ReceiptValidator\AppleAppStore\Validator} instead.
 *             Apple has deprecated the verifyReceipt endpoint in favor of the App Store Server API.
 * @see https://developer.apple.com/documentation/appstoreserverapi
 */
class Validator extends AbstractValidator
{
    /** Sandbox endpoint URL. */
    public const string ENDPOINT_SANDBOX = 'https://sandbox.itunes.apple.com';

    /** Production endpoint URL. */
    public const string ENDPOINT_PRODUCTION = 'https://buy.itunes.apple.com';

    /** @return array{production:string, sandbox:string} */
    protected function endpointMap(): array
    {
        return [
            Environment::PRODUCTION->value => self::ENDPOINT_PRODUCTION,
            Environment::SANDBOX->value    => self::ENDPOINT_SANDBOX,
        ];
    }

    /** iTunes receipt data, in base64 format. */
    protected ?string $receiptData = null;

    /** The shared secret for auto-renewable subscriptions. */
    protected ?string $sharedSecret = null;

    /**
     * The bundle identifier the receipt must belong to, or null to skip the check.
     *
     * verifyReceipt only proves that Apple issued the receipt, not that it was issued
     * for your app: a valid receipt from any other app passes. Set this so such a
     * receipt is rejected.
     */
    protected ?string $bundleId = null;

    /**
     * @param string|null $sharedSecret The app-specific shared secret, for auto-renewable subscriptions.
     * @param Environment $environment  The environment each validate() call tries first.
     * @param string|null $bundleId     The bundle identifier receipts must belong to; null skips the check.
     */
    public function __construct(
        ?string $sharedSecret = null,
        Environment $environment = Environment::PRODUCTION,
        ?string $bundleId = null,
    ) {
        parent::__construct();
        $this->sharedSecret = $sharedSecret;
        $this->environment  = $environment;
        $this->setBundleId($bundleId);
    }

    public function getSharedSecret(): ?string
    {
        return $this->sharedSecret;
    }

    public function setSharedSecret(?string $sharedSecret = null): self
    {
        $this->sharedSecret = $sharedSecret;
        return $this;
    }

    /** The bundle identifier receipts must belong to, or null when the check is off. */
    public function getBundleId(): ?string
    {
        return $this->bundleId;
    }

    /**
     * Require validated receipts to belong to this bundle identifier, or pass null
     * (or an empty string) to turn the check off.
     */
    public function setBundleId(?string $bundleId): self
    {
        $this->bundleId = ($bundleId === null || $bundleId === '') ? null : $bundleId;
        return $this;
    }

    /**
     * Validate the receipt.
     *
     * The request goes to the configured environment first. When Apple answers that
     * the receipt belongs to the other environment (21007 or 21008), it is sent there
     * once, for this call only: the validator's environment is not changed. The
     * response's getEnvironment() tells you which environment answered.
     *
     * @throws ValidationException
     */
    public function validate(?string $receiptData = null): Response
    {
        if ($receiptData !== null) {
            $this->setReceiptData($receiptData);
        }

        return $this->makeRequest();
    }

    /**
     * Send the receipt to $environment (the validator's environment when null),
     * switching to the other environment at most once if Apple says the receipt
     * belongs there. Does not change the validator's environment.
     *
     * @throws ValidationException
     */
    protected function makeRequest(?Environment $environment = null): Response
    {
        $environment ??= $this->environment;
        $body          = $this->prepareRequestData();

        [$decodedBody, $status] = $this->send($environment, $body);

        $redirect = match (true) {
            // Sandbox receipt sent to production (21007)
            $environment === Environment::PRODUCTION
                && $status === APIError::SANDBOX_RECEIPT_ON_PRODUCTION->value => Environment::SANDBOX,
            // Production receipt sent to sandbox (21008)
            $environment === Environment::SANDBOX
                && $status === APIError::PRODUCTION_RECEIPT_ON_SANDBOX->value => Environment::PRODUCTION,
            default => null,
        };

        if ($redirect !== null) {
            $this->logger->info('iTunes receipt environment mismatch, retrying in the other environment', [
                'original_environment' => $environment->value,
                'retry_environment'    => $redirect->value,
            ]);
            $environment = $redirect;
            // One switch only: a second mismatch status is reported as an error below.
            [$decodedBody, $status] = $this->send($environment, $body);
        }

        // Anything not VALID or SUBSCRIPTION_EXPIRED is an error
        if ($status !== APIError::VALID->value && $status !== APIError::SUBSCRIPTION_EXPIRED->value) {
            $errorCase = APIError::tryFrom($status);
            $description = $errorCase ? $errorCase->message() : 'An unknown error occurred.';
            $fullMessage = "iTunes API error [$status]: $description";
            $this->logger->warning('iTunes API error response', [
                'environment' => $environment->value,
                'status'      => $status,
                'error'       => $description,
            ]);
            throw new ValidationException($fullMessage, $status);
        }

        $response = new Response($decodedBody, $environment);

        $this->assertBundleId($decodedBody);

        $this->logger->info('iTunes API request successful', [
            'environment' => $environment->value,
            'status'      => $status,
        ]);

        return $response;
    }

    /**
     * POST the request body to one environment's verifyReceipt endpoint.
     *
     * @return array{0: array<mixed>, 1: int} The decoded body and its integer status.
     * @throws ValidationException
     */
    private function send(Environment $environment, string $body): array
    {
        $map      = $this->endpointMap();
        $endpoint = $map[$environment->value];

        $this->logger->debug('iTunes API request', [
            'environment' => $environment->value,
        ]);

        $request = $this->getRequestFactory()
            ->createRequest('POST', $endpoint . '/verifyReceipt')
            ->withBody($this->getStreamFactory()->createStream($body));

        foreach ($this->buildHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        try {
            $httpResponse = $this->getClient()->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('iTunes API connection failed', [
                'environment' => $environment->value,
                'error'       => $e->getMessage(),
            ]);
            throw new ValidationException('Unable to connect to iTunes server - ' . $e->getMessage(), 0, $e);
        }

        $httpStatus = $httpResponse->getStatusCode();
        if ($httpStatus !== 200) {
            $this->logger->warning('iTunes API unexpected HTTP status', [
                'environment' => $environment->value,
                'status_code' => $httpStatus,
            ]);
            throw new ValidationException("Unable to get response from iTunes server (HTTP $httpStatus)");
        }

        $raw = (string) $httpResponse->getBody();

        try {
            $decodedBody = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new ValidationException('iTunes server returned invalid JSON: ' . $e->getMessage());
        }

        if (!is_array($decodedBody)) {
            throw new ValidationException('iTunes server returned an unexpected response structure.');
        }

        $status = $decodedBody['status'] ?? null;
        if (!is_int($status)) {
            $this->logger->warning('iTunes API response without an integer status', [
                'environment' => $environment->value,
            ]);
            throw new ValidationException('iTunes server response has no valid status.');
        }

        return [$decodedBody, $status];
    }

    /**
     * When a bundle identifier is configured, require the receipt's bundle_id (and,
     * for iOS 6-style receipts, bid) to match it.
     *
     * @param array<mixed> $decodedBody
     * @throws ValidationException
     */
    private function assertBundleId(array $decodedBody): void
    {
        if ($this->bundleId === null) {
            return;
        }

        $receipt = is_array($decodedBody['receipt'] ?? null) ? $decodedBody['receipt'] : [];
        $found   = [];
        foreach (['bundle_id', 'bid'] as $key) {
            if (array_key_exists($key, $receipt)) {
                $found[] = $receipt[$key];
            }
        }

        if ($found === []) {
            $this->logger->warning('iTunes receipt has no bundle identifier', [
                'expected_bundle_id' => $this->bundleId,
            ]);
            throw new ValidationException('Receipt does not contain a bundle identifier.');
        }

        foreach ($found as $value) {
            if ($value !== $this->bundleId) {
                $this->logger->warning('iTunes receipt bundle identifier mismatch', [
                    'expected_bundle_id' => $this->bundleId,
                    'bundle_id'          => is_scalar($value) ? (string) $value : null,
                ]);
                throw new ValidationException('Receipt bundle identifier does not match the expected bundle identifier.');
            }
        }
    }

    /**
     * Prepare request data (JSON).
     *
     * @throws ValidationException
     */
    protected function prepareRequestData(): string
    {
        if ($this->receiptData === null || $this->receiptData === '') {
            throw new ValidationException('Receipt data must be set before validation.');
        }

        $payload = [
            'receipt-data' => $this->receiptData,
        ];

        if ($this->sharedSecret !== null && $this->sharedSecret !== '') {
            $payload['password'] = $this->sharedSecret;
        }

        try {
            return json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new ValidationException('Unable to encode request for iTunes server: ' . $e->getMessage());
        }
    }

    public function getReceiptData(): ?string
    {
        return $this->receiptData;
    }

    /**
     * Set receipt data, either in base64 or as raw JSON (auto-encodes JSON).
     */
    public function setReceiptData(string $receiptData = ''): self
    {
        $trimmed = ltrim($receiptData);
        // If it looks like raw JSON, base64-encode it to conform with Apple's API
        $this->receiptData = ($trimmed !== '' && $trimmed[0] === '{')
            ? base64_encode($receiptData)
            : $receiptData;

        return $this;
    }

    /**
     * Build request headers.
     *
     * @return array<string,string>
     */
    private function buildHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }
}
