<?php

namespace App\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ShopifyApiService
{
    private string $apiKey;
    private string $apiPassword;
    private string $storeName;

    public function __construct()
    {
        $this->apiKey = config('services.shopify.api_key');
        $this->apiPassword = config('services.shopify.api_password');
        $this->storeName = config('services.shopify.store_name');
    }

    /**
     * @throws ConnectionException
     */
    public function getProducts(?int $page = null, ?int $limit = null)
    {
        // TODO: Fix pagination and use it to get paginated products
        $body = [
               /*
               * Should be page_info instead of page and i need to get page_info, but i don't have the time to search more.

               * page_info: A unique ID used to access a certain page of results.
               * The page_info parameter can't be modified and must be used exactly as it appears in the link header URL.
               */
//                'page' => $page,
//                'page_info' => $page,
//                'limit' => $limit,
        ];

        $body = array_filter($body);

        $response = Http::withBasicAuth($this->apiKey, $this->apiPassword)
            ->get("https://{$this->storeName}.myshopify.com/admin/products.json", $body);

        if ($response->successful()) {
            return $response->json()['products'];
        }

        Log::error('Failed to fetch products from Shopify');
        return [];
    }

    /**
     * @throws ConnectionException
     */
    public function getInventoryLocations()
    {
        $response = Http::withBasicAuth($this->apiKey, $this->apiPassword)
            ->get("https://{$this->storeName}.myshopify.com/admin/locations.json");

        if ($response->successful() && !empty($response->json()['locations'])) {
            return $response->json()['locations'];
        }

        Log::error('Failed to fetch inventory locations from Shopify');
        return [];
    }

    /**
     * @throws ConnectionException
     */
    public function updateInventoryLevel($locationId, $inventoryItemId, $quantity, $productId): PromiseInterface|Response
    {
        $response = Http::withBasicAuth($this->apiKey, $this->apiPassword)
            ->post("https://{$this->storeName}.myshopify.com/admin/inventory_levels/set.json", [
                'location_id' => $locationId,
                'inventory_item_id' => $inventoryItemId,
                'available' => $quantity,
            ]);

        if ($response->successful()) {
            Log::info("Inventory level updated for product ID {$productId}, item ID {$inventoryItemId} to {$quantity}");
        } else {
            Log::error("Failed to update inventory level for product ID {$productId}, item ID {$inventoryItemId}. Response: " . $response->body());
            $this->logFailedProduct($productId, $inventoryItemId, $response->body());
        }

        return $response;
    }

    /**
     * @throws ConnectionException
     */
    public function createProduct($productData): PromiseInterface|Response
    {
        return Http::withBasicAuth($this->apiKey, $this->apiPassword)
            ->retry(3, 1000)
            ->post("https://{$this->storeName}.myshopify.com/admin/products.json", $productData);
    }

    private function logFailedProduct($productId, $inventoryItemId, $error): void
    {
        $logData = [
            'product_id' => $productId,
            'inventory_item_id' => $inventoryItemId,
            'error' => $error
        ];
        Storage::append('failed_products.log', json_encode($logData));
    }
}
