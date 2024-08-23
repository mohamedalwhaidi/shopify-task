<?php

namespace App\Console\Commands;

use App\Jobs\CreateShopifyProductsJob;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\UnavailableStream;

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
        try {
            $csv = Reader::createFromPath(Storage::path('products_export.csv'));
            $csv->setHeaderOffset(0);

            $stmt = new Statement();
            $records = collect($stmt->process($csv));

            $records->chunk(10)->each(function ($chunk) {
                CreateShopifyProductsJob::dispatch($chunk->toArray());
            });

            $this->info('Dispatched jobs to create Shopify products in batches.');
        } catch (UnavailableStream $e) {
            $this->error("Unable to open the CSV file: {$e->getMessage()}");
        } catch (Exception $e) {
            $this->error("Error processing the CSV file: {$e->getMessage()}");
        }
    }

}
