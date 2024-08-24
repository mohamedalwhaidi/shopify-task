<?php

namespace App\Services\Shopify\Facade;

use App\Services\Shopify\ShopifyApiFake;
use App\Services\Shopify\ShopifyApiService;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HigherOrderTapProxy;
use Shopify\Clients\HttpResponse;


/**
 * @method static array getProducts(?array $query = ['limit' => 50])
 * @method static PromiseInterface|Response createProduct(array $product)
 * @method static array getInventoryLocations()
 * @method static void updateInventoryLevel($locationId, $inventoryItemId, $quantity, $productId)
 * @method static void addLocation(array $location)
 * @method static void setException(Exception $exception)
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
