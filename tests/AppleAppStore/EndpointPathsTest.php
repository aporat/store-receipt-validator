<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\ConsumptionRequest;
use ReceiptValidator\AppleAppStore\ConsumptionRequestV1;
use ReceiptValidator\AppleAppStore\DeliveryStatus;
use ReceiptValidator\AppleAppStore\ExtendRenewalDateRequest;
use ReceiptValidator\AppleAppStore\MassExtendRenewalDateRequest;
use ReceiptValidator\AppleAppStore\NotificationHistoryRequest;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReflectionClass;
use ReflectionMethod;

/**
 * The App Store Server API contract: every endpoint the Validator calls, with the
 * HTTP method and path exactly as Apple documents them.
 *
 * Each row links to Apple's documentation for that endpoint. When a path in the
 * Validator changes, this test fails until the matching row is updated, which is
 * the moment to check the row against the linked page rather than the code.
 *
 * Issue #231 shipped because a path was edited together with the only test that
 * asserted it. Keeping the whole inventory in one table makes such an edit a
 * visible change to a documented value instead of an incidental string.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi
 *
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
final class EndpointPathsTest extends TestCase
{
    private const string TRANSACTION_ID          = 'txn-123';
    private const string ORIGINAL_TRANSACTION_ID = 'orig-456';
    private const string ORDER_ID                = 'ML0000000001';
    private const string PRODUCT_ID              = 'com.example.pro';
    private const string REQUEST_IDENTIFIER      = 'req-789';
    private const string TEST_NOTIFICATION_TOKEN = 'tok-abc';

    private const string DOCS = 'https://developer.apple.com/documentation/appstoreserverapi/';

    /**
     * One row per endpoint: validator method, HTTP verb, documented path, Apple docs slug, invocation.
     *
     * @return iterable<string, array{string, string, string, string, callable(Validator): mixed}>
     */
    public static function endpoints(): iterable
    {
        $txn   = self::TRANSACTION_ID;
        $orig  = self::ORIGINAL_TRANSACTION_ID;
        $order = self::ORDER_ID;
        $prod  = self::PRODUCT_ID;
        $req   = self::REQUEST_IDENTIFIER;
        $tok   = self::TEST_NOTIFICATION_TOKEN;

        // --- Transactions ---------------------------------------------------------

        yield 'getTransactionHistory' => [
            'getTransactionHistory', 'GET', "/inApps/v2/history/{$txn}",
            'get-transaction-history',
            static fn (Validator $v) => $v->getTransactionHistory($txn),
        ];

        yield 'getTransactionInfo' => [
            'getTransactionInfo', 'GET', "/inApps/v1/transactions/{$txn}",
            'get-transaction-info',
            static fn (Validator $v) => $v->getTransactionInfo($txn),
        ];

        yield 'getAppTransactionInfo' => [
            'getAppTransactionInfo', 'GET', "/inApps/v1/transactions/appTransactions/{$txn}",
            'get-app-transaction-info',
            static fn (Validator $v) => $v->getAppTransactionInfo($txn),
        ];

        yield 'finishTransaction' => [
            'finishTransaction', 'POST', "/inApps/v1/transactions/{$txn}/finish",
            'finish-a-transaction',
            static fn (Validator $v) => $v->finishTransaction($txn),
        ];

        yield 'setAppAccountToken' => [
            'setAppAccountToken', 'PUT', "/inApps/v1/transactions/{$orig}/appAccountToken",
            'set-app-account-token',
            static fn (Validator $v) => $v->setAppAccountToken($orig, '7389a31a-fb6d-4569-a2a6-db7d85d84813'),
        ];

        yield 'sendConsumptionInformation (v2)' => [
            'sendConsumptionInformation', 'PUT', "/inApps/v2/transactions/consumption/{$txn}",
            'send-consumption-information',
            static fn (Validator $v) => $v->sendConsumptionInformation(
                $txn,
                new ConsumptionRequest(true, false, DeliveryStatus::DELIVERED),
            ),
        ];

        yield 'sendConsumptionInformation (v1, deprecated)' => [
            'sendConsumptionInformation', 'PUT', "/inApps/v1/transactions/consumption/{$txn}",
            'send-consumption-information-v1',
            static fn (Validator $v) => $v->sendConsumptionInformation($txn, new ConsumptionRequestV1(true, false)),
        ];

        // --- Orders and refunds --------------------------------------------------

        yield 'lookUpOrderId' => [
            'lookUpOrderId', 'GET', "/inApps/v1/lookup/{$order}",
            'look-up-order-id',
            static fn (Validator $v) => $v->lookUpOrderId($order),
        ];

        yield 'getRefundHistory' => [
            'getRefundHistory', 'GET', "/inApps/v2/refund/lookup/{$txn}",
            'get-refund-history',
            static fn (Validator $v) => $v->getRefundHistory($txn),
        ];

        // --- Subscriptions -------------------------------------------------------

        yield 'getAllSubscriptionStatuses' => [
            'getAllSubscriptionStatuses', 'GET', "/inApps/v1/subscriptions/{$orig}",
            'get-all-subscription-statuses',
            static fn (Validator $v) => $v->getAllSubscriptionStatuses($orig),
        ];

        yield 'extendSubscriptionRenewalDate' => [
            'extendSubscriptionRenewalDate', 'PUT', "/inApps/v1/subscriptions/extend/{$orig}",
            'extend-a-subscription-renewal-date',
            static function (Validator $v) use ($orig) {
                $request                   = new ExtendRenewalDateRequest();
                $request->extendByDays     = 7;
                $request->extendReasonCode = 1;

                return $v->extendSubscriptionRenewalDate($orig, $request);
            },
        ];

        yield 'extendSubscriptionRenewalDatesForAllActiveSubscribers' => [
            'extendSubscriptionRenewalDatesForAllActiveSubscribers', 'POST', '/inApps/v1/subscriptions/extend/mass',
            'extend-subscription-renewal-dates-for-all-active-subscribers',
            static function (Validator $v) use ($prod, $req) {
                $request                    = new MassExtendRenewalDateRequest();
                $request->extendByDays      = 7;
                $request->extendReasonCode  = 1;
                $request->productId         = $prod;
                $request->requestIdentifier = $req;

                return $v->extendSubscriptionRenewalDatesForAllActiveSubscribers($request);
            },
        ];

        yield 'getStatusOfSubscriptionRenewalDateExtensions' => [
            'getStatusOfSubscriptionRenewalDateExtensions', 'GET', "/inApps/v1/subscriptions/extend/mass/{$prod}/{$req}",
            'get-status-of-subscription-renewal-date-extensions',
            static fn (Validator $v) => $v->getStatusOfSubscriptionRenewalDateExtensions($prod, $req),
        ];

        // --- Notifications -------------------------------------------------------

        yield 'requestTestNotification' => [
            'requestTestNotification', 'POST', '/inApps/v1/notifications/test',
            'request-a-test-notification',
            static fn (Validator $v) => $v->requestTestNotification(),
        ];

        yield 'getTestNotificationStatus' => [
            'getTestNotificationStatus', 'GET', "/inApps/v1/notifications/test/{$tok}",
            'get-test-notification-status',
            static fn (Validator $v) => $v->getTestNotificationStatus($tok),
        ];

        yield 'getNotificationHistory' => [
            'getNotificationHistory', 'POST', '/inApps/v1/notifications/history',
            'get-notification-history',
            static fn (Validator $v) => $v->getNotificationHistory(
                new NotificationHistoryRequest(1_700_000_000_000, 1_700_086_400_000),
            ),
        ];
    }

    /**
     * @param callable(Validator): mixed $invoke
     */
    #[DataProvider('endpoints')]
    public function testEndpointUsesDocumentedMethodAndPath(
        string $method,
        string $verb,
        string $path,
        string $docsSlug,
        callable $invoke,
    ): void {
        self::assertTrue(method_exists(Validator::class, $method), "Validator::{$method}() does not exist");

        $observed = null;
        $client   = $this->createMock(ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(static function (RequestInterface $request) use (&$observed): bool {
                $observed = [$request->getMethod(), $request->getUri()->getPath()];

                return true;
            }))
            // A 400 with an Apple error code makes every endpoint throw the same way,
            // so the table needs no per-endpoint response fixtures.
            ->willReturn(new GuzzleResponse(400, [], json_encode([
                'errorCode'    => 4000000,
                'errorMessage' => 'General bad request',
            ], JSON_THROW_ON_ERROR)));

        try {
            $invoke($this->validator($client));
            self::fail('Expected the 400 response to raise a ValidationException');
        } catch (ValidationException $e) {
            self::assertStringContainsString('App Store API error [4000000]', $e->getMessage());
        }

        self::assertSame(
            [$verb, $path],
            $observed,
            sprintf('%s() must send %s %s; see %s%s', $method, $verb, $path, self::DOCS, $docsSlug)
        );
    }

    /**
     * Every public Validator method that builds an /inApps/ path must appear in the table,
     * so a new endpoint cannot ship without a documented row.
     */
    public function testEveryEndpointMethodHasARow(): void
    {
        $tabulated = [];
        foreach (self::endpoints() as [$method]) {
            $tabulated[$method] = true;
        }

        $reflection = new ReflectionClass(Validator::class);
        $source     = file((string) $reflection->getFileName(), FILE_IGNORE_NEW_LINES) ?: [];

        $missing = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== Validator::class) {
                continue;
            }

            $body = implode("\n", array_slice(
                $source,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            ));

            if (str_contains($body, "'/inApps/") && !isset($tabulated[$method->getName()])) {
                $missing[] = $method->getName();
            }
        }

        self::assertSame([], $missing, 'Add these endpoints to EndpointPathsTest::endpoints(): ' . implode(', ', $missing));
    }

    public function testSandboxAndProductionHosts(): void
    {
        // Apple's documentation and official libraries now use api.storekit.apple.com and
        // api.storekit-sandbox.apple.com. The legacy *.itunes.apple.com hosts still answer
        // and are what this library has always sent, so they stay pinned here until a
        // deliberate change, since a hostname switch can break callers' egress allowlists.
        $hosts = [
            [Environment::SANDBOX, 'api.storekit-sandbox.itunes.apple.com'],
            [Environment::PRODUCTION, 'api.storekit.itunes.apple.com'],
        ];

        foreach ($hosts as [$env, $host]) {
            $client = $this->createMock(ClientInterface::class);
            $client
                ->expects($this->once())
                ->method('sendRequest')
                ->with($this->callback(static fn (RequestInterface $request): bool =>
                    $request->getUri()->getScheme() === 'https' && $request->getUri()->getHost() === $host))
                ->willReturn(new GuzzleResponse(400, [], '{"errorCode":4000000,"errorMessage":"x"}'));

            try {
                $this->validator($client, $env)->getTransactionInfo(self::TRANSACTION_ID);
            } catch (ValidationException) {
                // expected: the 400 is only there to short-circuit response parsing
            }
        }

        $this->addToAssertionCount(2);
    }

    private function validator(ClientInterface $client, Environment $environment = Environment::SANDBOX): Validator
    {
        $validator = new Validator(
            signingKey: (string) file_get_contents(__DIR__ . '/certs/testSigningKey.p8'),
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: $environment,
        );

        return $validator->setHttpClient($client);
    }
}
