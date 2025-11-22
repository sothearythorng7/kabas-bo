<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class LinkProductsToStores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:link-to-stores {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link all products to all stores (with is_reseller=true)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Récupérer tous les magasins (is_reseller = true)
        $stores = Store::where('is_reseller', true)->get();

        if ($stores->isEmpty()) {
            $this->error('❌ No stores found with is_reseller=true');
            return Command::FAILURE;
        }

        $this->info("📍 Stores found: {$stores->count()}");
        foreach ($stores as $store) {
            $this->line("   - Store #{$store->id}: {$store->name}");
        }
        $this->newLine();

        // Récupérer tous les produits
        $products = Product::all();
        $this->info("📦 Products found: {$products->count()}");
        $this->newLine();

        // Compter les liaisons existantes
        $existingLinks = DB::table('product_store')->count();
        $this->info("🔗 Existing links: {$existingLinks}");
        $this->newLine();

        $totalLinksToCreate = 0;
        $linksCreated = 0;

        // Pour chaque produit
        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();

        foreach ($products as $product) {
            foreach ($stores as $store) {
                // Vérifier si la liaison existe déjà
                $exists = DB::table('product_store')
                    ->where('product_id', $product->id)
                    ->where('store_id', $store->id)
                    ->exists();

                if (!$exists) {
                    $totalLinksToCreate++;

                    if (!$dryRun) {
                        // Créer la liaison via la relation many-to-many
                        $product->stores()->attach($store->id, [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $linksCreated++;
                    }
                }
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("✅ Would create {$totalLinksToCreate} new links");
            $this->info("💡 Run without --dry-run to apply changes");
        } else {
            $this->info("✅ Created {$linksCreated} new links");

            // Afficher le résultat final
            $finalLinks = DB::table('product_store')->count();
            $this->info("🔗 Total links now: {$finalLinks}");
            $this->newLine();

            // Afficher par magasin
            $this->info("📊 Links per store:");
            foreach ($stores as $store) {
                $count = $store->products()->count();
                $this->line("   - {$store->name}: {$count} products");
            }
        }

        return Command::SUCCESS;
    }
}
