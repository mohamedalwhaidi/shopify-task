<?php

namespace App\Jobs;

use App\Services\Shopify\Facade\ShopifyApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateShopifyInventoryJob implements ShouldQueue
{
    use Queueable;

    private const STOCK_QUANTITY = 50;

    public function __construct(private readonly array $products, private readonly array $locations)
    {
    }

    public function handle(): void
    {
        foreach ($this->products as $product) {
            foreach ($product['variants'] as $variant) {
                foreach ($this->locations as $location) {
                    try {
                        ShopifyApi::updateInventoryLevel(
                            locationId: $location->id,
                            inventoryItemId: $variant['inventory_item_id'],
                            quantity: self::STOCK_QUANTITY,
                            productId: $product['id']
                        );
                        Log::info("Inventory updated for product ID {$product['id']} at location {$location->id}");
                    } catch (\Exception $e) {
                        Log::error("Error updating inventory for product ID {$product['id']} at location {$location->id}: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
