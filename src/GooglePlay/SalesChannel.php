<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * Where an order was placed.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/orders#SalesChannel
 */
enum SalesChannel: string
{
    case UNSPECIFIED        = 'SALES_CHANNEL_UNSPECIFIED';
    case IN_APP             = 'IN_APP';
    case PC_EMULATOR        = 'PC_EMULATOR';
    case NATIVE_PC          = 'NATIVE_PC';
    case PLAY_STORE         = 'PLAY_STORE';
    case OUTSIDE_PLAY_STORE = 'OUTSIDE_PLAY_STORE';

    /**
     * Safer version of from() that falls back to UNSPECIFIED on unknown values.
     */
    public static function fromString(?string $value): self
    {
        return $value !== null ? (self::tryFrom($value) ?? self::UNSPECIFIED) : self::UNSPECIFIED;
    }
}
