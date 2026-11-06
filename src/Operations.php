<?php

declare(strict_types=1);

namespace Asin;

// Generated from the Asin OpenAPI document. Do not edit by hand.

/**
 * One method per documented Asin endpoint. Used by {@see Client}.
 */
trait Operations
{
    /**
     * @param array<string, mixed> $params
     */
    abstract protected function callOperation(string $name, array $params, ?RequestOptions $options): JsonResponse|BinaryResponse;

    /**
     * Check the price now. Asks for a check right away (1 credit), at most once per 5 minutes; earlier calls return the product unchanged. A paused product has to be resumed first. The check runs in the background; with wait=true the answer waits for it. Read last_e…
     *
     * `POST /products/{id}/check`
     *
     * @param array{id: string, wait?: bool} $params
     *   - id (required): product_id returned when the product was added
     *   - wait: Answer once the check is done (up to 45 seconds)
     */
    public function checkProduct(array $params, ?RequestOptions $options = null): JsonResponse
    {
        $response = $this->callOperation('check_product', $params, $options);
        \assert($response instanceof JsonResponse);

        return $response;
    }

    /**
     * Stop tracking a product. Removes the product and its price history.
     *
     * `DELETE /products/{id}`
     *
     * @param array{id: string} $params
     *   - id (required): product_id returned when the product was added
     */
    public function deleteProduct(array $params, ?RequestOptions $options = null): JsonResponse
    {
        $response = $this->callOperation('delete_product', $params, $options);
        \assert($response instanceof JsonResponse);

        return $response;
    }

    /**
     * Read every offer from Amazon. Reads the offers on Amazon for 1 credit, refunded if it doesn't answer. A read made in the last 3 hours, by anyone, is returned instead, for free (charged: false). The answer is the same as GET /offers.
     *
     * `POST /offers`
     *
     * @param array{asin: string, marketplace?: string, refresh?: bool} $params
     *   - asin (required): 10-character ASIN
     *   - marketplace: Amazon store, e.g. com for amazon.com
     *   - refresh: Read Amazon again unless the last read is under 5 minutes old (1 credit)
     */
    public function fetchOffers(array $params, ?RequestOptions $options = null): JsonResponse
    {
        $response = $this->callOperation('fetch_offers', $params, $options);
        \assert($response instanceof JsonResponse);

        return $response;
    }

    /**
     * Plan and remaining credits. Your plan, the credits left (monthly allowance and packs), how many products you track and the credits they use per month.
     *
     * `GET /account`
     *
     * @param array{} $params
     */
    public function getAccount(array $params = [], ?RequestOptions $options = null): JsonResponse
    {
        $response = $this->callOperation('get_account', $params, $options);
        \assert($response instanceof JsonResponse);

        return $response;
    }

    /**
     * Price history of any ASIN. Every price Asin read for an ASIN on a marketplace over the period, whoever tracks it, oldest first, with the lowest, highest and average of those prices. A history of more than 5000 points is thinned evenly, keeping its first and last poi…
     *
     * `GET /history`
     *
     * @param array{asin: string, marketplace?: string, days?: int} $params
     *   - asin (required): 10-character ASIN
     *   - marketplace: Amazon store, e.g. com for amazon.com
     *   - days: History period in days
     */
    public function getAsinHistory(array $params, ?RequestOptions $options = null): JsonResponse
    {
        $response = $this->callOperation('get_asin_history', $params, $options);
        \assert($response instanceof JsonResponse);

        return $response;
    }

    /**
     * Offers already known. Who sells the product and at what price, as last read, cheapest first, with Amazon's own price and the cheapest new and used offers (used is only known for Amazon's own outlet). Free: this never reads Amazon or spends a credit. When nobody…
     *
     * `GET /offers`
     *
     * @param array{asin: string, marketplace?: string} $params
     *   - asin (required): 10-character ASIN
     *   - marketplace: Amazon store, e.g. com for amazon.com
     */
    public function getOffers(array $params, ?RequestOptions $options = null): JsonResponse
    {
        $response = $this->callOperation('get_offers', $params, $options);
        \assert($response instanceof JsonResponse);

        return $response;
    }

