<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The reason the user picked in the cancellation survey.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#CancelSurveyReason
 */
enum CancelSurveyReason: string
{
    case UNSPECIFIED      = 'CANCEL_SURVEY_REASON_UNSPECIFIED';
    case NOT_ENOUGH_USAGE = 'CANCEL_SURVEY_REASON_NOT_ENOUGH_USAGE';
    case TECHNICAL_ISSUES = 'CANCEL_SURVEY_REASON_TECHNICAL_ISSUES';
    case COST_RELATED     = 'CANCEL_SURVEY_REASON_COST_RELATED';
    case FOUND_BETTER_APP = 'CANCEL_SURVEY_REASON_FOUND_BETTER_APP';
    case OTHERS           = 'CANCEL_SURVEY_REASON_OTHERS';

    /**
     * Safer version of from() that falls back to UNSPECIFIED on unknown values.
     */
    public static function fromString(?string $value): self
    {
        return $value !== null ? (self::tryFrom($value) ?? self::UNSPECIFIED) : self::UNSPECIFIED;
    }
}
