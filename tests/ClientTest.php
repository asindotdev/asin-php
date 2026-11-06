<?php

declare(strict_types=1);

namespace Asin\Tests;

use Asin\BatchResult;
use Asin\BinaryResponse;
use Asin\Client;
use Asin\Endpoints;
use Asin\Exception\ApiException;
use Asin\Exception\ConfigurationException;
use Asin\Exception\ResponseDecodingException;
use Asin\Exception\SerializationException;
use Asin\Exception\TransportException;
use Asin\JsonResponse;
use Asin\RequestOptions;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    private RecordingHttpClient $http;
    private Client $client;

    protected function setUp(): void
    {
        $this->http = new RecordingHttpClient();
        $this->client = new Client('test-key', ['http_client' => $this->http, 'retry_delay' => 0.001]);
    }

    public function testRequiresApiKey(): void
    {
        putenv('ASIN_API_KEY');
        $this->expectException(ConfigurationException::class);
        new Client();
    }

    public function testRejectsInvalidBaseUrl(): void
    {
        $this->expectException(ConfigurationException::class);
        new Client('k', ['base_url' => 'ftp://example.test']);
    }

    public function testReadsApiKeyFromEnvironment(): void
    {
        putenv('ASIN_API_KEY=env-key');
        try {
            (new Client(null, ['http_client' => $this->http]))->request('GET', '/account');
        } finally {
            putenv('ASIN_API_KEY');
        }
        self::assertSame('Bearer env-key', $this->http->requests[0]->getHeaderLine('Authorization'));
    }

    public function testFeaturedOperation(): void
    {
        $this->http->respondWith(RecordingHttpClient::json(['hello' => 'world']));
        $response = $this->client->searchAmazon(['query' => 'wireless earbuds']);
        self::assertSame(['hello' => 'world'], $response->data);
        $request = $this->http->requests[0];
        self::assertSame('GET', $request->getMethod());
        self::assertSame('https://www.asin.dev/api/v1/search', explode('?', (string) $request->getUri())[0]);
        self::assertSame('Bearer test-key', $request->getHeaderLine('Authorization'));
        self::assertStringStartsWith('asin-php/', $request->getHeaderLine('User-Agent'));
    }

    public function testQuerySerialization(): void
    {
        $client = new Client('k', ['http_client' => $this->http, 'base_url' => 'https://example.test/']);
        $client->request('GET', '/anything', [
            'text' => 'a b&c', 'flag' => false, 'count' => 0, 'ratio' => 1.5, 'whole' => 2.0, 'list' => ['x', 'y'], 'nothing' => null,
        ]);
        self::assertSame(
            'https://example.test/anything?text=a%20b%26c&flag=false&count=0&ratio=1.5&whole=2&list=x&list=y',
            (string) $this->http->requests[0]->getUri(),
        );
    }

    public function testPostSendsJsonBody(): void
    {
        $this->client->request('POST', '/anything', ['query' => 'x', 'page' => 2, 'gone' => null]);
        $request = $this->http->requests[0];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame(['query' => 'x', 'page' => 2], json_decode((string) $request->getBody(), true));
        self::assertSame('', $request->getUri()->getQuery());
    }

    public function testWaitLocationDependsOnTheEndpoint(): void
    {
        $this->client->request('POST', '/products/{id}/check', ['id' => 'p1', 'wait' => true]);
        self::assertSame('wait=true', $this->http->requests[0]->getUri()->getQuery());
        $this->client->request('POST', '/products', ['product' => 'B09DFCB66S', 'wait' => true]);
        self::assertSame('', $this->http->requests[1]->getUri()->getQuery());
        self::assertSame(['product' => 'B09DFCB66S', 'wait' => true], json_decode((string) $this->http->requests[1]->getBody(), true));
    }

    public function testPatchAndDelete(): void
    {
        $this->client->request('PATCH', '/products/{id}', ['id' => 'p1', 'paused' => true]);
        self::assertSame('PATCH', $this->http->requests[0]->getMethod());
        self::assertSame(['paused' => true], json_decode((string) $this->http->requests[0]->getBody(), true));
        $this->client->request('DELETE', '/products/{id}', ['id' => 'p1']);
        self::assertSame('DELETE', $this->http->requests[1]->getMethod());
        self::assertSame('', (string) $this->http->requests[1]->getBody());
    }

    public function testPathParameters(): void
    {
        $this->client->request('GET', '/items/{id}', ['id' => 'a/b c', 'other' => 1]);
        self::assertSame('https://www.asin.dev/api/v1/items/a%2Fb%20c?other=1', (string) $this->http->requests[0]->getUri());
        $this->expectException(SerializationException::class);
        $this->client->request('GET', '/items/{id}');
    }

    public function testRequiredParameters(): void
    {
        try {
            $this->client->searchAmazon([]); // @phpstan-ignore argument.type
            self::fail('Expected SerializationException');
        } catch (SerializationException) {
        }
        try {
            $this->client->searchAmazon(['query' => 'wireless earbuds', 'not_a_parameter' => 1]);
            self::fail('Expected SerializationException');
        } catch (SerializationException $exception) {
            self::assertSame('not_a_parameter', $exception->param);
        }
        self::assertSame([], $this->http->requests);
    }

    public function testUnsupportedMethod(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->client->getAccount([], new RequestOptions(method: 'POST'));
    }

    public function testEveryEndpoint(): void
    {
        foreach (Endpoints::ALL as $name => $endpoint) {
            $method = lcfirst(str_replace('_', '', ucwords($name, '_')));
            if (!method_exists($this->client, $method)) {
                $method .= 'Endpoint';
            }
            $this->client->{$method}(self::REQUIRED[$name] ?? []);
            $request = $this->http->requests[\count($this->http->requests) - 1];
            self::assertSame('https://www.asin.dev/api/v1' . str_replace('{id}', 'test', $endpoint['path']), explode('?', (string) $request->getUri())[0], $name);
            self::assertSame($endpoint['methods'][0], $request->getMethod(), $name);
        }
        self::assertCount(\count(Endpoints::ALL), $this->http->requests);
    }

    public function testBinaryResponse(): void
    {
        $this->http->respondWith(new Response(200, ['Content-Type' => 'image/png'], "\x89PNG"));
        $response = $this->client->request('GET', '/anything');
        self::assertInstanceOf(BinaryResponse::class, $response);
        self::assertSame("\x89PNG", $response->bytes);
        self::assertSame('image/png', $response->contentType);
    }

    public function testNonJsonSuccessForJsonOperation(): void
    {
        $this->http->respondWith(new Response(200, ['Content-Type' => 'text/html'], '<html>'));
        $this->expectException(ResponseDecodingException::class);
        $this->client->searchAmazon(['query' => 'wireless earbuds']);
    }

    public function testApiError(): void
    {
        $this->http->respondWith(RecordingHttpClient::json(['error' => 'Invalid API Key'], 401));
        try {
            $this->client->request('GET', '/account');
            self::fail('Expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(401, $exception->status);
            self::assertSame('Invalid API Key', $exception->getMessage());
            self::assertSame(['error' => 'Invalid API Key'], $exception->responseBody);
        }
    }

    public function testNonJsonErrorBody(): void
    {
        $this->http->respondWith(new Response(502, [], 'Bad gateway'));
        try {
            $this->client->request('GET', '/account');
            self::fail('Expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(502, $exception->status);
            self::assertSame('Bad gateway', $exception->rawResponse->body);
        }
    }

    public function testTransportError(): void
    {
        $this->http->failWith(new \RuntimeException('network down'));
        $this->expectException(TransportException::class);
        $this->client->request('GET', '/account');
    }

    public function testJsonResponseType(): void
    {
        $response = $this->client->request('GET', '/account');
        self::assertInstanceOf(JsonResponse::class, $response);
    }

    public function testRetriesThenSucceeds(): void
    {
        $this->http->queue(RecordingHttpClient::json(['error' => 'busy'], 503));
        $this->http->queue(new Response(429, ['Retry-After' => '0', 'Content-Type' => 'application/json'], '{}'));
        $this->http->queue(RecordingHttpClient::json(['ok' => true]));
        $response = $this->client->request('GET', '/account');
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertCount(3, $this->http->requests);
    }

    public function testRetryLimitsAndClientErrors(): void
    {
        $client = new Client('k', ['http_client' => $this->http, 'retry_delay' => 0.001, 'max_retries' => 2]);
        $this->http->respondWith(RecordingHttpClient::json(['error' => 'busy'], 503));
        try {
            $client->request('GET', '/account');
            self::fail('Expected ApiException');
        } catch (ApiException) {
        }
        self::assertCount(3, $this->http->requests);
        $this->http->respondWith(RecordingHttpClient::json(['error' => 'Payment required'], 402));
        try {
            $client->request('GET', '/account');
        } catch (ApiException) {
        }
        self::assertCount(4, $this->http->requests);
        $this->http->respondWith(RecordingHttpClient::json([], 500));
        try {
            $client->request('GET', '/account', [], new RequestOptions(maxRetries: 0));
        } catch (ApiException) {
        }
        self::assertCount(5, $this->http->requests);
    }

    public function testUnauthorizedIsNeverRetried(): void
    {
        $cases = [
            "Rate limit exceeded: You've exceeded the rate limit on your subscription" => 1,
            'Rate limit exceeded for the API key: quota daily' => 1,
            'Invalid API Key' => 1,
        ];
        foreach ($cases as $message => $expected) {
            $http = new RecordingHttpClient();
            $http->queue(RecordingHttpClient::json(['error' => $message], 401));
            $http->respondWith(RecordingHttpClient::json([]));
            $client = new Client('k', ['http_client' => $http, 'retry_delay' => 0.001]);
            try {
                $client->request('GET', '/account');
            } catch (ApiException) {
            }
            self::assertCount($expected, $http->requests, $message);
        }
    }

    public function testNestedErrorMessage(): void
    {
        $http = new RecordingHttpClient();
        $http->queue(RecordingHttpClient::json(['error' => ['code' => 'insufficient_credits', 'message' => 'No credits left']], 402));
        $client = new Client('k', ['http_client' => $http]);
        try {
            $client->request('GET', '/account');
            self::fail('Expected an API error.');
        } catch (ApiException $error) {
            self::assertSame('No credits left', $error->errorMessage);
        }
        $this->client->searchAmazon(['query' => 'wireless earbuds']);
    }

    public function testBatchWithPsr18Client(): void
    {
        $results = $this->client->batch([
            ['searchAmazon', ['query' => 'wireless earbuds']],
            ['searchAmazon', []],
            ['search_amazon', ['query' => 'wireless earbuds']],
        ]);
        self::assertCount(3, $results);
        self::assertTrue($results[0]->ok());
        self::assertInstanceOf(SerializationException::class, $results[1]->error);
        self::assertTrue($results[2]->ok());
        self::assertSame([0, 1, 2], array_map(static fn(BatchResult $r): int => $r->index, $results));
        self::assertCount(2, $this->http->requests);
    }

    public function testBatchWithGuzzlePoolRetries(): void
    {
        $mock = new MockHandler([
            RecordingHttpClient::json(['n' => 1]),
            RecordingHttpClient::json(['error' => 'busy'], 503),
            RecordingHttpClient::json(['n' => 2]),
        ]);
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock), 'http_errors' => false]);
        $client = new Client('k', ['http_client' => $guzzle, 'retry_delay' => 0.001]);
        $results = $client->batch([['searchAmazon', ['query' => 'wireless earbuds']], ['searchAmazon', ['query' => 'wireless earbuds']]], 2);
        self::assertTrue($results[0]->ok() && $results[1]->ok());
        self::assertInstanceOf(JsonResponse::class, $results[1]->value);
        self::assertSame(['n' => 2], $results[1]->value->data);
        self::assertSame(0, $mock->count());
    }

    /** @var array<string, array<string, mixed>> */
    private const REQUIRED = [
        'check_product' => ['id' => 'test'],
        'delete_product' => ['id' => 'test'],
        'fetch_offers' => ['asin' => 'test'],
        'get_asin_history' => ['asin' => 'test'],
        'get_offers' => ['asin' => 'test'],
        'get_product' => ['id' => 'test'],
        'search_amazon' => ['query' => 'test'],
        'track_product' => ['product' => 'test'],
        'update_product' => ['id' => 'test'],
    ];
}
