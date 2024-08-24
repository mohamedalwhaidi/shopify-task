<?php

namespace App\Console\Commands;

use App\Jobs\UpdateShopifyInventoryJob;
use App\Services\Shopify\Facade\ShopifyApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchUpdateShopifyInventoryJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-update-shopify-inventory-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to update Shopify inventory levels to 50';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $query = ['limit' => 50];
        $locations = ShopifyApi::getInventoryLocations();


        if (empty($locations)) {
            Log::error('Failed to retrieve inventory locations');
            return;
        }

        do {
            $result = ShopifyApi::getProducts($query);

            $products = $result['data'];
            $pageInfo = $result['page_info'];

            if (empty($products)) {
                break;
            }

            UpdateShopifyInventoryJob::dispatchSync($products, $locations);

            if ($pageInfo && $pageInfo->hasNextPage()) {
                $query = $pageInfo->getNextPageQuery();
            } else {
                break;
            }

        } while (true);

        $this->info('Dispatched jobs to update inventory levels.');
    }
}
