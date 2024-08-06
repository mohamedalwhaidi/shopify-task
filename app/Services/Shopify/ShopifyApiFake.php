<?php

namespace App\Services\Shopify;

use Exception;
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
    public function getProducts(int $page = null, int $limit = null): array
    {
        if ($this->exception) {
            throw $this->exception;
        }

        return $this->products->all();
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
