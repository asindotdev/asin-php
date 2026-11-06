<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Asin\Client;

$client = new Client(); // reads ASIN_API_KEY
try {
    $response = $client->request('GET', '/account');
    if ($response instanceof Asin\JsonResponse) {
        print_r($response->data);
    }
} catch (Asin\Exception\ApiException $exception) {
    fwrite(STDERR, sprintf("HTTP %d: %s\n", $exception->status, $exception->getMessage()));
}
