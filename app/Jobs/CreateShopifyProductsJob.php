<?php

namespace App\Jobs;

use App\Services\Shopify\Facade\ShopifyApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\UnavailableStream;
use League\Csv\Writer;

class CreateShopifyProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $uploadedProductsPath = 'uploaded_products.csv';

    private string $failedProductsPath = 'failed_products.csv';

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->processCsvInChunks(function ($chunk) {

                foreach ($chunk as $record) {
                    $productData = $this->prepareProductData($record);

                    $productData = $this->removeEmptyValues($productData);

                    try {
                        $response = ShopifyApi::createProduct($productData);

                        if ($response->created()) {
                            Log::info("Product created successfully: " . $productData['product']['title']);
                            $this->logProcessedRecord($record, $this->uploadedProductsPath);
                        } else {
                            Log::error("Failed to create product: " . $productData['product']['title']);
                            $this->logProcessedRecord($record, $this->failedProductsPath);
                        }
                    } catch (\Exception $e) {
                        Log::error("Error creating product: " . $productData['product']['title'] . " - " . $e->getMessage());
                        $this->logProcessedRecord($record, $this->failedProductsPath);
                    }
                }
            });
        } catch (UnavailableStream $e) {
            Log::error("Error reading CSV file: {$e->getMessage()}");
        } catch (Exception $e) {
            Log::error("Error processing CSV file: {$e->getMessage()}");
        }
    }

    /**
     * Get records in chunks
     * @throws UnavailableStream
     * @throws Exception
     */
    private function processCsvInChunks($callback): void
    {
        $csv = Reader::createFromPath(Storage::path('products_export.csv'));
        $csv->setHeaderOffset(0);

        $stmt = (new Statement());
        $records = $stmt->process($csv);
        $records = collect($records);

        $records->chunk(50)->each($callback);
    }

    /**
     * Prepare product data from record
     */
    private function prepareProductData($record): array
    {
        return [
            'product' => [
                'title' => $record['Title'],
                'body_html' => $record['Body (HTML)'],
                'vendor' => $record['Vendor'],
                'product_type' => $record['Type'],
                'handle' => $record['Handle'],
                'tags' => $record['Tags'],
                'published' => filter_var($record['Published'], FILTER_VALIDATE_BOOLEAN),
                'variants' => [
                    [
                        'price' => $record['Variant Price'],
                        'sku' => $record['Variant SKU'],
                        'inventory_management' => $record['Variant Inventory Tracker'],
                        'inventory_policy' => $record['Variant Inventory Policy'],
                        'fulfillment_service' => $record['Variant Fulfillment Service'],
                        'inventory_quantity' => 0,
                        'compare_at_price' => $record['Variant Compare At Price'],
                        'requires_shipping' => filter_var($record['Variant Requires Shipping'], FILTER_VALIDATE_BOOLEAN),
                        'taxable' => filter_var($record['Variant Taxable'], FILTER_VALIDATE_BOOLEAN),
                        'barcode' => $record['Variant Barcode'],
                        'grams' => (int)$record['Variant Grams'],
                        'weight_unit' => $record['Variant Weight Unit'],
                        'tax_code' => $record['Variant Tax Code'],
                        'cost' => $record['Cost per item'],
                        'option1' => $record['Option1 Value'],
                        'option2' => $record['Option2 Value'],
                        'option3' => $record['Option3 Value']
                    ]
                ],
                'options' => [
                    [
                        'name' => $record['Option1 Name'],
                        'values' => [$record['Option1 Value']]
                    ],
                    [
                        'name' => $record['Option2 Name'],
                        'values' => [$record['Option2 Value']]
                    ],
                    [
                        'name' => $record['Option3 Name'],
                        'values' => [$record['Option3 Value']]
                    ]
                ],
                'images' => [
                    [
                        'src' => $record['Image Src'],
                        'position' => (int)$record['Image Position'],
                        'alt' => $record['Image Alt Text']
                    ]
                ],
                'metafields' => [
                    [
                        'namespace' => 'seo',
                        'key' => 'title',
                        'value' => $record['SEO Title'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'seo',
                        'key' => 'description',
                        'value' => $record['SEO Description'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'google_product_category',
                        'value' => $record['Google Shopping / Google Product Category'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'gender',
                        'value' => $record['Google Shopping / Gender'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'age_group',
                        'value' => $record['Google Shopping / Age Group'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'mpn',
                        'value' => $record['Google Shopping / MPN'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'adwords_grouping',
                        'value' => $record['Google Shopping / AdWords Grouping'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'adwords_labels',
                        'value' => $record['Google Shopping / AdWords Labels'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'condition',
                        'value' => $record['Google Shopping / Condition'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'custom_product',
                        'value' => filter_var($record['Google Shopping / Custom Product'], FILTER_VALIDATE_BOOLEAN),
                        'type' => 'boolean'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'custom_label_0',
                        'value' => $record['Google Shopping / Custom Label 0'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'custom_label_1',
                        'value' => $record['Google Shopping / Custom Label 1'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'custom_label_2',
                        'value' => $record['Google Shopping / Custom Label 2'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'custom_label_3',
                        'value' => $record['Google Shopping / Custom Label 3'],
                        'type' => 'string'
                    ],
                    [
                        'namespace' => 'google_shopping',
                        'key' => 'custom_label_4',
                        'value' => $record['Google Shopping / Custom Label 4'],
                        'type' => 'string'
                    ]
                ]
            ]
        ];
    }

    /**
     * Remove empty values from product data
     * @param array $data
     * @return array
     */
    private function removeEmptyValues(array $data): array
    {
        $filterData = array_filter($data, function ($value, $key) {
            if (is_array($value)) {
                $value = $this->removeEmptyValues($value);
                return !empty($value);
            }
            return !is_null($value) && $value !== '' && $value !== 'N/A' && $value !== '-';
        }, ARRAY_FILTER_USE_BOTH);

        if (isset($filterData['product']['options'])) {
            $filterData['product']['options'] = array_filter($filterData['product']['options'], function ($option) {
                return !empty($option['name']) && !empty($option['values']);
            });
        }
        return $filterData;
    }

    /**
     * @throws UnavailableStream
     * @throws CannotInsertRecord
     * @throws Exception
     */
    private function logProcessedRecord($record, $filePath): void
    {
        $processedRecordsPath = Storage::path($filePath);
        $csv = Writer::createFromPath($processedRecordsPath, 'a+');
        $csv->insertOne($record);
    }
}
