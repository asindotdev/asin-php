<?php

declare(strict_types=1);

namespace Asin;

// Generated from the Asin OpenAPI document. Do not edit by hand.

/**
 * Path, methods, response kind and parameter names of every documented operation.
 */
final class Endpoints
{
    /**
     * @var array<string, array{path: string, methods: list<'GET'|'POST'|'PATCH'|'DELETE'>, response: 'json'|'any', required: list<string>, params: list<string>}>
     */
    public const ALL = [
        'check_product' => [
            'path' => '/products/{id}/check',
            'methods' => ['POST'],
            'response' => 'json',
            'required' => ['id'],
            'params' => ['id', 'wait'],
        ],
        'delete_product' => [
            'path' => '/products/{id}',
            'methods' => ['DELETE'],
            'response' => 'json',
            'required' => ['id'],
            'params' => ['id'],
        ],
        'fetch_offers' => [
            'path' => '/offers',
            'methods' => ['POST'],
            'response' => 'json',
            'required' => ['asin'],
            'params' => ['asin', 'marketplace', 'refresh'],
        ],
        'get_account' => [
            'path' => '/account',
            'methods' => ['GET'],
            'response' => 'json',
            'required' => [],
            'params' => [],
        ],
        'get_asin_history' => [
            'path' => '/history',
            'methods' => ['GET'],
            'response' => 'json',
            'required' => ['asin'],
            'params' => ['asin', 'marketplace', 'days'],
        ],
        'get_offers' => [
            'path' => '/offers',
            'methods' => ['GET'],
            'response' => 'json',
            'required' => ['asin'],
            'params' => ['asin', 'marketplace'],
        ],
        'get_product' => [
            'path' => '/products/{id}',
            'methods' => ['GET'],
            'response' => 'json',
            'required' => ['id'],
            'params' => ['id', 'days'],
        ],
        'list_products' => [
            'path' => '/products',
            'methods' => ['GET'],
            'response' => 'json',
            'required' => [],
            'params' => ['asin', 'marketplace', 'filter', 'page', 'limit'],
        ],
        'search_amazon' => [
            'path' => '/search',
            'methods' => ['GET'],
            'response' => 'json',
            'required' => ['query'],
            'params' => ['query', 'marketplace'],
        ],
        'track_product' => [
            'path' => '/products',
            'methods' => ['POST'],
            'response' => 'json',
            'required' => ['product'],
            'params' => ['product', 'marketplace', 'target_price', 'interval_minutes', 'wait'],
        ],
        'update_product' => [
            'path' => '/products/{id}',
            'methods' => ['PATCH'],
            'response' => 'json',
            'required' => ['id'],
            'params' => ['id', 'target_price', 'interval_minutes', 'alert_on_drop', 'paused'],
        ],
    ];

    /**
     * SDK method name to endpoint name, for batch().
     *
     * @var array<string, string>
     */
    public const METHODS = [
        'checkProduct' => 'check_product',
        'deleteProduct' => 'delete_product',
        'fetchOffers' => 'fetch_offers',
        'getAccount' => 'get_account',
        'getAsinHistory' => 'get_asin_history',
        'getOffers' => 'get_offers',
        'getProduct' => 'get_product',
        'listProducts' => 'list_products',
        'searchAmazon' => 'search_amazon',
        'trackProduct' => 'track_product',
        'updateProduct' => 'update_product',
    ];
}
