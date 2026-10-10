<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\ConsumptionRequest;
use ReceiptValidator\AppleAppStore\ConsumptionRequestV1;
use ReceiptValidator\AppleAppStore\DeliveryStatus;
use ReceiptValidator\AppleAppStore\ExtendRenewalDateRequest;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Every App Store Server API path parameter must stay inside its own path segment.
 *
 * Identifiers often come from client input (a receipt, a StoreKit payload, a support
 * form). Interpolated raw, "../", "?", "#" or "/" would let that input steer the request,
 * which carries the app's App Store Server API credentials, to a different endpoint.
 * Numeric identifiers (transactionId, originalTransactionId) must be digits only; free-form
 * identifiers are percent-encoded as a single segment. Each case is sent through a real
 * Guzzle client on a mock handler, so the asserted URI is the one that would leave the process.
 *
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
final class PathParameterSafetyTest extends TestCase
{
    private const string VALID_ID = '2000000123456789';

    /** @var list<array{request: RequestInterface}> */
    private array $history = [];

    /**
     * Endpoints whose parameter Apple defines as a numeric transaction identifier.
     *
     * @return iterable<string, array{callable(Validator, string): mixed, string}>
     */
    public static function numericEndpoints(): iterable
    {
        yield 'getTransactionHistory' => [
            static fn (Validator $v, string $id) => $v->getTransactionHistory($id),
            '/inApps/v2/history/%s',
        ];
        yield 'validate (deprecated)' => [
            static fn (Validator $v, string $id) => $v->validate($id),
            '/inApps/v2/history/%s',
        ];
        yield 'getTransactionInfo' => [
            static fn (Validator $v, string $id) => $v->getTransactionInfo($id),
            '/inApps/v1/transactions/%s',
        ];
        yield 'finishTransaction' => [
            static fn (Validator $v, string $id) => $v->finishTransaction($id),
            '/inApps/v1/transactions/%s/finish',
        ];
        yield 'setAppAccountToken' => [
            static fn (Validator $v, string $id) => $v->setAppAccountToken($id, '7389a31a-fb6d-4569-a2a6-db7d85d84813'),
            '/inApps/v1/transactions/%s/appAccountToken',
        ];
        yield 'sendConsumptionInformation (v2)' => [
            static fn (Validator $v, string $id) => $v->sendConsumptionInformation(
                $id,
                new ConsumptionRequest(true, false, DeliveryStatus::DELIVERED),
            ),
            '/inApps/v2/transactions/consumption/%s',
        ];
        yield 'sendConsumptionInformation (v1)' => [
            static fn (Validator $v, string $id) => $v->sendConsumptionInformation($id, new ConsumptionRequestV1(true, false)),
            '/inApps/v1/transactions/consumption/%s',
        ];
        yield 'getRefundHistory' => [
            static fn (Validator $v, string $id) => $v->getRefundHistory($id),
            '/inApps/v2/refund/lookup/%s',
        ];
        yield 'getAllSubscriptionStatuses' => [
            static fn (Validator $v, string $id) => $v->getAllSubscriptionStatuses($id),
            '/inApps/v1/subscriptions/%s',
        ];
        yield 'extendSubscriptionRenewalDate' => [
            static function (Validator $v, string $id) {
                $request                   = new ExtendRenewalDateRequest();
                $request->extendByDays     = 7;
                $request->extendReasonCode = 1;

                return $v->extendSubscriptionRenewalDate($id, $request);
            },
            '/inApps/v1/subscriptions/extend/%s',
        ];
    }

