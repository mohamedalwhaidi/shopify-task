<?php

namespace App\Jobs;

use App\Services\Shopify\Facade\ShopifyApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class UpdateShopifyInventoryJob implements ShouldQueue
{
    use Queueable;

    private const STOCK_QUANTITY = 50;


    public function handle(): void
    {
        $locations = ShopifyApi::getInventoryLocations();

        if (empty($locations)) {
            Log::error('Failed to retrieve inventory locations');
            return;
        }

        // TODO: user $page and $limit to fetch products
        $products = ShopifyApi::getProducts();

        if (empty($products)) {
            return;
        }

        foreach ($products as $product) {
            foreach ($product['variants'] as $variant) {
                foreach ($locations as $location) {
                    try {
                        ShopifyApi::updateInventoryLevel(
                            locationId: $location['id'],
                            inventoryItemId: $variant['inventory_item_id'],
                            quantity: self::STOCK_QUANTITY,
                            productId: $product['id'],
                        );
                    } catch (ConnectionException $e) {
                        Log::error($e->getMessage());
                        continue;
                    }
                }
            }
        }
    }
}
