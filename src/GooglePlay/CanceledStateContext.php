<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\Support\ValueCasting;

/**
 * Why a subscription is in the CANCELED state (`canceledStateContext`).
 *
 * Google sets exactly one of four marker objects; only the user-initiated one carries
 * data (the cancel time and an optional survey answer).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#CanceledStateContext
 */
final readonly class CanceledStateContext
{
    use ValueCasting;

    /** Who canceled, or null if Google sent a member this library does not know. */
    public ?CancellationSource $source;

    /** When the user canceled. Only set for user-initiated cancellations. */
    public ?CarbonImmutable $cancelTime;

    /** The survey reason, or null if the user gave none. */
    public ?CancelSurveyReason $surveyReason;

    /** Free-text survey answer when the reason is OTHERS. */
    public ?string $surveyUserInput;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $source = null;
        foreach (CancellationSource::cases() as $case) {
            if (array_key_exists($case->value, $data) && $data[$case->value] !== null) {
                $source = $case;
                break;
            }
        }
        $this->source = $source;

        $user   = is_array($data[CancellationSource::USER->value] ?? null) ? $data[CancellationSource::USER->value] : [];
        $survey = is_array($user['cancelSurveyResult'] ?? null) ? $user['cancelSurveyResult'] : [];

        $this->cancelTime      = $this->toDateFromRfc3339($user, 'cancelTime');
        $reason                = $this->toString($survey, 'reason');
        $this->surveyReason    = $reason !== null ? CancelSurveyReason::fromString($reason) : null;
        $this->surveyUserInput = $this->toString($survey, 'reasonUserInput');
    }

    public function getSource(): ?CancellationSource
    {
        return $this->source;
    }

    public function isUserInitiated(): bool
    {
        return $this->source === CancellationSource::USER;
    }

    public function isSystemInitiated(): bool
    {
        return $this->source === CancellationSource::SYSTEM;
    }

    public function isDeveloperInitiated(): bool
    {
        return $this->source === CancellationSource::DEVELOPER;
    }

    public function isReplacement(): bool
    {
        return $this->source === CancellationSource::REPLACEMENT;
    }

    public function getCancelTime(): ?CarbonInterface
    {
        return $this->cancelTime;
    }

    public function getSurveyReason(): ?CancelSurveyReason
    {
        return $this->surveyReason;
    }

    public function getSurveyUserInput(): ?string
    {
        return $this->surveyUserInput;
    }
}