    /**
     * Endpoints whose parameter is a free-form identifier, percent-encoded as one segment.
     *
     * @return iterable<string, array{callable(Validator, string): mixed, string}>
     */
    public static function freeFormEndpoints(): iterable
    {
        yield 'lookUpOrderId' => [
            static fn (Validator $v, string $id) => $v->lookUpOrderId($id),
            '/inApps/v1/lookup/%s',
        ];
        yield 'getTestNotificationStatus' => [
            static fn (Validator $v, string $id) => $v->getTestNotificationStatus($id),
            '/inApps/v1/notifications/test/%s',
        ];
        yield 'getAppTransactionInfo' => [
            static fn (Validator $v, string $id) => $v->getAppTransactionInfo($id),
            '/inApps/v1/transactions/appTransactions/%s',
        ];
        yield 'getStatusOfSubscriptionRenewalDateExtensions (productId)' => [
            static fn (Validator $v, string $id) => $v->getStatusOfSubscriptionRenewalDateExtensions($id, 'req-1'),
            '/inApps/v1/subscriptions/extend/mass/%s/req-1',
        ];
        yield 'getStatusOfSubscriptionRenewalDateExtensions (requestIdentifier)' => [
            static fn (Validator $v, string $id) => $v->getStatusOfSubscriptionRenewalDateExtensions('com.example.pro', $id),
            '/inApps/v1/subscriptions/extend/mass/com.example.pro/%s',
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function maliciousNumericIds(): iterable
    {
        yield 'traversal'            => ['../subscriptions/' . self::VALID_ID];
        yield 'traversal suffix'     => [self::VALID_ID . '/../../v1/notifications/test'];
        yield 'dot segment'          => ['..'];
        yield 'query'                => [self::VALID_ID . '?status=1'];
        yield 'fragment'             => [self::VALID_ID . '#x'];
        yield 'slash'                => [self::VALID_ID . '/finish'];
        yield 'encoded slash'        => [self::VALID_ID . '%2F..'];
        yield 'empty'                => [''];
        yield 'whitespace only'      => ['   '];
        yield 'leading whitespace'   => [' ' . self::VALID_ID];
        yield 'trailing newline'     => [self::VALID_ID . "\n"];
        yield 'inner space'          => ['2000 000'];
        yield 'sign'                 => ['-1'];
        yield 'non-ascii digits'     => ['١٢٣'];
        yield 'letters'              => ['txn-123'];
    }

    /**
     * @param callable(Validator, string): mixed $invoke
     */
    #[DataProvider('numericEndpoints')]
    public function testNumericEndpointSendsValidIdUnchanged(callable $invoke, string $pathFormat): void
    {
        $this->invokeExpectingApiError($invoke, self::VALID_ID);

        $this->assertSentPath(sprintf($pathFormat, self::VALID_ID));
    }

    /**
     * @param callable(Validator, string): mixed $invoke
     */
    #[DataProvider('numericEndpoints')]
    public function testNumericEndpointRejectsMaliciousIdsWithoutSendingARequest(callable $invoke, string $pathFormat): void
    {
        foreach (self::maliciousNumericIds() as $case => [$id]) {
            $this->history = [];
            $validator     = $this->validator();

            try {
                $invoke($validator, $id);
                self::fail(sprintf('Expected "%s" (%s) to be rejected for %s', addcslashes($id, "\n"), $case, $pathFormat));
            } catch (ValidationException $e) {
                // validate() reports an empty ID as missing before the path is built.
                self::assertMatchesRegularExpression('/^(Invalid|Missing) (original )?transaction ID/', $e->getMessage(), $case);
            }

            self::assertSame([], $this->history, "No request may be sent for the {$case} case");
        }
    }

    /**
     * @param callable(Validator, string): mixed $invoke
     */
    #[DataProvider('freeFormEndpoints')]
    public function testFreeFormEndpointEncodesEachIdAsOneSegment(callable $invoke, string $pathFormat): void
    {
        $cases = [
            'valid'     => ['MK5TTTVWJH', 'MK5TTTVWJH'],
            'traversal' => ['../../v1/subscriptions/1', '..%2F..%2Fv1%2Fsubscriptions%2F1'],
            'query'     => ['abc?status=1', 'abc%3Fstatus%3D1'],
            'fragment'  => ['abc#frag', 'abc%23frag'],
            'slash'     => ['abc/def', 'abc%2Fdef'],
            'space'     => ['ab c', 'ab%20c'],
        ];

        foreach ($cases as $case => [$id, $encoded]) {
            $this->history = [];
            $this->invokeExpectingApiError($invoke, $id);

            $this->assertSentPath(sprintf($pathFormat, $encoded), $case);
        }
    }

    /**
     * @param callable(Validator, string): mixed $invoke
     */
    #[DataProvider('freeFormEndpoints')]
    public function testFreeFormEndpointRejectsEmptyAndDotSegments(callable $invoke, string $pathFormat): void
    {
        foreach (['empty' => '', 'whitespace only' => " \t ", 'dot' => '.', 'dot dot' => '..'] as $case => $id) {
            $this->history = [];

            try {
                $invoke($this->validator(), $id);
                self::fail(sprintf('Expected the %s case to be rejected for %s', $case, $pathFormat));
            } catch (ValidationException $e) {
                self::assertStringStartsWith('Invalid ', $e->getMessage(), $case);
            }

            self::assertSame([], $this->history, "No request may be sent for the {$case} case");
        }
    }

    /**
     * Send through a validator whose API answers 400 with an Apple error code, so every
     * endpoint fails the same way after the request has gone out.
     *
     * @param callable(Validator, string): mixed $invoke
     */
    private function invokeExpectingApiError(callable $invoke, string $id): void
    {
        try {
            $invoke($this->validator(), $id);
            self::fail('Expected the mocked 400 response to raise a ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(4000000, $e->getCode(), $e->getMessage());
        }
    }

    private function assertSentPath(string $expectedPath, string $case = ''): void
    {
        self::assertCount(1, $this->history, $case);

        $uri = $this->history[0]['request']->getUri();

        self::assertSame('api.storekit-sandbox.apple.com', $uri->getHost(), $case);
        self::assertSame($expectedPath, $uri->getPath(), $case);
        self::assertSame('', $uri->getFragment(), $case);
        self::assertStringNotContainsString('/../', '/' . $uri->getPath() . '/', $case);
    }

    private function validator(): Validator
    {
        $mock = new MockHandler([
            new GuzzleResponse(400, [], '{"errorCode":4000000,"errorMessage":"General bad request"}'),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->history));

        $validator = new Validator(
            signingKey: (string) file_get_contents(__DIR__ . '/certs/testSigningKey.p8'),
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::SANDBOX,
        );

        return $validator->setHttpClient(new Client(['handler' => $stack, 'http_errors' => false]));
    }
}
