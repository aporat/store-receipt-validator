<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;

/**
 * Represents an App Store Server Notification V2.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/responsebodyv2decodedpayload
 */
class ServerNotification
{
    protected ServerNotificationType $notificationType;
    protected string $rawNotificationType = '';
    protected ?ServerNotificationSubtype $subtype = null;
    protected ?string $rawSubtype = null;
    protected Environment $environment;
    protected CarbonImmutable $signedDate;
    protected string $bundleId = '';
    protected ?int $appAppleId = null;
    protected string $notificationUUID = '';
    protected ?Transaction $transaction = null;
    protected ?RenewalInfo $renewalInfo = null;

    /**
     * Decode a notification and verify Apple's signature over it.
     *
     * This checks only that Apple signed the payload. To also require that the
     * notification belongs to your app and environment, use
     * {@see Validator::verifyNotification()}.
     *
     * @param array<string, mixed> $data     The request body, containing `signedPayload`.
     * @param TokenVerifier|null   $verifier Verifier for the JWS; defaults to Apple's certificate chain.
     * @throws ValidationException
     */
    public function __construct(array $data, ?TokenVerifier $verifier = null)
    {
        if (!array_key_exists('signedPayload', $data)) {
            throw new ValidationException('signedPayload key is missing from signed payload');
        }

        $token = TokenGenerator::decodeToken($data['signedPayload']);

        $verifier ??= new TokenVerifier();
        if (!$verifier->verify($token)) {
            throw new ValidationException('Signature verification failed for server notification');
        }

        $claims = $token->claims()->all();

        // notificationType (required). A type this version doesn't know yet is
        // not an error: Apple adds types over time and the payload is genuine,
        // so it maps to UNKNOWN and the raw string stays available.
        $typeRaw = is_scalar($claims['notificationType'] ?? null) ? (string) $claims['notificationType'] : '';
        if ($typeRaw === '') {
            throw new ValidationException('Server notification is missing its notificationType.');
        }
        $this->rawNotificationType = $typeRaw;
        $this->notificationType    = ServerNotificationType::fromString($typeRaw);

        // subtype (optional). An unrecognized subtype leaves getSubtype() null.
        if (is_scalar($claims['subtype'] ?? null) && (string) $claims['subtype'] !== '') {
            $this->rawSubtype = (string) $claims['subtype'];
            $this->subtype    = ServerNotificationSubtype::tryFrom($this->rawSubtype);
        }

        // Simple scalars
        $this->notificationUUID = (string)($claims['notificationUUID'] ?? '');
        $this->signedDate       = CarbonImmutable::createFromTimestampMs((int)($claims['signedDate'] ?? 0));

        $dataClaims = is_array($claims['data'] ?? null) ? $claims['data'] : [];

        // The app identity lives in whichever payload section Apple populated:
        // `data` for most notifications, `summary` for RENEWAL_EXTENSION summaries,
        // `externalPurchaseToken` for external purchase notifications, `appData` for app-level ones.
        $identity = $this->identityClaims($claims);

        $this->bundleId   = (string)($identity['bundleId'] ?? '');
        $this->appAppleId = is_numeric($identity['appAppleId'] ?? null) ? (int) $identity['appAppleId'] : null;

        $envRaw = $identity['environment'] ?? null;
        if (!is_string($envRaw) || $envRaw === '') {
            throw new ValidationException('Server notification is missing its environment.');
        }
        $this->environment = Environment::fromString($envRaw); // accepts "sandbox", "production", "prod"

        // Nested signed JWS blobs → decode, then hydrate typed objects
        if (!empty($dataClaims['signedTransactionInfo'])) {
            $txToken       = TokenGenerator::decodeToken($dataClaims['signedTransactionInfo']);
            $this->transaction = new Transaction($txToken->claims()->all());
        }

        if (!empty($dataClaims['signedRenewalInfo'])) {
            $renewalToken  = TokenGenerator::decodeToken($dataClaims['signedRenewalInfo']);
            $this->renewalInfo = new RenewalInfo($renewalToken->claims()->all());
        }
    }

    /**
     * The notification type, or {@see ServerNotificationType::UNKNOWN} for a type
     * this version of the library doesn't know yet. Check
     * {@see getRawNotificationType()} for the value Apple sent.
     */
    public function getNotificationType(): ServerNotificationType
    {
        return $this->notificationType;
    }

    /**
     * The notificationType exactly as Apple sent it, including types this
     * version of the library doesn't know yet.
     */
    public function getRawNotificationType(): string
    {
        return $this->rawNotificationType;
    }

    /**
     * Whether the notification type is one this version of the library knows.
     */
    public function hasKnownNotificationType(): bool
    {
        return $this->notificationType !== ServerNotificationType::UNKNOWN;
    }

    /**
     * The subtype, or null when the notification has none or carries a subtype
     * this version of the library doesn't know yet ({@see getRawSubtype()}).
     */
    public function getSubtype(): ?ServerNotificationSubtype
    {
        return $this->subtype;
    }

    /**
     * The subtype exactly as Apple sent it, or null when the notification has none.
     */
    public function getRawSubtype(): ?string
    {
        return $this->rawSubtype;
    }

    public function getNotificationUUID(): string
    {
        return $this->notificationUUID;
    }

    public function getSignedDate(): CarbonInterface
    {
        return $this->signedDate;
    }

    public function getBundleId(): string
    {
        return $this->bundleId;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * The app's numeric App Store identifier. Present in production; absent in sandbox.
     */
    public function getAppAppleId(): ?int
    {
        return $this->appAppleId;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function getRenewalInfo(): ?RenewalInfo
    {
        return $this->renewalInfo;
    }

    /**
     * Pick the bundleId / appAppleId / environment from whichever section carries them.
     *
     * Mirrors Apple's verifier: an external purchase token has no environment field,
     * so it is sandbox when the external purchase ID starts with "SANDBOX".
     *
     * @param array<string, mixed> $claims
     * @return array<string, mixed>
     */
    private function identityClaims(array $claims): array
    {
        foreach (['data', 'summary', 'appData'] as $section) {
            if (is_array($claims[$section] ?? null)) {
                return $claims[$section];
            }
        }

        $ext = $claims['externalPurchaseToken'] ?? null;
        if (is_array($ext)) {
            $externalPurchaseId = (string)($ext['externalPurchaseId'] ?? '');

            return $ext + [
                'environment' => str_starts_with($externalPurchaseId, 'SANDBOX')
                    ? Environment::SANDBOX->value
                    : Environment::PRODUCTION->value,
            ];
        }

        return [];
    }
}
