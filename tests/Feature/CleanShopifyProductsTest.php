<?php

namespace Tests\Feature;

use App\Services\ShopifyApiService;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;


class CleanShopifyProductsTest extends TestCase
{
    public function test_clean_shopify_products()
    {
        $mockShopifyApiService = Mockery::mock(ShopifyApiService::class);
        $this->app->instance(ShopifyApiService::class, $mockShopifyApiService);

        $mockProducts = [
            [
                'id' => 1,
                'title' => 'Product 1',
                'body_html' => null,
                'vendor' => 'Vendor 1',
                'product_type' => 'Type 1',
                'variants' => [
                    ['id' => 101, 'inventory_item_id' => 1001, 'title' => 'Variant 1']
                ]
            ],
            [
                'id' => 2,
                'title' => 'Product 2',
                'body_html' => 'Description 2',
                'vendor' => 'Vendor 2',
                'product_type' => 'Type 2',
                'variants' => [
                    ['id' => 102, 'inventory_item_id' => 1002, 'title' => 'Variant 2']
                ]
            ],
        ];

        $expectedCleanedProducts = [
            [
                'id' => 1,
                'title' => 'Product 1 - nullable',
                'vendor' => 'Vendor 1',
                'product_type' => 'Type 1',
                'variants' => [
                    ['id' => 101, 'inventory_item_id' => 1001, 'title' => 'Variant 1']
                ]
            ],
            [
                'id' => 2,
                'title' => 'Product 2',
                'body_html' => 'Description 2',
                'vendor' => 'Vendor 2',
                'product_type' => 'Type 2',
                'variants' => [
                    ['id' => 102, 'inventory_item_id' => 1002, 'title' => 'Variant 2']
                ]
            ],
        ];

        $mockShopifyApiService->shouldReceive('getProducts')
            ->once()
            ->andReturn($mockProducts);


        Artisan::call('app:clean-shopify-products');

        $this->assertEquals("The modified product is: " . json_encode($expectedCleanedProducts[0]) . "\n", Artisan::output());
    }
}
