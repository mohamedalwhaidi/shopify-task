<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/create-products', function () {
    $storeName = config('services.shopify.store_name');

    $response = Http::withBasicAuth(config('services.shopify.api_key'), config('services.shopify.api_password'))
        ->get("https://$storeName.myshopify.com/admin/products.json", [
//            'page_info' => 'hijgklmn',
            'limit' => 10,
        ]);

    // print error
    if ($response->failed()) {
        return $response->json();
    }

    return $response->json();
});
