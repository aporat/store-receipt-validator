<?php

namespace ReceiptValidator\Amazon;

use ReceiptValidator\AbstractResponse;
use ReceiptValidator\Exceptions\ValidationException;

class Response extends AbstractResponse
{

    /**
     * Parse JSON response into receipt and purchases.
     *
     * @return $this
     * @throws ValidationException
     */
    public function parseData(): self
    {
        if ($this->raw_data == null || !is_array($this->raw_data)) {
            throw new ValidationException('Response must be an array');
        }

        $this->purchases = [new Transaction($this->raw_data)];

        return $this;
    }

}
