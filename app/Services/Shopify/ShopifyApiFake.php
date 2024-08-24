<?php

namespace App\Services\Shopify;

use Exception;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

class ShopifyApiFake extends ShopifyApiService
{
    private Collection $products;
    private Collection $locations;
    private ?Exception $exception = null;

    public function __construct()
    {
        parent::__construct();
        $this->products = collect();
        $this->locations = collect();
    }

    public function addProduct(array $product): self
    {
        $this->products->push($product);
        return $this;
    }

    /**
     * @throws Exception
     */
    public function createProduct($productData): Response
    {
        if ($this->exception) {
            throw $this->exception;
        }

        $this->products->push($productData);

        $guzzleResponse = new GuzzleResponse(
            201,
            [],
            json_encode([
                'product' => [
                    'id' => rand(1000, 9999),
                    'title' => $productData['product']['title'],
                    'created_at' => now()->toDateTimeString(),
                ]
            ])
        );

        return new Response($guzzleResponse);
    }

    public function addLocation(array $location): self
    {
        $this->locations->push($location);
        return $this;
    }

    public function setException(Exception $exception): self
    {
        $this->exception = $exception;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function getProducts(?array $query = []): array
    {
        if ($this->exception) {
            throw $this->exception;
        }

        return [
            'data' => $this->products->all(),
            'page_info' => '',
        ];
    }

    /**
     * @throws Exception
     */
    public function getInventoryLocations()
    {
        if ($this->exception) {
            throw $this->exception;
        }

        return $this->locations->all();
    }

    /**
     * @throws Exception
     */
    public function updateInventoryLevel($locationId, $inventoryItemId, $quantity, $productId): bool
    {
        if ($this->exception) {
            throw $this->exception;
        }

        // Simulate a successful update
        return true;
    }
}
