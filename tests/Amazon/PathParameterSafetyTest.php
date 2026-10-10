<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\Amazon;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\Amazon\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Amazon RVS path parameters are percent-encoded as single segments, and the dot segments
 * that rawurlencode() would leave intact are rejected before any request is sent.
 */
#[CoversClass(Validator::class)]
final class PathParameterSafetyTest extends TestCase
{
    /** @var list<array{request: RequestInterface}> */
    private array $history = [];

    public function testEncodesReceiptAndUserIdsAsSingleSegments(): void
    {
        $cases = [
            'valid'     => ['receipt-1', 'user-1', 'receipt-1', 'user-1'],
            'traversal' => ['../../x', '../y', '..%2F..%2Fx', '..%2Fy'],
            'query'     => ['r?a=1', 'u?b', 'r%3Fa%3D1', 'u%3Fb'],
            'fragment'  => ['r#a', 'u#b', 'r%23a', 'u%23b'],
            'slash'     => ['r/a', 'u/b', 'r%2Fa', 'u%2Fb'],
        ];

        foreach ($cases as $case => [$receiptId, $userId, $encodedReceipt, $encodedUser]) {
            $this->history = [];

            try {
                $this->validator()->validate($receiptId, $userId);
            } catch (ValidationException) {
                // the mocked 400 only short-circuits response parsing
            }

            self::assertCount(1, $this->history, $case);
            $uri = $this->history[0]['request']->getUri();
            self::assertSame(
                "/sandbox/version/1.0/verifyReceiptId/developer/secret/user/{$encodedUser}/receiptId/{$encodedReceipt}",
                $uri->getPath(),
                $case
            );
            self::assertSame('', $uri->getQuery(), $case);
            self::assertSame('', $uri->getFragment(), $case);
        }
    }

    public function testRejectsDotSegmentsAndWhitespaceWithoutSendingARequest(): void
    {
        $cases = [
            ['..', 'user'],
            ['receipt', '.'],
            ['   ', 'user'],
            ['receipt', ' '],
            ['', 'user'],
            ['receipt', ''],
        ];

        foreach ($cases as [$receiptId, $userId]) {
            $this->history = [];

            try {
                $this->validator()->validate($receiptId, $userId);
                self::fail(sprintf('Expected receipt "%s" / user "%s" to be rejected', $receiptId, $userId));
            } catch (ValidationException $e) {
                self::assertMatchesRegularExpression('/^(Invalid|Missing) Amazon/', $e->getMessage());
            }

            self::assertSame([], $this->history);
        }
    }

    public function testRejectsDotSegmentSecret(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid Amazon developer secret');

        (new Validator('..', Environment::SANDBOX))->validate('receipt', 'user');
    }

    private function validator(): Validator
    {
        $stack = HandlerStack::create(new MockHandler([new GuzzleResponse(400, [], '{}')]));
        $stack->push(Middleware::history($this->history));

        $validator = new Validator('secret', Environment::SANDBOX);

        return $validator->setHttpClient(new Client(['handler' => $stack, 'http_errors' => false]));
    }
}
