<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore\JWT;

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\Plain;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * The chain rules of Apple's App Store Server Library, applied to chains other than
 * Apple's: certs/chain-variants holds an independent root → WWDR-style intermediate →
 * App Store leaf chain plus variants that each break one rule (see its generate.sh).
 *
 * @group apple-app-store
 */
#[CoversClass(TokenVerifier::class)]
final class TokenVerifierTest extends TestCase
{
    private const string VARIANTS = __DIR__ . '/../certs/chain-variants';
    private const string STOREKIT = __DIR__ . '/../certs/storekit';

    // -------------------------------------------------------------------------
    // Accepted
    // -------------------------------------------------------------------------

    public function testAnIntermediateOtherThanApplesIsAcceptedUnderATrustedRoot(): void
    {
        // The intermediate is not pinned: only the root is.
        self::assertTrue(self::verifierTrusting('root.pem')->verify(self::token()));
    }

    public function testRootFingerprintsMayBeSha256AndColonSeparated(): void
    {
        $sha256 = (string) openssl_x509_fingerprint(self::pem('root.pem'), 'sha256');
        $colons = strtoupper(implode(':', str_split($sha256, 2)));

        $verifier = new TokenVerifier([$colons]);

        self::assertSame([$sha256], $verifier->getTrustedRootFingerprints());
        self::assertTrue($verifier->verify(self::token()));
    }

    public function testAListNamingIntermediateAndRootStillWorks(): void
    {
        // The form earlier versions took: [intermediate, root].
        $verifier = new TokenVerifier([self::fingerprint('intermediate.pem'), self::fingerprint('root.pem')]);

        self::assertTrue($verifier->verify(self::token()));
    }

    public function testTrustedRootsCanBeReplaced(): void
    {
        $verifier = (new TokenVerifier())->setTrustedRootFingerprints([self::fingerprint('root.pem')]);

        self::assertTrue($verifier->verify(self::token()));
    }

    public function testDefaultsToAppleRootCaG3Only(): void
    {
        self::assertSame([TokenVerifier::APPLE_ROOT_CA_G3_FINGERPRINT], (new TokenVerifier())->getTrustedRootFingerprints());
    }

    public function testARealAppleNotificationVerifiesWithTheDefaultTrust(): void
    {
        $fixture = json_decode((string) file_get_contents(__DIR__ . '/../fixtures/test-notification-signed-payload.json'), true);
        $token   = TokenGenerator::decodeToken($fixture['signedPayload']);

        self::assertTrue((new TokenVerifier())->verify($token));
        self::assertTrue((new TokenVerifier(['63343abfb89a6a03ebb57e9b3f5fa7be7c4f5c756f3017b3a8c488c3653e9179']))->verify($token));
    }

    public function testATokenWithoutSignedDateIsCheckedAtTheCurrentTime(): void
    {
        $claims = self::claims();
        unset($claims['signedDate']);

        self::assertTrue(self::verifierTrusting('root.pem')->verify(self::token($claims)));
    }

    // -------------------------------------------------------------------------
    // Rejected: chain rules
    // -------------------------------------------------------------------------

