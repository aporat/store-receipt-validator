<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

use Psr\Http\Client\ClientExceptionInterface;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Verifies Amazon Appstore receipts with the Receipt Verification Service (RVS).
 *
 * A receipt is identified by the receipt ID and user ID the Appstore SDK hands the app
 * after a purchase. The shared secret identifies the developer; the RVS sandbox accepts
 * any non-empty value, production checks it.
 *
 * Failures are reported as a {@see ValidationException} whose code is the HTTP status
 * Amazon returned, so {@see APIError::fromException()} recovers the documented case.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html
 */
final class Validator extends AbstractValidator
{
    /** Amazon RVS sandbox endpoint. */
    public const string ENDPOINT_SANDBOX = 'https://appstore-sdk.amazon.com/sandbox';

    /** Amazon RVS production endpoint. */
    public const string ENDPOINT_PRODUCTION = 'https://appstore-sdk.amazon.com';

    /** Placeholder written over the shared secret wherever a message could carry it. */
    private const string REDACTED = '[redacted]';

    /** @return array{production:string, sandbox:string} */
    protected function endpointMap(): array
    {
        return [
            Environment::PRODUCTION->value => self::ENDPOINT_PRODUCTION,
            Environment::SANDBOX->value    => self::ENDPOINT_SANDBOX,
        ];
    }

    /** User ID. */
    protected ?string $userId = null;

    /** Receipt ID. */
    protected ?string $receiptId = null;

    /** Developer secret. */
    protected ?string $developerSecret = null;

    public function __construct(string $developerSecret, Environment $environment = Environment::PRODUCTION)
    {
        parent::__construct();
        $this->developerSecret = $developerSecret;
        $this->environment     = $environment;
    }

    /**
     * Verify a receipt with Amazon's RVS.
     *
     * The receipt ID and user ID may be passed here or set beforehand with
     * {@see setReceiptId()} and {@see setUserId()}.
     *
     * @throws ValidationException When a parameter is missing, the request fails, or
     *                             Amazon rejects the receipt. For a rejection the exception
     *                             code is the HTTP status; see {@see APIError}.
     */
    public function validate(?string $receiptId = null, ?string $userId = null): Response
    {
        if ($receiptId !== null) {
            $this->receiptId = $receiptId;
        }
        if ($userId !== null) {
            $this->userId = $userId;
        }

        return $this->makeRequest();
    }

    /**
     * Perform the HTTP request and parse the response.
     *
     * @throws ValidationException
     */
    protected function makeRequest(): Response
    {
        if ($this->developerSecret === null || $this->developerSecret === '') {
            throw new ValidationException('Missing Amazon developer secret');
        }
        if ($this->userId === null || $this->userId === '') {
            throw new ValidationException('Missing Amazon userId');
        }
        if ($this->receiptId === null || $this->receiptId === '') {
            throw new ValidationException('Missing Amazon receiptId');
        }

        $endpoint = $this->endpointForEnvironment();

        // URL-encode path segments to be safe with special characters
        $path = sprintf(
            '/version/1.0/verifyReceiptId/developer/%s/user/%s/receiptId/%s',
            rawurlencode($this->developerSecret),
            rawurlencode($this->userId),
            rawurlencode($this->receiptId)
        );

        $this->logger->debug('Amazon API request', [
            'environment' => $this->environment->value,
            'user_id'     => $this->userId,
            'receipt_id'  => $this->receiptId,
        ]);

        $request = $this->getRequestFactory()
            ->createRequest('GET', $endpoint . $path)
            ->withHeader('Accept', 'application/json')
            ->withHeader('User-Agent', self::userAgent());

        try {
            $httpResponse = $this->getClient()->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            // The shared secret is a path segment of the request URL, and HTTP clients
            // commonly quote the URL in their exception messages. The message is redacted
            // and the original exception is deliberately not chained, so neither the log
            // nor a serialised exception chain can carry the secret.
            $message = $this->redactSecret($e->getMessage());

            $this->logger->error('Amazon API connection failed', [
                'environment' => $this->environment->value,
                'exception'   => $e::class,
                'error'       => $message,
            ]);

            throw new ValidationException('Amazon validation request failed: ' . $message);
        }

        $status  = $httpResponse->getStatusCode();
        $rawBody = (string) $httpResponse->getBody();
        $decoded = json_decode($rawBody, true);

        if ($status !== 200) {
            $error = APIError::tryFrom($status);

            // Amazon documents outcomes by status code only. Fall back to whatever the
            // body says when the status is not one of the documented ones.
            $bodyMessage = is_array($decoded) && isset($decoded['message'])
                ? (string) $decoded['message']
                : trim($rawBody);
            $human = $error?->message() ?? ($bodyMessage !== '' ? $bodyMessage : 'An unknown error occurred.');

            $this->logger->warning('Amazon API error response', [
                'environment' => $this->environment->value,
                'status_code' => $status,
                'error'       => $error?->name,
                'message'     => $human,
            ]);

            throw new ValidationException("Amazon API error [$status]: $human", $status);
        }

        if (!is_array($decoded)) {
            throw new ValidationException('Amazon API returned invalid JSON: ' . json_last_error_msg(), $status);
        }

        $this->logger->info('Amazon API request successful', [
            'environment' => $this->environment->value,
            'user_id'     => $this->userId,
            'receipt_id'  => $this->receiptId,
        ]);

        return new Response($decoded, $this->environment, $this->userId);
    }

    /**
     * Replace the shared secret, in raw and URL-encoded form, wherever it appears in $text.
     */
    private function redactSecret(string $text): string
    {
        if ($this->developerSecret === null || $this->developerSecret === '') {
            return $text;
        }

        $needles = array_unique([$this->developerSecret, rawurlencode($this->developerSecret)]);

        return str_replace($needles, self::REDACTED, $text);
    }

    public function getDeveloperSecret(): ?string
    {
        return $this->developerSecret;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getReceiptId(): ?string
    {
        return $this->receiptId;
    }

    public function setReceiptId(?string $receiptId): self
    {
        $this->receiptId = $receiptId;
        return $this;
    }
}
