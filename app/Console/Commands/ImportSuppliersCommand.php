<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Supplier;
use App\Models\Contact;

class ImportSuppliersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:suppliers {--file=storage/referentiel/referenciel_kabas.xlsx}';
    protected $description = 'Import Suppliers from Excel file';

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
        
        // Récupérer l'onglet "Fournisseurs"
        $worksheet = $spreadsheet->getSheetByName('Fournisseurs');
        
        if (!$worksheet) {
            $this->error('Sheet "Fournisseurs" not found in the Excel file.');
            return Command::FAILURE;
        }

        $this->info('Clearing existing suppliers...');
        // Désactiver les vérifications de clés étrangères
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Supplier::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Importing suppliers...');
        $rows = $worksheet->toArray();
        array_shift($rows);
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $supplierName = trim($row[0] ?? '');
            if (empty($supplierName)) {
                continue;
            }
            $isBuyer = trim(strtolower($row[4] ?? '')) === 'non';

            $supplier = Supplier::create([
                'name' => $supplierName,
                'address' => "",
                'type' => $isBuyer ? 'buyer' : 'consignment',
            ]);

            $contact = self::normalizeContact($row[1] ?? '');

            Contact::create([
                'supplier_id' => $supplier->id,
                'last_name' => $contact['lastName'],
                'first_name' => $contact['firstName'],
                'email' => trim($row[2] ?? ''),
                'phone' => trim($row[3] ?? ''),
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Import completed successfully!');
        $this->info('Total suppliers created: ' . Supplier::count());

        return Command::SUCCESS;
    }

    private function normalizeContact(?string $contact): array
    {
        $contact = trim($contact ?? '');

        if ($contact === '') {
            return ['firstName' => null, 'lastName' => null];
        }

        // Supprimer les titres et préfixes courants
        $contact = preg_replace('/\b(Mr\.?|Ms\.?|Mrs\.?|Mme\.?|Mlle\.?|Dr\.?|Prof\.?|Sir|Miss|M)\b\.?/i', '', $contact);

        // Nettoyage des espaces multiples et des points
        $contact = preg_replace('/\s+/', ' ', $contact);
        $contact = trim($contact, " \t\n\r\0\x0B.");

        // Si le nom ressemble à une entreprise, on renvoie juste le nom complet
        if (preg_match('/(Co|Ltd|SARL|SA|SAS|PRO|Company|Enterprises?|Corporation|Studio|Group|Agency)/i', $contact)
            || strtoupper($contact) === $contact
            || str_word_count($contact) === 1 && strlen($contact) <= 4
        ) {
            return ['firstName' => null, 'lastName' => $contact];
        }

        // Séparer par espaces
        $parts = explode(' ', $contact);

        // Cas simples : 2 parties -> prénom + nom
        if (count($parts) === 2) {
            return [
                'firstName' => ucfirst(strtolower($parts[0])),
                'lastName'  => ucfirst(strtolower($parts[1])),
            ];
        }

        // Cas complexes : plusieurs mots
        $firstName = ucfirst(strtolower(array_shift($parts)));
        $lastName  = ucfirst(implode(' ', $parts));

        return [
            'firstName' => $firstName ?: null,
            'lastName'  => $lastName ?: null,
        ];
    }

}
