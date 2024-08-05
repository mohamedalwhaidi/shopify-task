<?php

namespace App\Console\Commands;

use App\Jobs\UpdateShopifyInventoryJob;
use App\Services\ShopifyApiService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

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

    public function __construct(
        public ShopifyApiService $shopifyService
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        UpdateShopifyInventoryJob::dispatch();

        $this->info('Dispatched jobs to update inventory levels.');
    }
}
