<?php

namespace ReceiptValidator\AppleAppStore;

use ArrayAccess;
use ReceiptValidator\AbstractResponse;
use ReceiptValidator\AppleAppStore\JWT\AppStoreJwsVerifier;
use ReceiptValidator\AppleAppStore\JWT\Parser;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReturnTypeWillChange;
use Throwable;

class Response extends AbstractResponse implements ArrayAccess
{
    /** @var array|null */
    protected ?array $raw_data;

    /** @var string|null */
    protected ?string $revision = null;

    /** @var string|null */
    protected ?string $bundle_id = null;

    /** @var int|null */
    protected ?int $app_apple_id = null;

    /** @var bool|null */
    protected ?bool $has_more = null;

    /** @var array */
    protected array $signed_transactions = [];

    /** @return string|null */
    public function getRevision(): ?string
    {
        return $this->revision;
    }

    /** @return string|null */
    public function getBundleId(): ?string
    {
        return $this->bundle_id;
    }

    /** @return int|null */
    public function getAppAppleId(): ?int
    {
        return $this->app_apple_id;
    }

    /** @return bool|null */
    public function hasMore(): ?bool
    {
        return $this->has_more;
    }

    /** @return array */
    public function getSignedTransactions(): array
    {
        return $this->signed_transactions;
    }

    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        $this->raw_data[$offset] = $value;
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
        if (!is_array($this->raw_data)) {
            throw new ValidationException('Response must be an array');
        }

        $data = $this->raw_data;

        $this->revision = $data['revision'] ?? null;
        $this->bundle_id = $data['bundleId'] ?? null;
        $this->app_apple_id = $data['appAppleId'] ?? null;

        if (($data['environment'] ?? null) === 'Production') {
            $this->environment = Environment::PRODUCTION;
        } else {
            $this->environment = Environment::SANDBOX;
        }

        $this->has_more = $data['hasMore'] ?? null;
        $this->signed_transactions = $data['signedTransactions'] ?? [];

        if (!empty($this->signed_transactions)) {
            foreach ($this->signed_transactions as $purchase_item_data) {
                try {
                    $jws = Parser::toJws($purchase_item_data);

                    $verifier = new AppStoreJwsVerifier();
                    if ($verifier->verify($jws)) {
                        $this->transactions[] = new Transaction($jws->getClaims());
                    }
                } catch (Throwable) {
                    // Ignore individual failure
                }
            }
        }

        return $this;
    }

    #[ReturnTypeWillChange]
    public function offsetGet($offset): mixed
    {
        return $this->raw_data[$offset] ?? null;
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        unset($this->raw_data[$offset]);
    }

    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return isset($this->raw_data[$offset]);
    }
}
