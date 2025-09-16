<?php

namespace ReceiptValidator\AppleAppStore;

use ReceiptValidator\AbstractResponse;
use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Encapsulates a decoded response from the App Store Server API.
 *
 * This immutable data object provides structured access to the transaction history
 * and other metadata returned by Apple's API.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/transactionhistoryresponse
 */
final class Response extends AbstractResponse
{
    /**
     * A string that indicates the version of the response.
     */
    public readonly ?string $revision;

    /**
     * The bundle identifier of the app.
     */
    public readonly ?string $bundleId;

    /**
     * The unique identifier of the app in the App Store.
     */
    public readonly ?int $appAppleId;

    /**
     * A Boolean value that indicates whether the App Store has more transaction
     * data to send.
     */
    public readonly bool $hasMore;

    /**
     * An array of signed JSON Web Signature (JWS) transactions.
     *
     * @var array<string>
     */
    public readonly array $signedTransactions;

    /**
     * Constructs the Response object and initializes its state.
     *
     * The definitive environment is determined from the response payload, as it is the
     * authoritative source, overriding any environment set during the request.
     *
     * @param array<string, mixed> $data The raw decoded JSON data from the API response.
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        // For this response type, the 'environment' field in the payload is the source of truth.
        $definitiveEnvironment = ($data['environment'] ?? 'Sandbox') === 'Production'
            ? Environment::PRODUCTION
            : Environment::SANDBOX;

        parent::__construct($data, $definitiveEnvironment);

        // Initialize readonly properties that belong to this class.
        $this->revision = $data['revision'] ?? null;
        $this->bundleId = $data['bundleId'] ?? null;
        $this->appAppleId = $data['appAppleId'] ?? null;
        $this->hasMore = $data['hasMore'] ?? false;
        $this->signedTransactions = $data['signedTransactions'] ?? [];

        $this->parseSignedTransactions();
    }

    /**
     * Decodes and verifies the JWS-signed transactions from the response payload.
     */
    private function parseSignedTransactions(): void
    {
        $verifier = new TokenVerifier();

        foreach ($this->signedTransactions as $signedTransaction) {
            $token = TokenGenerator::decodeToken($signedTransaction);

            if ($verifier->verify($token)) {
                $this->transactions[] = new Transaction($token->claims()->all());
            }
        }
    }

    /**
     * Returns the revision string from the response body.
     */
    public function getRevision(): ?string
    {
        return $this->revision;
    }

    /**
     * Returns the bundle identifier of the application.
     */
    public function getBundleId(): ?string
    {
        return $this->bundleId;
    }

    /**
     * Returns the unique identifier for the application in the App Store.
     */
    public function getAppAppleId(): ?int
    {
        return $this->appAppleId;
    }

    /**
     * Returns true if more transaction data is available from the API.
     */
    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    /**
     * Returns the raw array of JWS-formatted signed transaction strings.
     *
     * @return array<string>
     */
    public function getSignedTransactions(): array
    {
        return $this->signedTransactions;
    }

    /**
     * Returns the collection of parsed transaction objects.
     *
     * @return array<Transaction>
     */
    public function getTransactions(): array
    {
        /** @var array<Transaction> */
        return parent::getTransactions();
    }
}
