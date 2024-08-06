<?php

namespace App\Services\Shopify;

use Illuminate\Support\Collection;

class ShopifyApiFake extends ShopifyApiService
{
    private Collection $products;
    private Collection $locations;

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

    public function addLocation(array $location): self
    {
        $this->locations->push($location);
        return $this;
    }

    public function getProducts(int $page = null, int $limit = null): array
    {
        return $this->products->all();
    }

    public function getInventoryLocations()
    {
        return $this->locations->all();
    }

    public function updateInventoryLevel($locationId, $inventoryItemId, $quantity, $productId): true
    {
        // Simulate a successful update
        return true;
    }
}
