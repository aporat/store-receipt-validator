<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * Who canceled a subscription, derived from which `canceledStateContext` member is set.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#CanceledStateContext
 */
enum CancellationSource: string
{
    /** The user canceled, possibly leaving a survey answer. */
    case USER        = 'userInitiatedCancellation';
    /** Google canceled, e.g. after repeated payment failure. */
    case SYSTEM      = 'systemInitiatedCancellation';
    /** The developer canceled through the API. */
    case DEVELOPER   = 'developerInitiatedCancellation';
    /** The subscription was replaced by a new purchase. */
    case REPLACEMENT = 'replacementCancellation';
}
