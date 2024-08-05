<?php

namespace App\Console\Commands;

use App\Jobs\CreateShopifyProductsJob;
use Illuminate\Console\Command;

class DispatchCreateShopifyProductsJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-create-shopify-products-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch job to create products in Shopify';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        CreateShopifyProductsJob::dispatch();
        $this->info('Dispatched job to create Shopify products.');
    }
}
