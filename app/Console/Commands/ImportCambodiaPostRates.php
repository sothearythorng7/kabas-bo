<?php

namespace App\Console\Commands;

use App\Models\ShippingCarrier;
use App\Models\ShippingCountry;
use App\Models\ShippingRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportCambodiaPostRates extends Command
{
    protected $signature = 'shipping:import-cambodia-post
                            {file=storage/app/shipping/cambodia-post.xlsx : Path to the xlsx file}
                            {--clear : Delete existing Cambodia Post rates before importing}';

    protected $description = 'Import Cambodia Post shipping rates (EMS, Parcel, Letter) from scraped xlsx file';

    // Map sheet names to carrier names
    private array $sheetCarrierMap = [
        'EMS'    => 'EMS',
        'Parcel' => 'Parcel',
        'Letter' => 'Letter',
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        $spreadsheet = IOFactory::load($filePath);
        $totalImported = 0;
        $totalSkipped = 0;

        DB::transaction(function () use ($spreadsheet, &$totalImported, &$totalSkipped) {
            foreach ($this->sheetCarrierMap as $sheetName => $carrierName) {
                if (!$spreadsheet->sheetNameExists($sheetName)) {
                    $this->warn("Sheet '{$sheetName}' not found, skipping.");
                    continue;
                }

                $carrier = ShippingCarrier::firstOrCreate(
                    ['name' => $carrierName],
                    ['is_active' => true]
                );

                if ($this->option('clear')) {
                    $deleted = ShippingRate::where('shipping_carrier_id', $carrier->id)->delete();
                    $this->info("Cleared {$deleted} existing rates for {$carrierName}");
                }

                $sheet = $spreadsheet->getSheetByName($sheetName);
                $rows = $sheet->toArray();

                // Row 2 = weight headers: "Pays / Country", "500g", "1 kg", "1.5 kg", ...
                $weightHeaders = $rows[2];
                $weights = $this->parseWeightHeaders($weightHeaders);

                $this->info("Importing {$carrierName} ({$sheetName})...");
                $carrierImported = 0;
                $carrierSkipped = 0;

                // Data rows start at index 3
                for ($i = 3; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    $countryRaw = $row[0] ?? '';

                    if (empty($countryRaw)) continue;

                    // Extract country code: "FR (FRANCE)" -> "FR"
                    $countryCode = $this->extractCountryCode($countryRaw);
                    if (!$countryCode) {
                        $this->warn("  Could not parse country: {$countryRaw}");
                        $carrierSkipped++;
                        continue;
                    }

                    $country = ShippingCountry::where('code', $countryCode)->first();
                    if (!$country) {
                        $this->warn("  Country not found in DB: {$countryCode} — skipping");
                        $carrierSkipped++;
                        continue;
                    }

                    // Activate the country since Cambodia Post ships there
                    if (!$country->is_active) {
                        $country->update(['is_active' => true]);
                    }

                    foreach ($weights as $colIdx => $weightKg) {
                        $priceRaw = $row[$colIdx] ?? null;
                        $price = $this->parsePrice($priceRaw);

                        if ($price === null) continue; // "—" or empty = no service for this weight

                        // weight_from = previous step (or 0 for first)
                        $weightFrom = $weightKg - 0.5;
                        if ($weightFrom < 0) $weightFrom = 0;
                        $weightTo = $weightKg;

                        ShippingRate::updateOrCreate(
                            [
                                'shipping_country_id' => $country->id,
                                'shipping_carrier_id' => $carrier->id,
                                'weight_from'         => $weightFrom,
                                'weight_to'           => $weightTo,
                            ],
                            [
                                'price' => $price,
                            ]
                        );

                        $carrierImported++;
                    }
                }

                $this->info("  ✓ {$carrierImported} rates imported, {$carrierSkipped} countries skipped");
                $totalImported += $carrierImported;
                $totalSkipped += $carrierSkipped;
            }
        });

        $this->newLine();
        $this->info("Done! {$totalImported} rates imported, {$totalSkipped} skipped.");

        return self::SUCCESS;
    }

    /**
     * Parse weight headers like "500g", "1 kg", "1.5 kg", "20 kg"
     * Returns [colIndex => weightInKg]
     */
    private function parseWeightHeaders(array $headers): array
    {
        $weights = [];
        foreach ($headers as $idx => $header) {
            if ($idx === 0) continue; // skip "Pays / Country"
            if (empty($header)) continue;

            $header = trim(str_replace("\xc2\xa0", ' ', $header));

            if (preg_match('/^([\d.]+)\s*g$/i', $header, $m)) {
                $weights[$idx] = floatval($m[1]) / 1000; // 500g -> 0.5
            } elseif (preg_match('/^([\d.]+)\s*kg$/i', $header, $m)) {
                $weights[$idx] = floatval($m[1]);
            }
        }
        return $weights;
    }

    /**
     * Extract ISO country code from "FR (FRANCE)" format
     */
    private function extractCountryCode(string $raw): ?string
    {
        if (preg_match('/^([A-Z]{2})\s/', trim($raw), $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Parse price from "49.50 $" format, return null for "—" or empty
     */
    private function parsePrice(?string $raw): ?float
    {
        if ($raw === null || $raw === '' || $raw === '—' || $raw === "\u{2014}") {
            return null;
        }

        $cleaned = preg_replace('/[^0-9.]/', '', $raw);
        if ($cleaned === '' || $cleaned === '.') return null;

        return round(floatval($cleaned), 2);
    }
}
