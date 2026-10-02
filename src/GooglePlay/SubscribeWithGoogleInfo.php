<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\Support\ValueCasting;

/**
 * Subscriber details Google shares for Subscribe with Google purchases.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#SubscribeWithGoogleInfo
 */
final readonly class SubscribeWithGoogleInfo
{
    use ValueCasting;

    public ?string $profileId;

    public ?string $profileName;

    public ?string $emailAddress;

    public ?string $givenName;

    public ?string $familyName;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->profileId    = $this->toString($data, 'profileId');
        $this->profileName  = $this->toString($data, 'profileName');
        $this->emailAddress = $this->toString($data, 'emailAddress');
        $this->givenName    = $this->toString($data, 'givenName');
        $this->familyName   = $this->toString($data, 'familyName');
    }

    public function getProfileId(): ?string
    {
        return $this->profileId;
    }

    public function getProfileName(): ?string
    {
        return $this->profileName;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }
}
