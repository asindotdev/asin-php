# Asin PHP SDK

The official PHP SDK for the [Asin](https://www.asin.dev) Amazon price tracking API.

Track Amazon prices and products with a scheduled watchlist, price history, offers and alerts.

The SDK exposes **11 methods**, one per documented endpoint (with array-shape PHPDoc for static analysis), plus a generic `request()`. It sends requests through any PSR-18 client and uses Guzzle by default.

## Requirements

- PHP 8.1 or newer

## Installation

```sh
composer require asindotdev/sdk
```

## Get your API key

[Sign up for Asin](https://www.asin.dev/signup) or sign in, then copy your API key from the [dashboard](https://www.asin.dev/app). The client reads the `ASIN_API_KEY` environment variable when no key is passed.

## Quick start

```php
<?php

require 'vendor/autoload.php';

use Asin\Client;

$client = new Client(getenv('ASIN_API_KEY') ?: null);
$response = $client->searchAmazon(['query' => 'wireless earbuds']);
print_r($response->data);
```

Parameter arrays use the names of the [API reference](https://www.asin.dev/docs). Unknown names throw a `SerializationException`; use `request()` for undocumented parameters.

## HTTP methods

Each method uses the HTTP verb of its endpoint. `GET` and `DELETE` send parameters in the query string; `POST` and `PATCH` send them as a JSON body, except `wait` on `/products/{id}/check`, which stays in the query string. Null values are never sent, so `target_price` cannot be removed through the SDK yet.

`null` values are omitted, `false` and `0` are sent, and lists repeat the parameter name.

## Any endpoint

```php
$response = $client->request('GET', '/account');
```

## Errors

```php
use Asin\Exception\ApiException;

try {
    $client->searchAmazon(['query' => 'wireless earbuds']);
} catch (ApiException $exception) {
    echo $exception->status, ': ', $exception->getMessage();
}
```

| Exception | When |
| --- | --- |
| `ConfigurationException` | Missing API key, invalid options or unsupported method. |
| `SerializationException` | A required parameter is missing, unknown or can't be sent. |
| `TransportException` | Network failure or timeout. |
| `ApiException` | Non-2xx status (`status`, `errorMessage`, `responseBody`, `rawResponse`). |
| `ResponseDecodingException` | A success response could not be decoded. |

All of them extend `Asin\Exception\AsinException`.

## Retries

Requests are retried up to 3 times after a network error, a `408`, `429`, `500`, `502`, `503` or `504`. Other statuses, such as `400`, `401`, `402` or `404`, and client-side timeouts are not retried. The delay starts at 1 second and grows 1.5× per retry (capped at 30 seconds); a `Retry-After` header wins.

```php
$client = new Client(null, ['max_retries' => 5, 'retry_delay' => 0.5]);
$client->searchAmazon(['query' => 'wireless earbuds'], new RequestOptions(maxRetries: 0)); // one request without retries
```

## Batch requests

`batch()` sends several calls with at most 10 requests in flight (`$concurrency`), through Guzzle's connection pool, and returns one `BatchResult` per call in input order. A failing call never stops the others. With a custom PSR-18 client the calls run one after another.

```php
$inputs = ['wireless earbuds', 'mechanical keyboard', 'usb-c hub'];
$calls = array_map(static fn(string $value): array => ['searchAmazon', ['query' => $value]], $inputs);

foreach ($client->batch($calls, concurrency: 5) as $result) {
    if ($result->ok()) {
        print_r($result->value->data);
    } else {
        echo $inputs[$result->index], ': ', $result->error->getMessage(), "\n";
    }
}
```

| Constant | Default | Description |
| --- | --- | --- |
| `Client::DEFAULT_CONCURRENCY` | `10` | Tasks run at the same time by batch |
| `Client::DEFAULT_TIMEOUT` | `120.0` | Request timeout (seconds) |
| `Client::MAX_RETRIES` | `3` | Retry attempts after a network error, a 408/429/5xx |
| `Client::RETRY_BACKOFF_FACTOR` | `1.5` | Exponential backoff multiplier |
| `Client::DEFAULT_RETRY_DELAY` | `1.0` | Delay before the first retry (seconds) |

## Options and custom HTTP clients

```php
$client = new Client(null, [
    'timeout' => 60.0,               // seconds, default 120 (default Guzzle client only)
    'headers' => ['X-Trace' => 'abc'],
    'http_client' => $psr18Client,   // optional: any PSR-18 client
]);
```

## Endpoints

11 methods, generated from the [OpenAPI document](https://www.asin.dev/openapi.json).

| Method | HTTP | Description |
| --- | --- | --- |
| `checkProduct()` | POST `/products/{id}/check` | Asks for a check right away (1 credit), at most once per 5 minutes; earlier calls return the product unchanged. A paused product has to be… |
| `deleteProduct()` | DELETE `/products/{id}` | Removes the product and its price history. |
| `fetchOffers()` | POST `/offers` | Reads the offers on Amazon for 1 credit, refunded if it doesn't answer. A read made in the last 3 hours, by anyone, is returned instead, fo… |
| `getAccount()` | GET `/account` | Your plan, the credits left (monthly allowance and packs), how many products you track and the credits they use per month. |
| `getAsinHistory()` | GET `/history` | Every price Asin read for an ASIN on a marketplace over the period, whoever tracks it, oldest first, with the lowest, highest and average o… |
| `getOffers()` | GET `/offers` | Who sells the product and at what price, as last read, cheapest first, with Amazon's own price and the cheapest new and used offers (used i… |
| `getProduct()` | GET `/products/{id}` | The product's latest state and every price read of the period, oldest first. The history covers every read of this ASIN on this marketplace… |
| `listProducts()` | GET `/products` | Your watchlist, newest first, with each product's latest state and its recent prices (trend). Filter with asin (and marketplace) to know if… |
| `searchAmazon()` | GET `/search` | Amazon search results (up to 60, sponsored included), to find the ASIN of a product. Free, limited to 30 searches per hour. |
| `trackProduct()` | POST `/products` | Adds an ASIN, an Amazon link or a short link (amzn.to, a.co…) to your watchlist. The first check runs in the background within seconds (che… |
| `updateProduct()` | PATCH `/products/{id}` | Target price, drop alerts, check interval, or pause. Send only what changes. |

## License

MIT — see [LICENSE](LICENSE).
