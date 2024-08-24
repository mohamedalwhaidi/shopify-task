<?php

namespace App\Console\Commands;

use App\Services\Shopify\Facade\ShopifyApi;
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

    public function handle(): void
    {
        $query = ['limit' => 50];

        try {
            do {
                $response = ShopifyApi::getProducts($query);

                $products = $response['data'];
                $pageInfo = $response['page_info'];

                foreach ($products as $product) {
                    $cleanedProduct = $this->cleanProduct($product);
                    Log::info('Cleaned product: ' . json_encode($cleanedProduct));
                }

                if ($pageInfo && $pageInfo->hasNextPage()) {
                    $query = $pageInfo->getNextPageQuery();
                } else {
                    break;
                }

            } while (true);

        } catch (ConnectionException|\JsonException $e) {
            $this->error($e->getMessage());
            return;
        }
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
