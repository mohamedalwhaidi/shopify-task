<?php

namespace App\Console\Commands;

use App\Services\ShopifyApiService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class CleanShopifyProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-shopify-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct(
        public ShopifyApiService $shopifyService
    )
    {
        parent::__construct();
    }

    public function handle(): void
    {
        /**
         * In real scenario, we would use pagination to get paginated products
         * and clean them in chunks
           but because the pagination is not work correctly, we will get all products
         * and we should use this code inside a job to avoid the timeout
         * but to print the result in terminal as you need in the task [Then console log the modified object as a string.]
         * we will use it here
         **/
        try {
            $products = $this->shopifyService->getProducts();
        } catch (ConnectionException $e) {
            $this->error($e->getMessage());
            return;
        }

        $products = collect($products);

        $products->chunk(50)->each(function ($chunk) {
            $chunk->each(function ($product) {
                $cleanedProduct = $this->cleanProduct($product);
                Log::info('Cleaned product: ' . json_encode($cleanedProduct));
            });
        });

        $this->info('Products cleaned and logged.');
    }

    private function cleanProduct(array $product): array
    {
        $hasNullable = $this->hasNullableValues($product);
        $cleanedProduct = $this->removeEmptyValues($product);

        if ($hasNullable) {
            $cleanedProduct['title'] .= ' - nullable';
            $this->info('The modified product is: ' . json_encode($cleanedProduct));
        }

        return $cleanedProduct;
    }

    private function hasNullableValues(array $data): bool
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->hasNullableValues($value)) {
                    return true;
                }
            } else {
                if ($this->isRemovableValue($value)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isRemovableValue($value): bool
    {
        return is_null($value) || $value === '' || $value === 'N/A' || $value === '-';
    }

    /**
     * Remove empty values from data
     * @param array $data
     * @return array
     */
    private function removeEmptyValues(array $data): array
    {
        return array_filter($data, function ($value) {
            if (is_array($value)) {
                $value = $this->removeEmptyValues($value);
                return !empty($value);
            }
            return !$this->isRemovableValue($value);
        });
    }

}
