<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay\JWT;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\GooglePlay\JWT\ServiceAccountCredentials;

#[CoversClass(ServiceAccountCredentials::class)]
final class ServiceAccountCredentialsTest extends TestCase
{
    private const string FIXTURE = __DIR__ . '/../certs/testServiceAccount.json';

    public function testFromJsonParsesKeyFile(): void
    {
        $credentials = ServiceAccountCredentials::fromJson((string) file_get_contents(self::FIXTURE));

        self::assertSame('iap-verifier@test-project.iam.gserviceaccount.com', $credentials->clientEmail);
        self::assertStringStartsWith('-----BEGIN', trim($credentials->privateKey));
        self::assertSame('abcdef1234567890', $credentials->privateKeyId);
        self::assertSame('https://oauth2.googleapis.com/token', $credentials->tokenUri);
        self::assertSame('test-project', $credentials->projectId);
    }

    public function testFromArrayDefaultsTokenUri(): void
    {
        $credentials = ServiceAccountCredentials::fromArray([
            'client_email' => 'svc@example.iam.gserviceaccount.com',
            'private_key'  => 'PEM',
        ]);

        self::assertSame(ServiceAccountCredentials::DEFAULT_TOKEN_URI, $credentials->tokenUri);
        self::assertNull($credentials->privateKeyId);
        self::assertNull($credentials->projectId);
    }

    public function testFromArrayIgnoresEmptyTokenUri(): void
    {
        $credentials = ServiceAccountCredentials::fromArray([
            'client_email' => 'svc@example.iam.gserviceaccount.com',
            'private_key'  => 'PEM',
            'token_uri'    => '',
        ]);

        self::assertSame(ServiceAccountCredentials::DEFAULT_TOKEN_URI, $credentials->tokenUri);
    }

    public function testFromJsonRejectsInvalidJson(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('could not be decoded');

        ServiceAccountCredentials::fromJson('{not json');
    }

    public function testFromJsonRejectsNonObject(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must decode to an object');

        ServiceAccountCredentials::fromJson('"just a string"');
    }

    public function testFromArrayRejectsWrongType(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expected a service_account key file, got type "authorized_user"');

        ServiceAccountCredentials::fromArray(['type' => 'authorized_user']);
    }

    public function testConstructorRejectsMissingEmail(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('client_email');

        new ServiceAccountCredentials(clientEmail: ' ', privateKey: 'PEM');
    }

    public function testConstructorRejectsMissingKey(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('private_key');

        new ServiceAccountCredentials(clientEmail: 'svc@example.com', privateKey: '');
    }

    public function testConstructorRejectsEmptyTokenUri(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('token_uri');

        new ServiceAccountCredentials(clientEmail: 'svc@example.com', privateKey: 'PEM', tokenUri: ' ');
    }
}
