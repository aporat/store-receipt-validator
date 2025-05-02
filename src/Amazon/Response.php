<?php

namespace ReceiptValidator\Amazon;

use ReceiptValidator\AbstractResponse;
use ReceiptValidator\Exceptions\ValidationException;

class Response extends AbstractResponse
{
    /**
     * Parse JSON response into receipt and transactions.
     *
     * @return $this
     * @throws ValidationException
     */
    public function parse(): self
    {
        if (!is_array($this->rawData)) {
            throw new ValidationException('Response must be an array');
        }

        $this->transactions = [new Transaction($this->rawData)];

        return $this;
    }

    /**
     * @return array<Transaction>
     */
    public function getTransactions(): array
    {
        /** @var array<Transaction> */
        return parent::getTransactions();
    }
}
