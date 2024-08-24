<?php

namespace App\Services\Shopify;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Psr\Http\Client\ClientExceptionInterface;
use Shopify\Auth\FileSessionStorage;
use Shopify\Auth\Session;
use Shopify\Clients\Rest;
use Shopify\Context;
use Shopify\Exception\MissingArgumentException;
use Shopify\Exception\UninitializedContextException;
use Shopify\Rest\Admin2024_07\InventoryLevel;
use Shopify\Rest\Admin2024_07\Location;

class ShopifyApiService
{
    private string $apiKey;
    private string $apiPassword;
    private string $storeName;

    private Rest $client;

    private Session $session;

    /**
     * @throws MissingArgumentException
     */
    public function __construct()
    {
        $this->apiKey = config('services.shopify.api_key');
        $this->apiPassword = config('services.shopify.api_password');
        $this->storeName = config('services.shopify.store_name');

        Context::initialize(
            apiKey: $this->apiKey,
            apiSecretKey: $this->apiPassword,
            scopes: ['read_products', 'write_products', 'read_inventory', 'write_inventory'],
            hostName: $this->storeName,
            sessionStorage: new FileSessionStorage(),
            apiVersion: '2024-07',
            isEmbeddedApp: false,
            isPrivateApp: true
        );

        $this->session = new Session(
            id: "offline_$this->storeName",
            shop: $this->storeName,
            isOnline: false,
            state: 'active',
        );

        $this->session->setAccessToken($this->apiPassword);

        $this->client = new Rest(
            $this->session->getShop(),
            $this->session->getAccessToken()
        );
    }


    public function getProducts(?array $query = ['limit' => 50]): array
    {
        try {
            $response = $this->client->get(path: 'products', query: $query, tries: 3);

            if ($response->getStatusCode() != 200) {
                Log::error('Failed to fetch products from Shopify: Invalid response code ' . $response->getStatusCode());
                return [
                    'data' => [],
                    'page_info' => null,
                ];
            }

            $products = $response->getDecodedBody()['products'];
            $pageInfo = $response->getPageInfo();

            return [
                'data' => $products,
                'page_info' => $pageInfo,
            ];

        } catch (ClientExceptionInterface|UninitializedContextException $e) {
            Log::error('Failed to fetch products from Shopify: ' . $e->getMessage());
            return [
                'data' => [],
                'page_info' => null,
            ];
        } catch (\JsonException $e) {
            Log::error('Failed to decode response from Shopify: ' . $e->getMessage());
            return [
                'data' => [],
                'page_info' => null,
            ];
        }
    }


    /**
     * @throws ConnectionException
     */
    public function createProduct($productData): PromiseInterface|Response
    {
        return Http::withBasicAuth($this->apiKey, $this->apiPassword)
            ->retry(3, 1000)
            ->post("https://$this->storeName.myshopify.com/admin/products.json", $productData);
    }


    public function getInventoryLocations(): Location|array
    {
        try {
            return Location::all($this->session);
        } catch (\Exception $e) {
            Log::error('Error fetching inventory locations: ' . $e->getMessage());
            return [];
        }

    }


    public function updateInventoryLevel($locationId, $inventoryItemId, $quantity, $productId): ?array
    {
        try {
            $inventoryLevel = new InventoryLevel($this->session);

            return $inventoryLevel->adjust(
                [],
                [
                    'location_id' => $locationId,
                    'inventory_item_id' => $inventoryItemId,
                    'available_adjustment' => $quantity,
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error updating inventory for product ID {$productId}, item ID {$inventoryItemId}: " . $e->getMessage());
            return [];
        }
    }

    private function logFailedProduct($productId, $inventoryItemId, $locationId, $error): void
    {
        $logData = [
            'product_id' => $productId,
            'inventory_item_id' => $inventoryItemId,
            'location_id' => $locationId,
            'error' => $error
        ];
        Storage::append('failed_products.log', json_encode($logData));
    }
}
