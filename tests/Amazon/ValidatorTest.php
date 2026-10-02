<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\Amazon;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\AbstractLogger;
use ReceiptValidator\AbstractValidator;
use ReceiptValidator\Amazon\APIError;
use ReceiptValidator\Amazon\CancelReason;
use ReceiptValidator\Amazon\ProductType;
use ReceiptValidator\Amazon\PromotionStatus;
use ReceiptValidator\Amazon\PromotionType;
use ReceiptValidator\Amazon\Validator as AmazonValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use RuntimeException;

/**
 * @group amazon
 */
#[CoversClass(AmazonValidator::class)]
final class ValidatorTest extends TestCase
{
    private const string SECRET = 'secret=123';

    private const string EXPECTED_PATH =
        '/version/1.0/verifyReceiptId/developer/secret%3D123/user/user123/receiptId/receipt123';

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSetAndGetDeveloperSecretAndEndpoint(): void
    {
        $validator = new AmazonValidator('SECRET', Environment::SANDBOX);
        $validator->setUserId('user1');
        $validator->setReceiptId('receipt1');

        self::assertSame('SECRET', $validator->getDeveloperSecret());
        self::assertSame('user1', $validator->getUserId());
        self::assertSame('receipt1', $validator->getReceiptId());
        self::assertSame(Environment::SANDBOX, $validator->getEnvironment());
    }

    public function testSetAndGetEnvironment(): void
    {
        $validator = new AmazonValidator('topsecret', Environment::PRODUCTION);
        self::assertSame(Environment::PRODUCTION, $validator->getEnvironment());

        $validator->setEnvironment(Environment::SANDBOX);
        self::assertSame(Environment::SANDBOX, $validator->getEnvironment());
    }

    public function testValidateWithSubscriptionFixture(): void
    {
        $validator = $this->validatorReturning(200, $this->fixture('validSubscriptionResponse'), Environment::SANDBOX);
        $validator->setUserId('user123')->setReceiptId('receipt123');

        $response = $validator->validate();

        self::assertSame(Environment::SANDBOX, $response->getEnvironment());
        self::assertSame('user123', $response->getUserId());
        self::assertSame('com.amazon.iapsamplev2.expansion_set_3', $response->getProductId());
        self::assertSame(ProductType::SUBSCRIPTION, $response->getProductType());
        self::assertTrue($response->isTestTransaction());
        self::assertSame('US', $response->getCountryCode());

        $tx = $response->getTransactions()[0];
        self::assertTrue($tx->isAutoRenewing());
        self::assertSame('1 Week', $tx->getTerm());
        self::assertSame('sub1-weekly_term', $tx->getTermSku());
        self::assertSame(1561104377, $tx->getFreeTrialEndDate()?->getTimestamp());
        self::assertSame(1561104377, $tx->getGracePeriodEndDate()?->getTimestamp());
        self::assertSame(1561103277, $tx->getRenewalDate()?->getTimestamp());
        self::assertSame(1561104377, $tx->getExpiresAt()?->getTimestamp());
    }

    public function testValidateEntitledPurchaseFixture(): void
    {
        $validator = $this->validatorReturning(200, $this->fixture('entitledPurchaseResponse'), Environment::SANDBOX);
        $validator->setUserId('user123')->setReceiptId('receipt123');

        $tx = $validator->validate()->getTransactions()[0];

        self::assertSame('com.amazon.iapsamplev2.expansion_set_3', $tx->getProductId());
        self::assertStringStartsWith('q1YqVrJSSs7P1UvMTazKz9PLTCwoTswtyEktM9JLrShIzCvOzM', (string) $tx->getTransactionId());
        self::assertSame(1, $tx->getQuantity());
        self::assertTrue($tx->isEntitlement());
        self::assertSame(1402008634, $tx->getPurchaseDate()?->getTimestamp());
        self::assertTrue($tx->isEntitled());
        self::assertNull($tx->getExpiresAt());
    }

    public function testValidateConsumablePurchaseFixture(): void
    {
        $validator = $this->validatorReturning(200, $this->fixture('consumablePurchaseResponse'));

        $response = $validator->validate('receipt123', 'user123');

        self::assertSame(ProductType::CONSUMABLE, $response->getProductType());
        self::assertSame('wE1EG1gsEZI9q9UnI5YoZ2OxeoVKPdR5bvPMqyKQq5Y=:1:11', $response->getReceiptId());
        self::assertTrue($response->isEntitled());
    }

