<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\GooglePlay\JWT\CallbackAccessTokenProvider;
use ReceiptValidator\GooglePlay\SubscriptionCancellationType;
use ReceiptValidator\GooglePlay\Validator;

/**
 * Google Play path parameters are percent-encoded as single segments, and the dot segments
 * that rawurlencode() would leave intact are rejected before any request is sent.
 */
#[CoversClass(Validator::class)]
final class PathParameterSafetyTest extends TestCase
{
    private const string BASE = '/androidpublisher/v3/applications/com.example.app';

    /** @var list<array{request: RequestInterface}> */
    private array $history = [];

    /**
     * @return iterable<string, array{callable(Validator, string): mixed, string}>
     */
    public static function endpoints(): iterable
    {
        yield 'getSubscriptionPurchaseV2' => [
            static fn (Validator $v, string $id) => $v->getSubscriptionPurchaseV2($id),
            '/purchases/subscriptionsv2/tokens/%s',
        ];
        yield 'cancelSubscription' => [
            static fn (Validator $v, string $id) => $v->cancelSubscription($id, SubscriptionCancellationType::USER_REQUESTED_STOP_RENEWALS),
            '/purchases/subscriptionsv2/tokens/%s:cancel',
        ];
        yield 'getProductPurchaseV2' => [
            static fn (Validator $v, string $id) => $v->getProductPurchaseV2($id),
            '/purchases/productsv2/tokens/%s',
        ];
        yield 'getProductPurchase (token)' => [
            static fn (Validator $v, string $id) => $v->getProductPurchase('coins', $id),
            '/purchases/products/coins/tokens/%s',
        ];
        yield 'getProductPurchase (product)' => [
            static fn (Validator $v, string $id) => $v->getProductPurchase($id, 'tok'),
            '/purchases/products/%s/tokens/tok',
        ];
        yield 'acknowledgeSubscription (subscription)' => [
            static fn (Validator $v, string $id) => $v->acknowledgeSubscription($id, 'tok'),
            '/purchases/subscriptions/%s/tokens/tok:acknowledge',
        ];
        yield 'consumeProduct (product)' => [
            static fn (Validator $v, string $id) => $v->consumeProduct($id, 'tok'),
            '/purchases/products/%s/tokens/tok:consume',
        ];
        yield 'getOrder' => [
            static fn (Validator $v, string $id) => $v->getOrder($id),
            '/orders/%s',
        ];
    }

    /**
     * @param callable(Validator, string): mixed $invoke
     */
    #[DataProvider('endpoints')]
    public function testEncodesEachValueAsOneSegment(callable $invoke, string $pathFormat): void
    {
        $cases = [
            'valid'     => ['GPA.1234-5678', 'GPA.1234-5678'],
            'traversal' => ['../../reviews', '..%2F..%2Freviews'],
            'query'     => ['a?b=1', 'a%3Fb%3D1'],
            'fragment'  => ['a#b', 'a%23b'],
            'slash'     => ['a/b', 'a%2Fb'],
        ];

        foreach ($cases as $case => [$value, $encoded]) {
            $this->history = [];

            try {
                $invoke($this->validator(), $value);
            } catch (ValidationException) {
                // the mocked 404 only short-circuits response parsing
            }

            self::assertCount(1, $this->history, $case);
            $uri = $this->history[0]['request']->getUri();
            self::assertSame(self::BASE . sprintf($pathFormat, $encoded), $uri->getPath(), $case);
            self::assertSame('', $uri->getFragment(), $case);
        }
    }

    /**
     * @param callable(Validator, string): mixed $invoke
     */
    #[DataProvider('endpoints')]
    public function testRejectsEmptyWhitespaceAndDotSegments(callable $invoke, string $pathFormat): void
    {
        foreach (['', '   ', '.', '..'] as $value) {
            $this->history = [];

            try {
                $invoke($this->validator(), $value);
                self::fail(sprintf('Expected "%s" to be rejected for %s', $value, $pathFormat));
            } catch (ValidationException $e) {
                self::assertMatchesRegularExpression('/^(Invalid Google Play|Google Play .* cannot be empty)/', $e->getMessage());
            }

            self::assertSame([], $this->history);
        }
    }

    public function testRejectsDotSegmentPackageName(): void
    {
        $this->expectException(ValidationException::class);

        new Validator('..');
    }

    private function validator(): Validator
    {
        $stack = HandlerStack::create(new MockHandler([new GuzzleResponse(404, [], '{}')]));
        $stack->push(Middleware::history($this->history));

        $validator = new Validator('com.example.app');
        $validator->setAccessTokenProvider(new CallbackAccessTokenProvider(static fn (): string => 'token'));

        return $validator->setHttpClient(new Client(['handler' => $stack, 'http_errors' => false]));
    }
}
