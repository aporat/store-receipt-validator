<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\Environment;
use ReceiptValidator\Support\ValueCasting;

/**
 * Represents the decoded payload of a signed app transaction (JWSAppTransaction).
 *
 * An app transaction is a record of a customer's original purchase of your app,
 * cryptographically signed by the App Store. It is distinct from an in-app purchase
 * transaction and is useful for verifying first-install dates and build versions.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/get-app-transaction-info
 * @see https://developer.apple.com/documentation/storekit/apptransaction
 */
final readonly class AppTransaction
{
    use ValueCasting;

    /** The unique identifier of the app download transaction assigned by the App Store. */
    public ?string $appTransactionId;

    /** The unique identifier of the app in the App Store. */
    public ?int $appAppleId;

    /** The bundle identifier of the app. */
    public ?string $bundleId;

    /**
     * The app version that this transaction applies to (the currently installed version
     * at the time of the request).
     */
    public ?string $applicationVersion;

    /**
     * A number that identifies the version of the app that the customer originally purchased.
     * This corresponds to the CFBundleVersion on iOS or the build number on macOS.
     */
    public ?int $versionExternalIdentifier;

    /** The app version that the customer first purchased from the App Store. */
    public ?string $originalApplicationVersion;

    /** The platform on which the customer originally purchased the app. */
    public ?string $originalPlatform;

    /**
     * The granular type of the App Store receipt.
     *
     * Possible values: Production, ProductionSandbox, ProductionVR, ProductionSandboxVR
     */
    public ?string $receiptType;

    /**
     * The Base64-encoded device verification value. Use this together with
     * {@see $deviceVerificationNonce} to verify the transaction belongs to the device.
     */
    public ?string $deviceVerification;

    /** The UUID used to compute the device verification value. */
    public ?string $deviceVerificationNonce;

    /** The time the App Store generated the receipt. */
    public ?CarbonImmutable $receiptCreationDate;

    /** The original date the customer purchased the app. */
    public ?CarbonImmutable $originalPurchaseDate;

    /** The date the customer pre-ordered the app, if applicable. */
    public ?CarbonImmutable $preorderDate;

    /** The time the App Store signed the JWS data. */
    public ?CarbonImmutable $signedDate;

    /** The server environment in which the transaction was signed. */
    public Environment $environment;

    /**
     * @param array<string, mixed> $data The decoded claims from a JWS app transaction.
     */
    public function __construct(array $data = [])
    {
        $this->appTransactionId             = $this->toString($data, 'appTransactionId');
        $this->appAppleId                   = $this->toInt($data, 'appAppleId');
        $this->bundleId                     = $this->toString($data, 'bundleId');
        $this->applicationVersion           = $this->toString($data, 'applicationVersion');
        $this->versionExternalIdentifier    = $this->toInt($data, 'versionExternalIdentifier');
        $this->originalApplicationVersion   = $this->toString($data, 'originalApplicationVersion');
        $this->originalPlatform             = $this->toString($data, 'originalPlatform');
        $this->receiptType                  = $this->toString($data, 'receiptType');
        $this->deviceVerification           = $this->toString($data, 'deviceVerification');
        $this->deviceVerificationNonce      = $this->toString($data, 'deviceVerificationNonce');
        $this->environment                  = $this->toEnvironment($data, 'environment');
        $this->receiptCreationDate          = $this->toDateFromMs($data, 'receiptCreationDate');
        $this->originalPurchaseDate         = $this->toDateFromMs($data, 'originalPurchaseDate');
        $this->preorderDate                 = $this->toDateFromMs($data, 'preorderDate');
        $this->signedDate                   = $this->toDateFromMs($data, 'signedDate');
    }

    public function getAppTransactionId(): ?string
    {
        return $this->appTransactionId;
    }

    public function getAppAppleId(): ?int
    {
        return $this->appAppleId;
    }

    public function getBundleId(): ?string
    {
        return $this->bundleId;
    }

    public function getApplicationVersion(): ?string
    {
        return $this->applicationVersion;
    }

    public function getVersionExternalIdentifier(): ?int
    {
        return $this->versionExternalIdentifier;
    }

    public function getOriginalApplicationVersion(): ?string
    {
        return $this->originalApplicationVersion;
    }

    public function getOriginalPlatform(): ?string
    {
        return $this->originalPlatform;
    }

    public function getReceiptType(): ?string
    {
        return $this->receiptType;
    }

    public function getDeviceVerification(): ?string
    {
        return $this->deviceVerification;
    }

    public function getDeviceVerificationNonce(): ?string
    {
        return $this->deviceVerificationNonce;
    }

    public function getReceiptCreationDate(): ?CarbonInterface
    {
        return $this->receiptCreationDate;
    }

    public function getOriginalPurchaseDate(): ?CarbonInterface
    {
        return $this->originalPurchaseDate;
    }

    public function getPreorderDate(): ?CarbonInterface
    {
        return $this->preorderDate;
    }

    public function getSignedDate(): ?CarbonInterface
    {
        return $this->signedDate;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }
}