    /**
     * Get a product and its price history. The product's latest state and every price read of the period, oldest first. The history covers every read of this ASIN on this marketplace, including those made before you added it, so a product added today can come with months of prices;…
     *
     * `GET /products/{id}`
     *
     * @param array{id: string, days?: int} $params
     *   - id (required): product_id returned when the product was added
     *   - days: History period in days
     */
    public function getProduct(array $params, ?RequestOptions $options = null): JsonResponse
    {
        $response = $this->callOperation('get_product', $params, $options);
        \assert($response instanceof JsonResponse);

        return $response;
    }

    /**
     * List tracked products. Your watchlist, newest first, with each product's latest state and its recent prices (trend). Filter with asin (and marketplace) to know if a product is tracked, or with filter to get the same lists as the app's tabs.
     *
     * `GET /products`
     *
     * @param array{asin?: string, marketplace?: string, filter?: string, page?: int, limit?: int} $params
     *   - asin: Only this ASIN
     *   - marketplace: Only this Amazon store
     *   - filter: Only the products of one of the app's watchlist filters. all: Every tracked product (default). drops: Cheaper now than when the product was added. target: At o…
     *   - page: Page number
     *   - limit: Products per page
     */
    public function listProducts(array $params = [], ?RequestOptions $options = null): JsonResponse
    {
        $response = $this->callOperation('list_products', $params, $options);
        \assert($response instanceof JsonResponse);

        return $response;
    }

    /**
     * Search Amazon. Amazon search results (up to 60, sponsored included), to find the ASIN of a product. Free, limited to 30 searches per hour.
     *
     * `GET /search`
     *
     * @param array{query: string, marketplace?: string} $params
     *   - query (required): Keywords
     *   - marketplace: Amazon store, e.g. com for amazon.com
     */
    public function searchAmazon(array $params, ?RequestOptions $options = null): JsonResponse
    {
        $response = $this->callOperation('search_amazon', $params, $options);
        \assert($response instanceof JsonResponse);

        return $response;
    }

    /**
     * Track a product. Adds an ASIN, an Amazon link or a short link (amzn.to, a.co…) to your watchlist. The first check runs in the background within seconds (checking is true until then): poll GET /products/{id}, or send wait: true to get the answer once it's d…
     *
     * `POST /products`
     *
     * @param array{product: string, marketplace?: string, target_price?: int|float, interval_minutes?: int, wait?: bool} $params
     *   - product (required): ASIN (B09DFCB66S) or Amazon product link
     *   - marketplace: Amazon store, e.g. com for amazon.com. A product link sets it on its own.
     *   - target_price: Email the owner when the price goes at or below this amount. null removes it.
     *   - interval_minutes: Minutes between two checks (1440 = daily). Checks faster than daily need a plan or a credit pack: every 60 minutes with a pack or Starter, 30 with Pro, 5 with…
     *   - wait: Answer once the first check is done (up to 45 seconds)
     */
    public function trackProduct(array $params, ?RequestOptions $options = null): JsonResponse
    {
        $response = $this->callOperation('track_product', $params, $options);
        \assert($response instanceof JsonResponse);

        return $response;
    }

    /**
     * Change alerts and checks. Target price, drop alerts, check interval, or pause. Send only what changes.
     *
     * `PATCH /products/{id}`
     *
     * @param array{id: string, target_price?: int|float, interval_minutes?: int, alert_on_drop?: bool, paused?: bool} $params
     *   - id (required): product_id returned when the product was added
     *   - target_price: Email the owner when the price goes at or below this amount. null removes it.
     *   - interval_minutes: Minutes between two checks (1440 = daily). Checks faster than daily need a plan or a credit pack: every 60 minutes with a pack or Starter, 30 with Pro, 5 with…
     *   - alert_on_drop: Email when the price drops or the product is back in stock
     *   - paused: Stop checking (and charging) for now; false resumes
     */
    public function updateProduct(array $params, ?RequestOptions $options = null): JsonResponse
    {
        $response = $this->callOperation('update_product', $params, $options);
        \assert($response instanceof JsonResponse);

        return $response;
    }
}