    /**
     * @param list<string> $x5c   Files from certs/chain-variants (or "storekit/<file>").
     * @param list<string> $trust Root files to trust.
     */
    #[DataProvider('brokenChains')]
    public function testABrokenChainIsRejected(array $x5c, array $trust, string $message): void
    {
        $token = self::token(x5c: $x5c, key: str_starts_with($x5c[0], 'storekit/') ? self::STOREKIT . '/leaf.p8' : null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($message);

        (new TokenVerifier(array_map(self::fingerprint(...), $trust)))->verify($token);
    }

    /**
     * @return iterable<string, array{list<string>, list<string>, string}>
     */
    public static function brokenChains(): iterable
    {
        $chain = ['leaf.pem', 'intermediate.pem', 'root.pem'];

        yield 'root not trusted' => [$chain, ['other-root.pem'], 'not rooted to a trusted Apple certificate'];
        yield 'wrong root presented' => [['leaf.pem', 'intermediate.pem', 'other-root.pem'], ['root.pem'], 'not rooted to a trusted Apple certificate'];
        yield 'intermediate not issued by the trusted root' => [['leaf.pem', 'intermediate.pem', 'other-root.pem'], ['other-root.pem'], 'Intermediate certificate could not be verified'];
        yield 'intermediate signed by a root impostor with the same name' => [['leaf.pem', 'intermediate-by-impostor.pem', 'root.pem'], ['root.pem'], 'Intermediate certificate could not be verified'];
        yield 'leaf not issued by the intermediate' => [['storekit/leaf.pem', 'intermediate.pem', 'root.pem'], ['root.pem'], 'Leaf certificate could not be verified'];
        yield 'intermediate without the WWDR marker' => [['leaf.pem', 'intermediate-no-marker.pem', 'root.pem'], ['root.pem'], 'not an Apple WWDR certificate'];
        yield 'intermediate that is not a CA' => [['leaf.pem', 'intermediate-not-ca.pem', 'root.pem'], ['root.pem'], 'not a certificate authority'];
        yield 'leaf without the App Store marker' => [['leaf-no-marker.pem', 'intermediate.pem', 'root.pem'], ['root.pem'], 'not an App Store signing certificate'];
        yield 'pinned certificate that is not self-signed' => [['leaf.pem', 'intermediate.pem', 'intermediate.pem'], ['intermediate.pem'], 'not rooted to a trusted Apple certificate'];
    }

    public function testATamperedSignatureInTheChainIsRejected(): void
    {
        $intermediate = (string) base64_decode(self::der('intermediate.pem'), true);
        // The last byte is inside the certificate's own signature value.
        $intermediate[-1] = chr(ord($intermediate[-1]) ^ 0x01);

        $token = self::token(x5c: [self::der('leaf.pem'), base64_encode($intermediate), self::der('root.pem')], raw: true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Intermediate certificate could not be verified');
        self::verifierTrusting('root.pem')->verify($token);
    }

    public function testATamperedJwsSignatureIsRejected(): void
    {
        [$header, $payload, $signature] = explode('.', self::token()->toString());
        $signature[5] = $signature[5] === 'A' ? 'B' : 'A';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('JWS signature verification failed');
        self::verifierTrusting('root.pem')->verify(TokenGenerator::decodeToken("$header.$payload.$signature"));
    }

    // -------------------------------------------------------------------------
    // Rejected: headers, certificates and dates
    // -------------------------------------------------------------------------

    public function testAnAlgorithmOtherThanEs256IsRejected(): void
    {
        [, $payload, $signature] = explode('.', self::token()->toString());
        $header = self::base64Url((string) json_encode([
            'alg' => 'ES384',
            'x5c' => [self::der('leaf.pem'), self::der('intermediate.pem'), self::der('root.pem')],
        ]));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Token algorithm must be ES256');
        self::verifierTrusting('root.pem')->verify(TokenGenerator::decodeToken("$header.$payload.$signature"));
    }

    /**
     * @param list<mixed> $x5c
     */
    #[DataProvider('badChainHeaders')]
    public function testAMalformedChainHeaderIsRejected(array $x5c, string $message): void
    {
        $token = self::token(x5c: $x5c, raw: true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($message);
        self::verifierTrusting('root.pem')->verify($token);
    }

    /**
     * @return iterable<string, array{list<mixed>, string}>
     */
    public static function badChainHeaders(): iterable
    {
        yield 'two certificates' => [[self::der('leaf.pem'), self::der('intermediate.pem')], 'valid x5c certificate chain'];
        yield 'four certificates' => [[self::der('leaf.pem'), self::der('intermediate.pem'), self::der('root.pem'), self::der('root.pem')], 'valid x5c certificate chain'];
        yield 'unparsable certificate' => [[self::der('leaf.pem'), base64_encode('not a certificate'), self::der('root.pem')], 'Failed to parse a certificate'];
        yield 'non-string certificate' => [[self::der('leaf.pem'), ['nested'], self::der('root.pem')], 'Failed to parse a certificate'];
    }

    public function testATokenSignedBeforeTheChainWasValidIsRejected(): void
    {
        $token = self::token(['signedDate' => 946684800000] + self::claims()); // 2000-01-01

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('was not valid at the time the token was signed');
        self::verifierTrusting('root.pem')->verify($token);
    }

    public function testReceiptCreationDateIsUsedWhenSignedDateIsMissing(): void
    {
        $claims = self::claims();
        unset($claims['signedDate']);
        $claims['receiptCreationDate'] = 946684800000; // 2000-01-01

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('was not valid at the time the token was signed');
        self::verifierTrusting('root.pem')->verify(self::token($claims));
    }

    // -------------------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------------------

    /**
     * @param list<mixed> $fingerprints
     */
    #[DataProvider('badFingerprintLists')]
    public function testABadTrustedRootListIsRefused(array $fingerprints): void
    {
        $this->expectException(ValidationException::class);

        new TokenVerifier($fingerprints);
    }

    /**
     * @return iterable<string, array{list<mixed>}>
     */
    public static function badFingerprintLists(): iterable
    {
        yield 'empty' => [[]];
        yield 'not hex' => [['not-a-fingerprint']];
        yield 'wrong length' => [['b52cb02fd567e0359fe8']];
        yield 'not a string' => [[42]];
    }

    // -------------------------------------------------------------------------
    // Fixtures
    // -------------------------------------------------------------------------

    private static function verifierTrusting(string $root): TokenVerifier
    {
        return new TokenVerifier([self::fingerprint($root)]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function claims(): array
    {
        return [
            'transactionId' => '2000000000000042',
            'bundleId'      => 'com.example',
            'signedDate'    => time() * 1000,
        ];
    }

    /**
     * @param array<string, mixed>|null $claims
     * @param list<mixed>|null          $x5c    File names, or (with $raw) the header values themselves.
     */
    private static function token(?array $claims = null, ?array $x5c = null, bool $raw = false, ?string $key = null): Plain
    {
        $x5c ??= ['leaf.pem', 'intermediate.pem', 'root.pem'];
        if (!$raw) {
            $x5c = array_map(self::der(...), $x5c);
        }

        $builder = (new Builder(new JoseEncoder(), ChainedFormatter::default()))->withHeader('x5c', $x5c);
        foreach ($claims ?? self::claims() as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }

        $token = $builder->getToken(new Sha256(), InMemory::file($key ?? self::VARIANTS . '/leaf.p8'));

        return TokenGenerator::decodeToken($token->toString());
    }

    private static function pem(string $file): string
    {
        $path = str_starts_with($file, 'storekit/') ? self::STOREKIT . '/' . substr($file, 9) : self::VARIANTS . '/' . $file;

        return (string) file_get_contents($path);
    }

    private static function der(string $file): string
    {
        return (string) preg_replace('/-----[^-]+-----|\s+/', '', self::pem($file));
    }

    private static function fingerprint(string $file): string
    {
        return (string) openssl_x509_fingerprint(self::pem($file));
    }

    private static function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
