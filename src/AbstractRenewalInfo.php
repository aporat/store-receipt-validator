<?php

declare(strict_types=1);

namespace ReceiptValidator;

use ReceiptValidator\Support\ValueCasting;

/**
 * Base class for RenewalInfo models (iTunes, AppleAppStore).
 */
abstract readonly class AbstractRenewalInfo
{
    use ValueCasting;
}
