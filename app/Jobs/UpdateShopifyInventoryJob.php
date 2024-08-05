<?php

namespace App\Jobs;

use App\Services\ShopifyApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class UpdateShopifyInventoryJob implements ShouldQueue
{
    use Queueable;

    private const STOCK_QUANTITY = 50;
    public ShopifyApiService $shopifyService;

    public function __construct()
    {
        $this->shopifyService = new ShopifyApiService();
    }


    /**
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $locations = $this->shopifyService->getInventoryLocations();

        if (empty($locations)) {
            Log::error('Failed to retrieve inventory locations');
            return;
        }

        // TODO: user $page and $limit to fetch products
        $products = $this->shopifyService->getProducts();

        if (empty($products)) {
            return;
        }

        foreach ($products as $product) {
            foreach ($product['variants'] as $variant) {
                foreach ($locations as $location) {
                    try {
                        $this->shopifyService->updateInventoryLevel($location['id'], $variant['inventory_item_id'], self::STOCK_QUANTITY, $product['id']);
                    } catch (ConnectionException $e) {
                        Log::error($e->getMessage());
                        continue;
                    }
                }
            }
        }
    }
}
