<?php

namespace Tests\Feature;

use App\Jobs\CreateShopifyProductsJob;
use App\Services\Shopify\Facade\ShopifyApi;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use Tests\TestCase;

class CreateShopifyProductsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_the_job_correctly()
    {
        ShopifyApi::fake();
        Queue::fake();

        $this->artisan('app:dispatch-create-shopify-products-job')
            ->assertExitCode(0);

        Queue::assertPushed(CreateShopifyProductsJob::class);
    }

    public function test_it_creates_products_successfully()
    {
        ShopifyApi::fake();

        Storage::fake('local');

        $path = Storage::path('products_export.csv');
        $csv = Writer::createFromPath($path, 'w+');
        $csv->insertOne([
            'Title', 'Body (HTML)', 'Vendor', 'Type', 'Handle', 'Tags', 'Published',
            'Variant Price', 'Variant SKU', 'Variant Inventory Tracker',
            'Variant Inventory Policy', 'Variant Fulfillment Service', 'Variant Compare At Price',
            'Variant Requires Shipping', 'Variant Taxable', 'Variant Barcode',
            'Variant Grams', 'Variant Weight Unit', 'Variant Tax Code', 'Cost per item',
            'Option1 Name', 'Option1 Value', 'Option2 Name', 'Option2 Value',
            'Option3 Name', 'Option3 Value', 'Image Src', 'Image Position',
            'Image Alt Text', 'SEO Title', 'SEO Description', 'Google Shopping / Google Product Category',
            'Google Shopping / Gender', 'Google Shopping / Age Group', 'Google Shopping / MPN',
            'Google Shopping / AdWords Grouping', 'Google Shopping / AdWords Labels',
            'Google Shopping / Condition', 'Google Shopping / Custom Product',
            'Google Shopping / Custom Label 0', 'Google Shopping / Custom Label 1',
            'Google Shopping / Custom Label 2', 'Google Shopping / Custom Label 3', 'Google Shopping / Custom Label 4'
        ]);
        $csv->insertOne([
            'Test Product', 'Description', 'Vendor', 'Type', 'handle', 'Tags', 'true',
            '10.00', 'SKU123', 'shopify', 'deny', 'manual', '15.00', 'true',
            'true', '123456789012', '500', 'g', 'HTS', '5.00',
            'Size', 'M', 'Color', 'Red', 'Material', 'Cotton', 'http://example.com/image.jpg',
            '1', 'Alt text', 'SEO Title Example', 'SEO Description Example',
            'Apparel & Accessories > Clothing > Shirts & Tops', 'Unisex', 'Adult',
            'MPN12345', 'AdWords Grouping Example', 'AdWords Label Example', 'New',
            'false', 'Custom Label 0', 'Custom Label 1', 'Custom Label 2', 'Custom Label 3', 'Custom Label 4'
        ]);

        $records = [
            [
                'Title' => 'Test Product', 'Body (HTML)' => 'Description', 'Vendor' => 'Vendor',
                'Type' => 'Type', 'Handle' => 'handle', 'Tags' => 'Tags', 'Published' => 'true',
                'Variant Price' => '10.00', 'Variant SKU' => 'SKU123', 'Variant Inventory Tracker' => 'shopify',
                'Variant Inventory Policy' => 'deny', 'Variant Fulfillment Service' => 'manual',
                'Variant Compare At Price' => '15.00', 'Variant Requires Shipping' => 'true',
                'Variant Taxable' => 'true', 'Variant Barcode' => '123456789012', 'Variant Grams' => '500',
                'Variant Weight Unit' => 'g', 'Variant Tax Code' => 'HTS', 'Cost per item' => '5.00',
                'Option1 Name' => 'Size', 'Option1 Value' => 'M', 'Option2 Name' => 'Color',
                'Option2 Value' => 'Red', 'Option3 Name' => 'Material', 'Option3 Value' => 'Cotton',
                'Image Src' => 'http://example.com/image.jpg', 'Image Position' => '1',
                'Image Alt Text' => 'Alt text', 'SEO Title' => 'SEO Title Example',
                'SEO Description' => 'SEO Description Example',
                'Google Shopping / Google Product Category' => 'Apparel & Accessories > Clothing > Shirts & Tops',
                'Google Shopping / Gender' => 'Unisex', 'Google Shopping / Age Group' => 'Adult',
                'Google Shopping / MPN' => 'MPN12345', 'Google Shopping / AdWords Grouping' => 'AdWords Grouping Example',
                'Google Shopping / AdWords Labels' => 'AdWords Label Example',
                'Google Shopping / Condition' => 'New', 'Google Shopping / Custom Product' => 'false',
                'Google Shopping / Custom Label 0' => 'Custom Label 0', 'Google Shopping / Custom Label 1' => 'Custom Label 1',
                'Google Shopping / Custom Label 2' => 'Custom Label 2', 'Google Shopping / Custom Label 3' => 'Custom Label 3',
                'Google Shopping / Custom Label 4' => 'Custom Label 4'
            ],
        ];

        $job = new CreateShopifyProductsJob($records);
        $job->handle();

        $response = ShopifyApi::getProducts();

        $this->assertCount(1, $response['data']);
        $this->assertEquals('Test Product', $response['data'][0]['product']['title']);
    }

    public function test_it_logs_errors_when_product_creation_fails()
    {
        ShopifyApi::fake()->setException(new Exception('API Failure'));

        Storage::fake('local');
        $path = Storage::path('products_export.csv');
        $csv = Writer::createFromPath($path, 'w+');
        $csv->insertOne([
            'Title', 'Body (HTML)', 'Vendor', 'Type', 'Handle', 'Tags', 'Published',
            'Variant Price', 'Variant SKU', 'Variant Inventory Tracker',
            'Variant Inventory Policy', 'Variant Fulfillment Service', 'Variant Compare At Price',
            'Variant Requires Shipping', 'Variant Taxable', 'Variant Barcode',
            'Variant Grams', 'Variant Weight Unit', 'Variant Tax Code', 'Cost per item',
            'Option1 Name', 'Option1 Value', 'Option2 Name', 'Option2 Value',
            'Option3 Name', 'Option3 Value', 'Image Src', 'Image Position',
            'Image Alt Text', 'SEO Title', 'SEO Description', 'Google Shopping / Google Product Category',
            'Google Shopping / Gender', 'Google Shopping / Age Group', 'Google Shopping / MPN',
            'Google Shopping / AdWords Grouping', 'Google Shopping / AdWords Labels',
            'Google Shopping / Condition', 'Google Shopping / Custom Product',
            'Google Shopping / Custom Label 0', 'Google Shopping / Custom Label 1',
            'Google Shopping / Custom Label 2', 'Google Shopping / Custom Label 3', 'Google Shopping / Custom Label 4'
        ]);
        $csv->insertOne([
            'Test Product', 'Description', 'Vendor', 'Type', 'handle', 'Tags', 'true',
            '10.00', 'SKU123', 'shopify', 'deny', 'manual', '15.00', 'true',
            'true', '123456789012', '500', 'g', 'HTS', '5.00',
            'Size', 'M', 'Color', 'Red', 'Material', 'Cotton', 'http://example.com/image.jpg',
            '1', 'Alt text', 'SEO Title Example', 'SEO Description Example',
            'Apparel & Accessories > Clothing > Shirts & Tops', 'Unisex', 'Adult',
            'MPN12345', 'AdWords Grouping Example', 'AdWords Label Example', 'New',
            'false', 'Custom Label 0', 'Custom Label 1', 'Custom Label 2', 'Custom Label 3', 'Custom Label 4'
        ]);

        Log::shouldReceive('error')->once()->with('Error creating product: Test Product - API Failure');

        $records = [
            [
                'Title' => 'Test Product', 'Body (HTML)' => 'Description', 'Vendor' => 'Vendor',
                'Type' => 'Type', 'Handle' => 'handle', 'Tags' => 'Tags', 'Published' => 'true',
                'Variant Price' => '10.00', 'Variant SKU' => 'SKU123', 'Variant Inventory Tracker' => 'shopify',
                'Variant Inventory Policy' => 'deny', 'Variant Fulfillment Service' => 'manual',
                'Variant Compare At Price' => '15.00', 'Variant Requires Shipping' => 'true',
                'Variant Taxable' => 'true', 'Variant Barcode' => '123456789012', 'Variant Grams' => '500',
                'Variant Weight Unit' => 'g', 'Variant Tax Code' => 'HTS', 'Cost per item' => '5.00',
                'Option1 Name' => 'Size', 'Option1 Value' => 'M', 'Option2 Name' => 'Color',
                'Option2 Value' => 'Red', 'Option3 Name' => 'Material', 'Option3 Value' => 'Cotton',
                'Image Src' => 'http://example.com/image.jpg', 'Image Position' => '1',
                'Image Alt Text' => 'Alt text', 'SEO Title' => 'SEO Title Example',
                'SEO Description' => 'SEO Description Example',
                'Google Shopping / Google Product Category' => 'Apparel & Accessories > Clothing > Shirts & Tops',
                'Google Shopping / Gender' => 'Unisex', 'Google Shopping / Age Group' => 'Adult',
                'Google Shopping / MPN' => 'MPN12345', 'Google Shopping / AdWords Grouping' => 'AdWords Grouping Example',
                'Google Shopping / AdWords Labels' => 'AdWords Label Example',
                'Google Shopping / Condition' => 'New', 'Google Shopping / Custom Product' => 'false',
                'Google Shopping / Custom Label 0' => 'Custom Label 0', 'Google Shopping / Custom Label 1' => 'Custom Label 1',
                'Google Shopping / Custom Label 2' => 'Custom Label 2', 'Google Shopping / Custom Label 3' => 'Custom Label 3',
                'Google Shopping / Custom Label 4' => 'Custom Label 4'
            ],
        ];

        $job = new CreateShopifyProductsJob($records);
        $job->handle();
    }
}
