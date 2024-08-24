<?php

namespace Tests\Unit;

use App\Services\Shopify\Facade\ShopifyApi;
use Exception;
use Tests\TestCase;


class ShopifyApiFakeTest extends TestCase
{
    public function test_it_can_add_and_retrieve_products()
    {
        ShopifyApi::fake();

        ShopifyApi::createProduct([
            'product' => [
                'title' => 'Test Product',
            ]
        ]);
        $response = ShopifyApi::getProducts();

        $this->assertCount(1, $response['data']);
        $this->assertEquals('Test Product', $response['data'][0]['product']['title']);
    }

    public function test_it_can_throw_exception_when_set()
    {
        ShopifyApi::fake();

        ShopifyApi::setException(new Exception('Test Exception'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test Exception');

        ShopifyApi::getProducts();
    }

    public function test_it_can_add_and_retrieve_locations()
    {
        ShopifyApi::fake();

        ShopifyApi::addLocation(['name' => 'Test Location']);
        $locations = ShopifyApi::getInventoryLocations();

        $this->assertCount(1, $locations);
        $this->assertEquals('Test Location', $locations[0]['name']);
    }

    public function test_it_can_update_inventory_level()
    {
        ShopifyApi::fake();

        $result = ShopifyApi::updateInventoryLevel(1, 1, 100, 1);

        $this->assertTrue((bool)$result);
    }
}
