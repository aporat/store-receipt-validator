<?php

namespace ReceiptValidator\AppleAppStore;

use ArrayAccess;
use ReceiptValidator\AbstractResponse;
use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReturnTypeWillChange;

/**
 * Represents a decoded response from the App Store Server API.
 * @implements ArrayAccess<string, mixed>
 */
class Response extends AbstractResponse implements ArrayAccess
{
    /** @var array<string, mixed>|null Raw transaction data */
    protected ?array $rawData;

    /** @var string|null The latest revision string */
    protected ?string $revision = null;

    /** @var string|null The bundle ID of the app */
    protected ?string $bundleId = null;

    /** @var int|null The App Store app ID */
    protected ?int $appAppleId = null;

    /** @var bool|null Indicates if more transactions are available */
    protected ?bool $hasMore = null;

    /** @var array<string> Signed transactions (JWS strings) */
    protected array $signedTransactions = [];

    /** @return string|null */
    public function getRevision(): ?string
    {
        return $this->revision;
    }

    /** @return string|null */
    public function getBundleId(): ?string
    {
        return $this->bundleId;
    }

    /** @return int|null */
    public function getAppAppleId(): ?int
    {
        return $this->appAppleId;
    }

    /** @return bool|null */
    public function hasMore(): ?bool
    {
        return $this->hasMore;
    }

    /** @return array<string> */
    public function getSignedTransactions(): array
    {
        return $this->signedTransactions;
    }

    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        $this->rawData[$offset] = $value;
        $this->parse();
    }

    /**
     * Parse transaction data.
     *
     * @return $this
     * @throws ValidationException
     */
    public function parse(): self
    {
        if (!is_array($this->rawData)) {
            throw new ValidationException('Response must be an array');
        }

        $data = $this->rawData;

        $this->revision = $data['revision'] ?? null;
        $this->bundleId = $data['bundleId'] ?? null;
        $this->appAppleId = $data['appAppleId'] ?? null;

        $this->environment = ($data['environment'] ?? null) === 'Production'
            ? Environment::PRODUCTION
            : Environment::SANDBOX;

        $this->hasMore = $data['hasMore'] ?? null;
        $this->signedTransactions = $data['signedTransactions'] ?? [];

        foreach ($this->signedTransactions as $signedTransaction) {
            $token = TokenGenerator::decodeToken($signedTransaction);

            $verifier = new TokenVerifier();
            if ($verifier->verify($token)) {
                $this->transactions[] = new Transaction($token->claims()->all());
            }
        }

        return $this;
    }

    #[ReturnTypeWillChange]
    public function offsetGet($offset): mixed
    {
        return $this->rawData[$offset] ?? null;
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        unset($this->rawData[$offset]);
    }

    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return isset($this->rawData[$offset]);
    }
}
