<?php

namespace App\Services\Shopify\Facade;

use App\Services\Shopify\ShopifyApiFake;
use App\Services\Shopify\ShopifyApiService;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HigherOrderTapProxy;


/**
 * @method static array getProducts(?int $page = null, ?int $limit = null)
 * @method static PromiseInterface|Response createProduct(array $product)
 * @method static array getInventoryLocations()
 * @method static void updateInventoryLevel($locationId, $inventoryItemId, $quantity, $productId)
 */
class ShopifyApi extends Facade
{
    public static function fake(): ShopifyApiFake|HigherOrderTapProxy
    {
        return tap(new ShopifyApiFake(), function ($fake) {
            self::swap($fake);
        });
    }

    protected static function getFacadeAccessor(): string
    {
        return ShopifyApiService::class;
    }

}
