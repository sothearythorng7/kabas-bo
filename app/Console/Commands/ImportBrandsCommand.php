<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Brand;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportBrandsCommand extends Command
{
    protected $signature = 'import:brands {--file=storage/referentiel/referenciel_kabas.xlsx}';
    protected $description = 'Import brands from Excel file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = storage_path(str_replace('storage/', 'app/', $this->option('file')));

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info('Loading Excel file...');
        $spreadsheet = IOFactory::load($filePath);
        
        // Récupérer l'onglet "Brands"
        $worksheet = $spreadsheet->getSheetByName('Marques');
        
        if (!$worksheet) {
            $this->error('Sheet "Marques" not found in the Excel file.');
            return Command::FAILURE;
        }

        $this->info('Clearing existing brands...');
        
        // Désactiver les vérifications de clés étrangères
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Brand::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Importing brands...');
        $rows = $worksheet->toArray();

        // Ignorer la première ligne (en-tête)
        array_shift($rows);

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $brandName = trim($row[0] ?? '');

            if (empty($brandName)) {
                continue;
            }

            brand::create(['name' => $brandName]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Import completed successfully!');
        $this->info('Total brands created: ' . Brand::count());

        return Command::SUCCESS;
    }
}
