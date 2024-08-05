<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopifyProductCreationTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_create_products_from_csv(): void
    {
        // Mock the Shopify API response
        Http::fake([
            'https://your_api_key:your_api_password@your_store_name.myshopify.com/admin/products.json' => Http::response(['product' => ['id' => 12345]], 200),
        ]);

        // Store the provided CSV file in the storage
        Storage::fake('local');
        Storage::put('products_export.csv', file_get_contents(base_path('tests/Feature/products_export.csv')));

        // Call the route that handles product creation
        $response = $this->get('/create-products');

        // Assert that the API was called with the correct data
        Http::assertSent(function ($request) {
            return $request->hasHeader('Content-Type', 'application/json') &&
                $request['product']['title'] === 'Sample Product' && // Adjust based on actual CSV content
                $request['product']['body_html'] === 'This is a sample product' && // Adjust based on actual CSV content
                $request['product']['vendor'] === 'Vendor' && // Adjust based on actual CSV content
                $request['product']['product_type'] === 'Type' && // Adjust based on actual CSV content
                $request['product']['variants'][0]['price'] === '9.99' && // Adjust based on actual CSV content
                $request['product']['variants'][0]['inventory_quantity'] === 50;
        });

        // Assert the response
        $response->assertStatus(200);
    }
}
