<?php

namespace App\Console\Commands;

use App\Models\ShippingCarrier;
use App\Models\ShippingCountry;
use App\Models\ShippingRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncCambodiaPostRates extends Command
{
    protected $signature = 'shipping:sync-cambodia-post
                            {--delay=1500 : Delay in ms between API requests}
                            {--dry-run : Show what would be done without writing to DB}';

    protected $description = 'Sync Cambodia Post shipping rates (EMS, Parcel, Letter) from their online calculator';

    private const API_URL = 'https://cambodiapost.com.kh/delivery_service/services/calculate_item_total_cost_multi';
    private const COUNTRIES_URL = 'https://cambodiapost.com.kh/select2/getAllCountries';

    // Map Cambodia Post service names to our carrier names
    private const SERVICE_CARRIER_MAP = [
        'EMS'                  => 'EMS',
        'Parcel'               => 'Parcel',
        'Letter'               => 'Letter',
        'Letter Register'      => 'Letter',
        'Letter Register + AR' => 'Letter',
        'AO'                   => 'Letter',
        'ePacket'              => 'ePacket',
    ];

    // Weight steps in kg matching existing DB brackets (0.5 kg increments)
    private array $weightSteps = [];

    public function handle(): int
    {
        // Build weight steps: 0.5, 1.0, 1.5, ..., 30.0
        for ($w = 0.5; $w <= 30.0; $w = round($w + 0.5, 1)) {
            $this->weightSteps[] = $w;
        }

        $delay = (int) $this->option('delay');
        $dryRun = $this->option('dry-run');

        $this->info('Fetching countries from Cambodia Post...');

        $countries = $this->fetchCountries();
        if (empty($countries)) {
            $this->error('Could not fetch countries from Cambodia Post API.');
            return self::FAILURE;
        }

        $this->info(count($countries) . ' countries found on Cambodia Post.');

        // Build lookup: Cambodia Post country_id => { cp_id, code, name }
        $cpCountries = [];
        foreach ($countries as $c) {
            // Format: "FR (FRANCE)" or "US (UNITED STATES)"
            if (preg_match('/^([A-Z]{2})\s+\((.+)\)$/', $c['text'], $m)) {
                $cpCountries[] = [
                    'cp_id' => $c['id'],
                    'code'  => $m[1],
                    'name'  => $m[2],
                ];
            }
        }

        $this->info(count($cpCountries) . ' countries parsed.');

        // Track which country codes Cambodia Post serves
        $servedCountryCodes = [];
        $totalRates = 0;
        $totalErrors = 0;

        $bar = $this->output->createProgressBar(count($cpCountries));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        foreach ($cpCountries as $cpCountry) {
            $bar->setMessage($cpCountry['code'] . ' (' . $cpCountry['name'] . ')');

            $dbCountry = ShippingCountry::where('code', $cpCountry['code'])->first();
            if (!$dbCountry) {
                $bar->advance();
                continue;
            }

            // Query API with a representative weight to check which services are available
            // Then query per weight step for each available service
            $countryRates = $this->fetchRatesForCountry($cpCountry['cp_id'], $delay);

            if ($countryRates === null) {
                $totalErrors++;
                $bar->advance();
                continue;
            }

            if (!empty($countryRates)) {
                $servedCountryCodes[] = $cpCountry['code'];

                if (!$dryRun) {
                    $this->saveRates($dbCountry, $countryRates);
                }

                $rateCount = 0;
                foreach ($countryRates as $carrierRates) {
                    $rateCount += count($carrierRates);
                }
                $totalRates += $rateCount;
            }

            $bar->advance();
        }

        $bar->setMessage('Done!');
        $bar->finish();
        $this->newLine(2);

        // Update country active status based on Cambodia Post availability
        if (!$dryRun) {
            $this->updateCountryStatus($servedCountryCodes);
        }

        $this->info("Sync complete: {$totalRates} rates synced for " . count($servedCountryCodes) . " countries.");
        if ($totalErrors > 0) {
            $this->warn("{$totalErrors} countries had API errors.");
        }
        if ($dryRun) {
            $this->warn('Dry run — no changes written to database.');
        }

        return self::SUCCESS;
    }

    /**
     * Fetch all countries from Cambodia Post API (paginated).
     */
    private function fetchCountries(): array
    {
        $countries = [];
        $page = 1;
        $lastPage = 1;

        do {
            try {
                $response = Http::timeout(15)
                    ->get(self::COUNTRIES_URL, ['keyword' => '', 'page' => $page]);

                if (!$response->successful()) break;

                $data = $response->json();
                $results = $data['data'] ?? [];
                $lastPage = $data['last_page'] ?? 1;

                if (empty($results)) break;

                $countries = array_merge($countries, $results);
                $page++;

                usleep(500_000); // 500ms between pages
            } catch (\Exception $e) {
                $this->warn("Error fetching countries page {$page}: " . $e->getMessage());
                break;
            }
        } while ($page <= $lastPage);

        return $countries;
    }

    /**
     * Fetch rates for a single country across all weight steps.
     * Returns: ['EMS' => [[weight_kg, price, time_min, time_max], ...], 'Parcel' => [...]]
     */
    private function fetchRatesForCountry(int $cpCountryId, int $delayMs): ?array
    {
        $allRates = [];

        foreach ($this->weightSteps as $weightKg) {
            try {
                $response = Http::timeout(15)
                    ->get(self::API_URL, [
                        'delivery_service_id' => '',
                        'country_id'          => $cpCountryId,
                        'total_weight'        => $weightKg,
                        'service'             => 'international',
                        'province_from_id'    => '',
                        'district_from_id'    => '',
                        'province_id'         => '',
                        'district_id'         => '',
                    ]);

                if (!$response->successful()) {
                    // Weight out of range for this country — stop incrementing
                    if ($response->status() === 404) break;
                    continue;
                }

                $data = $response->json();

                if (($data['code'] ?? 0) !== 200 || empty($data['result'])) {
                    continue;
                }

                foreach ($data['result'] as $service) {
                    $serviceName = $service['service'] ?? '';
                    $carrierName = self::SERVICE_CARRIER_MAP[$serviceName] ?? null;

                    if (!$carrierName) continue;

                    $price = $service['cost_usd'] ?? null;
                    if ($price === null) continue;

                    $price = round((float) $price, 5);
                    $key = $carrierName . '_' . $weightKg;

                    // Keep the cheapest rate when multiple services map to the same carrier
                    if (!isset($allRates[$carrierName][$key]) || $price < $allRates[$carrierName][$key]['price']) {
                        $allRates[$carrierName][$key] = [
                            'weight_kg' => $weightKg,
                            'price'     => $price,
                            'time_min'  => $service['time_estimate'] ?? null,
                            'time_max'  => $service['time_estimate_to'] ?? null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Cambodia Post API error for country {$cpCountryId} at {$weightKg}kg: " . $e->getMessage());
                return null;
            }

            usleep($delayMs * 1000);
        }

        return $allRates;
    }

    /**
     * Save rates to database for a given country.
     */
    private function saveRates(ShippingCountry $country, array $ratesByCarrier): void
    {
        DB::transaction(function () use ($country, $ratesByCarrier) {
            foreach ($ratesByCarrier as $carrierName => $rates) {
                $carrier = ShippingCarrier::firstOrCreate(
                    ['name' => $carrierName],
                    ['is_active' => true]
                );

                // Remove existing rates for this country+carrier to avoid stale data
                ShippingRate::where('shipping_country_id', $country->id)
                    ->where('shipping_carrier_id', $carrier->id)
                    ->delete();

                foreach (array_values($rates) as $rate) {
                    $weightFrom = round($rate['weight_kg'] - 0.5, 2);
                    if ($weightFrom < 0) $weightFrom = 0;

                    ShippingRate::create([
                        'shipping_country_id' => $country->id,
                        'shipping_carrier_id' => $carrier->id,
                        'weight_from'         => $weightFrom,
                        'weight_to'           => $rate['weight_kg'],
                        'price'               => $rate['price'],
                        'delivery_time_min'   => $rate['time_min'],
                        'delivery_time_max'   => $rate['time_max'],
                    ]);
                }
            }
        });
    }

    /**
     * Activate countries served by Cambodia Post, deactivate those no longer served.
     * Only touches countries that have Cambodia Post carrier rates.
     */
    private function updateCountryStatus(array $servedCodes): void
    {
        $cambodiaPostCarrierIds = ShippingCarrier::whereIn('name', array_values(self::SERVICE_CARRIER_MAP))
            ->pluck('id')
            ->toArray();

        if (empty($cambodiaPostCarrierIds)) return;

        // Activate countries that Cambodia Post serves
        ShippingCountry::whereIn('code', $servedCodes)
            ->where('is_active', false)
            ->update(['is_active' => true]);

        // Deactivate countries that Cambodia Post no longer serves
        // Only if they have NO rates from other carriers
        $noLongerServed = ShippingCountry::where('is_active', true)
            ->whereNotIn('code', $servedCodes)
            ->get();

        foreach ($noLongerServed as $country) {
            $hasOtherCarrierRates = ShippingRate::where('shipping_country_id', $country->id)
                ->whereNotIn('shipping_carrier_id', $cambodiaPostCarrierIds)
                ->exists();

            if (!$hasOtherCarrierRates) {
                $country->update(['is_active' => false]);
            }
        }

        $activated = ShippingCountry::whereIn('code', $servedCodes)->where('is_active', true)->count();
        $this->info("Country status: {$activated} active countries.");
    }
}