    public function testValidateCanceledSubscriptionFixture(): void
    {
        $validator = $this->validatorReturning(200, $this->fixture('canceledSubscriptionResponse'));

        $response = $validator->validate('receipt123', 'user123');

        self::assertTrue($response->isCanceled());
        self::assertSame(CancelReason::CUSTOMER_CANCELED, $response->getCancelReason());
        self::assertSame(1400784371, $response->getCancellationDate()?->getTimestamp());
        self::assertFalse($response->isEntitled());
        self::assertTrue($response->isBetaProduct());
        self::assertSame(1, $response->getTransaction()?->getQuantity()); // quantity: null
    }

    public function testValidatePromotionalSubscriptionFixture(): void
    {
        $validator = $this->validatorReturning(200, $this->fixture('promotionalSubscriptionResponse'));

        $response = $validator->validate('receipt123', 'user123');

        $promotions = $response->getPromotions();
        self::assertCount(1, $promotions);
        self::assertSame(PromotionType::INTRODUCTORY_PRICE, $promotions[0]->getType());
        self::assertSame(PromotionStatus::QUEUED, $promotions[0]->getStatus());
        self::assertNull($response->getTransaction()?->getActivePromotion());
        self::assertFalse($response->isTestTransaction());
    }

    public function testValidateArgumentsOverridePreviouslySetValues(): void
    {
        $validator = $this->validatorReturning(200, $this->fixture('consumablePurchaseResponse'));
        $validator->setUserId('stale-user')->setReceiptId('stale-receipt');

        $response = $validator->validate('receipt123', 'user123');

        self::assertSame('user123', $response->getUserId());
        self::assertSame('user123', $validator->getUserId());
        self::assertSame('receipt123', $validator->getReceiptId());
    }

    public function testRequestCarriesAcceptAndUserAgentHeaders(): void
    {
        $validator = $this->validatorReturning(200, '{"receiptId":"r"}', Environment::PRODUCTION, function (RequestInterface $request): bool {
            return $request->getHeaderLine('Accept') === 'application/json'
                && $request->getHeaderLine('User-Agent') === AbstractValidator::userAgent()
                && str_starts_with((string) $request->getUri(), AmazonValidator::ENDPOINT_PRODUCTION . '/version/');
        });

        self::assertSame('r', $validator->validate('receipt123', 'user123')->getReceiptId());
    }

    #[DataProvider('documentedErrorProvider')]
    public function testDocumentedStatusCodesMapToApiError(int $status, APIError $expected): void
    {
        $validator = $this->validatorReturning($status, '{"message":"ignored in favour of the documented meaning"}');

        try {
            $validator->validate('receipt123', 'user123');
            self::fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            self::assertSame($status, $e->getCode());
            self::assertSame("Amazon API error [$status]: " . $expected->message(), $e->getMessage());
            self::assertSame($expected, APIError::fromException($e));
        }
    }

    public static function documentedErrorProvider(): iterable
    {
        yield '400' => [400, APIError::INVALID_RECEIPT];
        yield '410' => [410, APIError::RECEIPT_NO_LONGER_VALID];
        yield '429' => [429, APIError::THROTTLED];
        yield '496' => [496, APIError::INVALID_SHARED_SECRET];
        yield '497' => [497, APIError::INVALID_USER_ID];
        yield '500' => [500, APIError::INTERNAL_ERROR];
    }

    #[DataProvider('undocumentedErrorProvider')]
    public function testUndocumentedStatusCodesFallBackToBody(int $status, string $body, string $expectedMessage): void
    {
        $validator = $this->validatorReturning($status, $body);

        try {
            $validator->validate('receipt123', 'user123');
            self::fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            self::assertSame($status, $e->getCode());
            self::assertSame("Amazon API error [$status]: $expectedMessage", $e->getMessage());
            self::assertNull(APIError::fromException($e));
        }
    }

