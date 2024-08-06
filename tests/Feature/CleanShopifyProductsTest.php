<?php

namespace Tests\Feature;

use App\Services\Shopify\Facade\ShopifyApi;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;


class CleanShopifyProductsTest extends TestCase
{
    public function test_clean_shopify_products()
    {
        ShopifyApi::fake()
            ->addProduct([
                'id' => 1,
                'title' => 'Product 1', // should be modified to 'Product 1 - nullable'
                'body_html' => null, // 'Description 1',
                'vendor' => 'Vendor 1',
                'product_type' => 'Type 1',
                'variants' => [
                    ['id' => 101, 'inventory_item_id' => 1001, 'title' => 'Variant 1']
                ]
            ])
            ->addProduct([
                'id' => 2,
                'title' => 'Product 2',
                'body_html' => 'Description 2',
                'vendor' => 'Vendor 2',
                'product_type' => 'Type 2',
                'variants' => [
                    ['id' => 102, 'inventory_item_id' => 1002, 'title' => 'Variant 2']
                ]
            ]);

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

        Artisan::call('app:clean-shopify-products');

        $this->assertEquals("The modified product is: " . json_encode($expectedCleanedProducts[0]) . "\n", Artisan::output());
    }
}
