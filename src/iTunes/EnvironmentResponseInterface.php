<?php

namespace ReceiptValidator\iTunes;

interface EnvironmentResponseInterface
{
    public function isSandbox(): bool;

    public function isProduction(): bool;
}