    public static function undocumentedErrorProvider(): iterable
    {
        yield 'json message' => [503, '{"message":"Service Unavailable"}', 'Service Unavailable'];
        yield 'plain text' => [502, "Bad Gateway\n", 'Bad Gateway'];
        yield 'empty body' => [403, '', 'An unknown error occurred.'];
        yield 'json without message' => [403, '{"error":"x"}', '{"error":"x"}'];
    }

    public function testInvalidJsonOnSuccessStatusThrows(): void
    {
        $validator = $this->validatorReturning(200, 'not json');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Amazon API returned invalid JSON');

        $validator->validate('receipt123', 'user123');
    }

    public function testConnectionFailureRedactsTheSharedSecret(): void
    {
        $encoded   = rawurlencode(self::SECRET);
        $exception = new class ("cURL error 6 for https://appstore-sdk.amazon.com/version/1.0/verifyReceiptId/developer/$encoded/user/u and raw " . self::SECRET) extends RuntimeException implements ClientExceptionInterface {
        };

        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')->once()->andThrow($exception);

        $records = [];
        $logger  = new class ($records) extends AbstractLogger {
            /** @param array<int, array{0: string, 1: string, 2: array<string, mixed>}> $records */
            public function __construct(private array &$records)
            {
            }

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [(string) $level, (string) $message, $context];
            }
        };

        $validator = new AmazonValidator(self::SECRET, Environment::SANDBOX);
        $validator->setHttpClient($mockClient)->setLogger($logger);

        try {
            $validator->validate('receipt123', 'user123');
            self::fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(0, $e->getCode());
            self::assertNull($e->getPrevious());
            self::assertStringContainsString('Amazon validation request failed: cURL error 6', $e->getMessage());
            self::assertStringContainsString('/developer/[redacted]/user/u and raw [redacted]', $e->getMessage());
            self::assertStringNotContainsString(self::SECRET, $e->getMessage());
            self::assertStringNotContainsString($encoded, $e->getMessage());
        }

        $errorRecords = array_values(array_filter($records, static fn (array $r) => $r[0] === 'error'));
        self::assertCount(1, $errorRecords);
        self::assertSame('Amazon API connection failed', $errorRecords[0][1]);
        self::assertSame($exception::class, $errorRecords[0][2]['exception']);
        self::assertStringNotContainsString(self::SECRET, $errorRecords[0][2]['error']);
        self::assertStringNotContainsString($encoded, $errorRecords[0][2]['error']);
    }

    #[DataProvider('missingParameterProvider')]
    public function testMissingParametersThrow(string $secret, ?string $userId, ?string $receiptId, string $expected): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldNotReceive('sendRequest');

        $validator = new AmazonValidator($secret, Environment::SANDBOX);
        $validator->setHttpClient($mockClient)->setUserId($userId)->setReceiptId($receiptId);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expected);

        $validator->validate();
    }

    public static function missingParameterProvider(): iterable
    {
        yield 'secret' => ['', 'user', 'receipt', 'Missing Amazon developer secret'];
        yield 'user id' => ['secret', null, 'receipt', 'Missing Amazon userId'];
        yield 'empty user id' => ['secret', '', 'receipt', 'Missing Amazon userId'];
        yield 'receipt id' => ['secret', 'user', null, 'Missing Amazon receiptId'];
        yield 'empty receipt id' => ['secret', 'user', '', 'Missing Amazon receiptId'];
    }

    private function fixture(string $name): string
    {
        return (string) file_get_contents(__DIR__ . "/fixtures/$name.json");
    }

    /**
     * @param (callable(RequestInterface): bool)|null $extraCheck
     */
    private function validatorReturning(
        int $status,
        string $body,
        Environment $environment = Environment::SANDBOX,
        ?callable $extraCheck = null,
    ): AmazonValidator {
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->withArgs(static function (RequestInterface $request) use ($extraCheck): bool {
                return $request->getMethod() === 'GET'
                    && str_contains((string) $request->getUri(), self::EXPECTED_PATH)
                    && ($extraCheck === null || $extraCheck($request));
            })
            ->andReturn(new GuzzleResponse($status, [], $body));

        $validator = new AmazonValidator(self::SECRET, $environment);
        $validator->setHttpClient($mockClient);

        return $validator;
    }
}
