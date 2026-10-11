<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\iTunes;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\iTunes\APIError;
use ReceiptValidator\iTunes\Validator;

#[CoversClass(Validator::class)]
final class ValidatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSetAndGetEnvironment(): void
    {
        $validator = new Validator('secret', Environment::SANDBOX);
        self::assertSame(Environment::SANDBOX, $validator->getEnvironment());

        $validator->setEnvironment(Environment::PRODUCTION);
        self::assertSame(Environment::PRODUCTION, $validator->getEnvironment());
    }

    public function testSetReceiptData_autoEncodesJson(): void
    {
        $validator = new Validator('secret');
        $json     = '{"example":"json"}';
        $base64   = base64_encode($json);

        $validator->setReceiptData($json);
        self::assertSame($base64, $validator->getReceiptData());

        // If already base64, it should remain unchanged
        $validator->setReceiptData($base64);
        self::assertSame($base64, $validator->getReceiptData());
    }

    public function testGetSharedSecret(): void
    {
        $validator = new Validator('secret');
        self::assertSame('secret', $validator->getSharedSecret());
    }

    public function testSetSharedSecretUpdatesValue(): void
    {
        $validator = new Validator('original');
        $validator->setSharedSecret('updated');
        self::assertSame('updated', $validator->getSharedSecret());
    }

    public function testSetSharedSecretToNull(): void
    {
        $validator = new Validator('secret');
        $validator->setSharedSecret(null);
        self::assertNull($validator->getSharedSecret());
    }

    public function testSetSharedSecretDefaultsToNull(): void
    {
        $validator = new Validator('secret');
        $validator->setSharedSecret();
        self::assertNull($validator->getSharedSecret());
    }

    public function testSetSharedSecretReturnsSelf(): void
    {
        $validator = new Validator('secret');
        $result = $validator->setSharedSecret('new-secret');
        self::assertSame($validator, $result);
    }

    public function testConstructorWithNullSharedSecret(): void
    {
        $validator = new Validator(null);
        self::assertNull($validator->getSharedSecret());
    }

    public function testValidateReturnsResponse(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => 0,
                'receipt' => ['app_item_id' => 123, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        $validator = new Validator('secret');
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('abc');

        $response = $validator->validate();
        self::assertIsArray($response->getRawData());
    }

    public function testRetryOnSandboxError_21007(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        // First call (production) replies with 21007 → retry on SANDBOX
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode(['status' => 21007], JSON_THROW_ON_ERROR)));
        // Second call (sandbox) succeeds
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => 0,
                'receipt' => ['app_item_id' => 123, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        $validator = new Validator('secret', Environment::PRODUCTION);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('xyz');

        $response = $validator->validate();
        self::assertIsArray($response->getRawData());
    }

    public function testRetryOnProductionErrorFromSandbox_21008(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        // First call (sandbox) replies with 21008 → retry on PRODUCTION
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode(['status' => 21008], JSON_THROW_ON_ERROR)));
        // Second call (production) succeeds
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => 0,
                'receipt' => ['app_item_id' => 123, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        $validator = new Validator('secret', Environment::SANDBOX);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('xyz');

        $response = $validator->validate();
        self::assertIsArray($response->getRawData());
    }

    public function testThrowsOnInvalidHttpStatus(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(500, [], 'Server error'));

        $validator = new Validator('secret', Environment::PRODUCTION);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('test');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unable to get response from iTunes server');

        $validator->validate();
    }

    public function testInAppPurchaseResponseFromFixture(): void
    {
        $json       = file_get_contents(__DIR__ . '/fixtures/inAppPurchaseResponse.json');
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], $json));

        $validator = new Validator('secret', Environment::SANDBOX);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('dummy-data');

        $response = $validator->validate();

        self::assertSame('com.myapp', $response->getBundleId());
        self::assertCount(2, $response->getTransactions());
        self::assertSame('myapp.1', $response->getTransactions()[0]->getProductId());
    }

    public function testInAppPurchaseInvalidReceiptResponseFromFixture(): void
    {
        $json       = file_get_contents(__DIR__ . '/fixtures/inAppPurchaseInvalidReceiptResponse.json');
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], $json));

        $validator = new Validator('secret', Environment::SANDBOX);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('dummy-data');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The data in the receipt-data property was malformed.');

        $validator->validate();
    }

    public function testThrowsValidationExceptionWithFormattedMessage(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode(['status' => 21004], JSON_THROW_ON_ERROR)));

        $validator = new Validator('invalid-shared-secret', Environment::PRODUCTION);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('dummy');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('iTunes API error [21004]: The shared secret you provided does not match the shared secret on file for your account.');

        $validator->validate();
    }

    public function testValidateThrowsWhenReceiptDataMissing(): void
    {
        $validator = new Validator('secret', Environment::SANDBOX);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Receipt data must be set before validation.');

        // no setReceiptData() call
        $validator->validate();
    }

    public function testInvalidJsonBodyThrows(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], '{not-json'));

        $validator = new Validator('secret', Environment::PRODUCTION);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('abc');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('iTunes server returned invalid JSON');

        $validator->validate();
    }

    public function testSubscriptionExpiredReturnsResponse(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => APIError::SUBSCRIPTION_EXPIRED->value,
                'receipt' => ['app_item_id' => 123, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        $validator = new Validator('secret', Environment::PRODUCTION);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('dummy-data');

        $response = $validator->validate();
        self::assertIsArray($response->getRawData());
        self::assertSame(APIError::SUBSCRIPTION_EXPIRED->value, $response->getRawData()['status']);
    }

    public function testApiErrorFromIntHelper(): void
    {
        self::assertSame(APIError::JSON_INVALID, APIError::fromInt(21000));
        self::assertNull(APIError::fromInt(999999));
    }

    public function testSharedSecretIncludedInRequestPayload(): void
    {
        $capturedRequest = null;

        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturnUsing(function (RequestInterface $req) use (&$capturedRequest): GuzzleResponse {
                $capturedRequest = $req;
                return new GuzzleResponse(200, [], json_encode([
                    'status'  => 0,
                    'receipt' => ['app_item_id' => 1, 'in_app' => []],
                ], JSON_THROW_ON_ERROR));
            });

        $validator = new Validator('topsecret', Environment::PRODUCTION);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData(base64_encode('anything'));
        $validator->validate();

        $this->assertNotNull($capturedRequest);
        $this->assertSame('POST', $capturedRequest->getMethod());
        $this->assertStringContainsString('/verifyReceipt', (string) $capturedRequest->getUri());

        $payload = json_decode((string) $capturedRequest->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertSame('topsecret', $payload['password'] ?? null);
        $this->assertArrayHasKey('receipt-data', $payload);

        // Now repeat with NO shared secret → password should be absent
        $capturedRequest2 = null;

        $mockClient2 = Mockery::mock(ClientInterface::class);
        $mockClient2->shouldReceive('sendRequest')
            ->once()
            ->andReturnUsing(function (RequestInterface $req) use (&$capturedRequest2): GuzzleResponse {
                $capturedRequest2 = $req;
                return new GuzzleResponse(200, [], json_encode([
                    'status'  => 0,
                    'receipt' => ['app_item_id' => 1, 'in_app' => []],
                ], JSON_THROW_ON_ERROR));
            });

        $validator2 = new Validator(null, Environment::PRODUCTION);
        $validator2->setHttpClient($mockClient2);
        $validator2->setReceiptData(base64_encode('anything'));
        $validator2->validate();

        $this->assertNotNull($capturedRequest2);
        $payload2 = json_decode((string) $capturedRequest2->getBody(), true);
        $this->assertIsArray($payload2);
        $this->assertArrayNotHasKey('password', $payload2);
        $this->assertArrayHasKey('receipt-data', $payload2);
    }

    /**
     * A client that answers each request with the next body in $bodies and records
     * the host of every request it receives.
     *
     * @param list<array<mixed>|string> $bodies JSON-encodable arrays, or raw body strings
     * @param list<string>              $hosts  receives the host of each request
     */
    private function sequenceClient(array $bodies, array &$hosts): ClientInterface
    {
        $mock = Mockery::mock(ClientInterface::class);
        $mock->shouldReceive('sendRequest')
            ->times(count($bodies))
            ->andReturnUsing(function (RequestInterface $req) use (&$bodies, &$hosts): GuzzleResponse {
                $hosts[] = $req->getUri()->getHost();
                $body    = array_shift($bodies);

                return new GuzzleResponse(200, [], is_string($body) ? $body : json_encode($body, JSON_THROW_ON_ERROR));
            });

        return $mock;
    }

    /** @return array<string, mixed> */
    private static function validBody(string $bundleId = 'com.example.app', int $status = 0): array
    {
        return ['status' => $status, 'receipt' => ['bundle_id' => $bundleId, 'in_app' => []]];
    }

    public function testBundleIdDefaultsToNull(): void
    {
        self::assertNull((new Validator('secret'))->getBundleId());
    }

    public function testBundleIdFromConstructorAndSetter(): void
    {
        $validator = new Validator('secret', Environment::PRODUCTION, 'com.example.app');
        self::assertSame('com.example.app', $validator->getBundleId());

        self::assertSame($validator, $validator->setBundleId('com.example.other'));
        self::assertSame('com.example.other', $validator->getBundleId());

        $validator->setBundleId('');
        self::assertNull($validator->getBundleId());

        $validator->setBundleId('com.example.app')->setBundleId(null);
        self::assertNull($validator->getBundleId());
    }

    public function testMatchingBundleIdIsAccepted(): void
    {
        $hosts     = [];
        $validator = new Validator('secret', Environment::PRODUCTION, 'com.example.app');
        $validator->setHttpClient($this->sequenceClient([self::validBody()], $hosts));

        $response = $validator->validate('abc');

        self::assertSame('com.example.app', $response->getBundleId());
    }

    public function testMismatchedBundleIdIsRejected(): void
    {
        $hosts     = [];
        $validator = new Validator('secret', Environment::PRODUCTION, 'com.example.app');
        $validator->setHttpClient($this->sequenceClient([self::validBody('com.attacker.app')], $hosts));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Receipt bundle identifier does not match the expected bundle identifier.');

        $validator->validate('abc');
    }

    public function testMismatchedBundleIdIsRejectedOnExpiredSubscription(): void
    {
        $hosts     = [];
        $validator = new Validator('secret', Environment::PRODUCTION, 'com.example.app');
        $validator->setHttpClient($this->sequenceClient([
            self::validBody('com.attacker.app', APIError::SUBSCRIPTION_EXPIRED->value),
        ], $hosts));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Receipt bundle identifier does not match');

        $validator->validate('abc');
    }

    public function testIos6StyleBidIsChecked(): void
    {
        $hosts = [];
        $body  = ['status' => 0, 'receipt' => ['bid' => 'com.example.app', 'product_id' => 'p1', 'transaction_id' => '1']];

        $validator = new Validator('secret', Environment::PRODUCTION, 'com.example.app');
        $validator->setHttpClient($this->sequenceClient([$body], $hosts));
        self::assertSame('com.example.app', $validator->validate('abc')->getBundleId());

        $body['receipt']['bid'] = 'com.attacker.app';
        $validator->setHttpClient($this->sequenceClient([$body], $hosts));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Receipt bundle identifier does not match');

        $validator->validate('abc');
    }

    public function testBundleIdAndBidMustBothMatch(): void
    {
        $hosts     = [];
        $validator = new Validator('secret', Environment::PRODUCTION, 'com.example.app');
        $validator->setHttpClient($this->sequenceClient([
            ['status' => 0, 'receipt' => ['bundle_id' => 'com.example.app', 'bid' => 'com.attacker.app']],
        ], $hosts));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Receipt bundle identifier does not match');

        $validator->validate('abc');
    }

    public function testNonStringBundleIdIsRejected(): void
    {
        $hosts     = [];
        $validator = new Validator('secret', Environment::PRODUCTION, '123');
        $validator->setHttpClient($this->sequenceClient([['status' => 0, 'receipt' => ['bundle_id' => 123]]], $hosts));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Receipt bundle identifier does not match');

        $validator->validate('abc');
    }

    public function testReceiptWithoutBundleIdIsRejectedWhenExpected(): void
    {
        $hosts     = [];
        $validator = new Validator('secret', Environment::PRODUCTION, 'com.example.app');
        $validator->setHttpClient($this->sequenceClient([['status' => 0, 'receipt' => ['in_app' => []]]], $hosts));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Receipt does not contain a bundle identifier.');

        $validator->validate('abc');
    }

    public function testUnsetBundleIdKeepsOldBehaviour(): void
    {
        $hosts     = [];
        $validator = new Validator('secret');
        $validator->setHttpClient($this->sequenceClient([
            self::validBody('com.any.app'),
            ['status' => 0, 'receipt' => ['in_app' => []]],
        ], $hosts));

        self::assertSame('com.any.app', $validator->validate('abc')->getBundleId());
        self::assertNull($validator->validate('abc')->getBundleId());
    }

    public function testSandboxRetryDoesNotChangeTheValidatorEnvironment(): void
    {
        $hosts     = [];
        $validator = new Validator('secret', Environment::PRODUCTION);
        $validator->setHttpClient($this->sequenceClient([
            ['status' => APIError::SANDBOX_RECEIPT_ON_PRODUCTION->value],
            self::validBody(),
            self::validBody(),
        ], $hosts));

        $first = $validator->validate('abc');

        self::assertSame(['buy.itunes.apple.com', 'sandbox.itunes.apple.com'], $hosts);
        self::assertSame(Environment::SANDBOX, $first->getEnvironment());
        self::assertSame(Environment::PRODUCTION, $validator->getEnvironment());

        $second = $validator->validate('abc');

        self::assertSame(['buy.itunes.apple.com', 'sandbox.itunes.apple.com', 'buy.itunes.apple.com'], $hosts);
        self::assertSame(Environment::PRODUCTION, $second->getEnvironment());
    }

    public function testProductionRetryDoesNotChangeTheValidatorEnvironment(): void
    {
        $hosts     = [];
        $validator = new Validator('secret', Environment::SANDBOX);
        $validator->setHttpClient($this->sequenceClient([
            ['status' => APIError::PRODUCTION_RECEIPT_ON_SANDBOX->value],
            self::validBody(),
        ], $hosts));

        $response = $validator->validate('abc');

        self::assertSame(['sandbox.itunes.apple.com', 'buy.itunes.apple.com'], $hosts);
        self::assertSame(Environment::PRODUCTION, $response->getEnvironment());
        self::assertSame(Environment::SANDBOX, $validator->getEnvironment());
    }

    public function testResponseReportsTheEnvironmentThatAnsweredWithoutRetry(): void
    {
        $hosts     = [];
        $validator = new Validator('secret', Environment::SANDBOX);
        $validator->setHttpClient($this->sequenceClient([self::validBody()], $hosts));

        self::assertSame(Environment::SANDBOX, $validator->validate('abc')->getEnvironment());
        self::assertSame(['sandbox.itunes.apple.com'], $hosts);
    }

    public function testEnvironmentSwitchHappensAtMostOnce(): void
    {
        $hosts     = [];
        $validator = new Validator('secret', Environment::PRODUCTION);
        $validator->setHttpClient($this->sequenceClient([
            ['status' => APIError::SANDBOX_RECEIPT_ON_PRODUCTION->value],
            ['status' => APIError::PRODUCTION_RECEIPT_ON_SANDBOX->value],
        ], $hosts));

        try {
            $validator->validate('abc');
            self::fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame(APIError::PRODUCTION_RECEIPT_ON_SANDBOX->value, $e->getCode());
            self::assertStringStartsWith('iTunes API error [21008]', $e->getMessage());
        }

        self::assertSame(['buy.itunes.apple.com', 'sandbox.itunes.apple.com'], $hosts);
        self::assertSame(Environment::PRODUCTION, $validator->getEnvironment());
    }

    public function testRetryOnlyForTheMatchingEnvironment(): void
    {
        // 21008 from production is not a redirect: production is already the other environment.
        $hosts     = [];
        $validator = new Validator('secret', Environment::PRODUCTION);
        $validator->setHttpClient($this->sequenceClient([
            ['status' => APIError::PRODUCTION_RECEIPT_ON_SANDBOX->value],
        ], $hosts));

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(APIError::PRODUCTION_RECEIPT_ON_SANDBOX->value);

        $validator->validate('abc');
    }

    public function testBundleIdIsCheckedOnTheRetriedResponse(): void
    {
        $hosts     = [];
        $validator = new Validator('secret', Environment::PRODUCTION, 'com.example.app');
        $validator->setHttpClient($this->sequenceClient([
            ['status' => APIError::SANDBOX_RECEIPT_ON_PRODUCTION->value],
            self::validBody('com.attacker.app'),
        ], $hosts));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Receipt bundle identifier does not match');

        $validator->validate('abc');
    }

    /** @return array<string, array{0: array<mixed>|string}> */
    public static function bodiesWithoutIntegerStatus(): array
    {
        return [
            'missing status'      => [['receipt' => ['bundle_id' => 'com.example.app', 'in_app' => []]]],
            'null status'         => [['status' => null]],
            'string status'       => [['status' => '0']],
            'float status'        => ['{"status":0.0}'],
            'boolean status'      => [['status' => false]],
            'array status'        => [['status' => [0]]],
            'empty object'        => ['{}'],
            'empty list'          => ['[]'],
        ];
    }

    /** @param array<mixed>|string $body */
    #[DataProvider('bodiesWithoutIntegerStatus')]
    public function testResponseWithoutIntegerStatusThrows(array|string $body): void
    {
        $hosts     = [];
        $validator = new Validator('secret');
        $validator->setHttpClient($this->sequenceClient([$body], $hosts));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('iTunes server response has no valid status.');

        $validator->validate('abc');
    }

    public function testMissingStatusOnRetriedResponseThrows(): void
    {
        $hosts     = [];
        $validator = new Validator('secret');
        $validator->setHttpClient($this->sequenceClient([
            ['status' => APIError::SANDBOX_RECEIPT_ON_PRODUCTION->value],
            ['receipt' => []],
        ], $hosts));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('iTunes server response has no valid status.');

        $validator->validate('abc');
    }

    /** @return array<string, array{0: string}> */
    public static function nonObjectBodies(): array
    {
        return [
            'scalar JSON' => ['42'],
            'JSON string' => ['"ok"'],
        ];
    }

    #[DataProvider('nonObjectBodies')]
    public function testNonObjectJsonThrows(string $body): void
    {
        $hosts     = [];
        $validator = new Validator('secret');
        $validator->setHttpClient($this->sequenceClient([$body], $hosts));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('iTunes server returned an unexpected response structure.');

        $validator->validate('abc');
    }

    public function testEmptyBodyThrows(): void
    {
        $hosts     = [];
        $validator = new Validator('secret');
        $validator->setHttpClient($this->sequenceClient([''], $hosts));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('iTunes server returned invalid JSON');

        $validator->validate('abc');
    }

    public function testHttpErrorMessageIncludesTheStatusCode(): void
    {
        $mock = Mockery::mock(ClientInterface::class);
        $mock->shouldReceive('sendRequest')->once()->andReturn(new GuzzleResponse(503, [], '<html>busy</html>'));

        $validator = new Validator('secret');
        $validator->setHttpClient($mock);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unable to get response from iTunes server (HTTP 503)');

        $validator->validate('abc');
    }

    public function testConnectionFailureThrowsValidationException(): void
    {
        $mock = Mockery::mock(ClientInterface::class);
        $mock->shouldReceive('sendRequest')->once()->andThrow(
            new ConnectException('timed out', new Request('POST', 'https://buy.itunes.apple.com/verifyReceipt'))
        );

        $validator = new Validator('secret');
        $validator->setHttpClient($mock);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unable to connect to iTunes server - timed out');

        $validator->validate('abc');
    }

    public function testUnencodableRequestThrowsBeforeAnyRequest(): void
    {
        $mock = Mockery::mock(ClientInterface::class);
        $mock->shouldNotReceive('sendRequest');

        $validator = new Validator("\xB1");
        $validator->setHttpClient($mock);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unable to encode request for iTunes server');

        $validator->validate('abc');
    }
}
