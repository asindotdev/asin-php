<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Asin\Client;

$client = new Client(); // reads ASIN_API_KEY
$response = $client->searchAmazon(['query' => 'wireless earbuds']);
echo json_encode($response->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
