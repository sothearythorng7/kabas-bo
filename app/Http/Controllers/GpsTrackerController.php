<?php

namespace App\Http\Controllers;

use App\Models\GpsDevice;
use App\Models\GpsPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GpsTrackerController extends Controller
{
    public function index()
    {
        $devices = GpsDevice::with('latestPosition')
            ->where('is_active', true)
            ->get();

        exec('pgrep -f "gps-listener.php" 2>/dev/null', $output, $exitCode);
        $listenerRunning = $exitCode === 0;

        return view('gps-tracker.index', compact('devices', 'listenerRunning'));
    }

    public function history(Request $request)
    {
        $deviceId = $request->input('device_id');
        $from = $request->input('from', now()->subDay()->toDateTimeString());
        $to = $request->input('to', now()->toDateTimeString());

        $positions = GpsPosition::where('device_id', $deviceId)
            ->whereBetween('device_time', [$from, $to])
            ->orderBy('device_time', 'asc')
            ->get(['latitude', 'longitude', 'speed', 'heading', 'altitude', 'device_time', 'created_at', 'gps_fixed', 'acc_on', 'battery_level']);

        // Reject GPS outliers: any point requiring > 200 km/h from the previous kept point
        $clean = collect();
        $lastClean = null;
        foreach ($positions as $pos) {
            if (!$lastClean) {
                $clean->push($pos);
                $lastClean = $pos;
                continue;
            }
            $dt = $pos->device_time && $lastClean->device_time
                ? max(1, $lastClean->device_time->diffInSeconds($pos->device_time))
                : 1;
            $dist = $this->haversineMeters(
                (float) $lastClean->latitude, (float) $lastClean->longitude,
                (float) $pos->latitude, (float) $pos->longitude
            );
            $impliedKmh = ($dist / $dt) * 3.6;
            if ($impliedKmh > 200) {
                continue; // teleport — drop
            }
            $clean->push($pos);
            $lastClean = $pos;
        }
        $positions = $clean;

        // Thin out stationary points: when speed=0 and acc unchanged, keep max 1 per 2 minutes
        $thinned = collect();
        $lastKept = null;

        foreach ($positions as $pos) {
            if (!$lastKept) {
                $thinned->push($pos);
                $lastKept = $pos;
                continue;
            }

            $speed = floatval($pos->speed);
            $lastSpeed = floatval($lastKept->speed);
            $accChanged = $pos->acc_on !== $lastKept->acc_on;
            $timeDiff = $pos->device_time && $lastKept->device_time
                ? $pos->device_time->diffInSeconds($lastKept->device_time)
                : 120;

            // Always keep if: moving, ACC changed, or enough time elapsed while stationary
            if ($speed > 2 || $lastSpeed > 2 || $accChanged || $timeDiff >= 120) {
                $thinned->push($pos);
                $lastKept = $pos;
            }
        }

        return response()->json($thinned->values());
    }

    public function latestPositions()
    {
        $devices = GpsDevice::with('latestPosition')
            ->where('is_active', true)
            ->get()
            ->map(function ($device) {
                $pos = $device->latestPosition;
                return [
                    'device_id' => $device->device_id,
                    'name' => $device->name,
                    'position' => $pos ? [
                        'lat' => $pos->latitude,
                        'lng' => $pos->longitude,
                        'speed' => $pos->speed,
                        'battery' => $pos->battery_level,
                        'gps_fixed' => $pos->gps_fixed,
                        'acc_on' => $pos->acc_on,
                        'time' => $pos->device_time?->format('Y-m-d H:i:s')
                            ?? $pos->created_at->format('Y-m-d H:i:s'),
                    ] : null,
                ];
            });

        return response()->json($devices);
    }

    /**
     * Batch geocode — returns cached results and resolves uncached ones
     * Uses Overpass API for POI names + Nominatim fallback for addresses
     */
    public function geocode(Request $request)
    {
        $points = $request->input('points', []);
        if (empty($points)) {
            return response()->json(['results' => []]);
        }

        $results = [];
        $toResolve = [];

        // Step 1: Check DB cache
        foreach ($points as $point) {
            $lat = round($point['lat'], 5);
            $lng = round($point['lng'], 5);
            $key = "{$lat},{$lng}";

            $cached = DB::table('gps_geocode_cache')
                ->where('latitude', $lat)
                ->where('longitude', $lng)
                ->value('location_name');

            if ($cached) {
                $results[$key] = $cached;
            } else {
                $toResolve[$key] = ['lat' => $lat, 'lng' => $lng];
            }
        }

        if (empty($toResolve)) {
            return response()->json(['results' => $results]);
        }

        // Step 2: Query Overpass for nearby POIs (single batch request)
        $poiNames = $this->overpassPOIs(array_values($toResolve));

        // Step 3: For remaining points, use Nominatim
        foreach ($toResolve as $key => $point) {
            if (!empty($poiNames[$key])) {
                $name = $poiNames[$key];
            } else {
                $name = $this->nominatimReverse($point['lat'], $point['lng']);
            }

            $results[$key] = $name;

            // Cache result
            DB::table('gps_geocode_cache')->updateOrInsert(
                ['latitude' => $point['lat'], 'longitude' => $point['lng']],
                ['location_name' => $name, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Query Overpass API for named POIs near multiple points (50m radius)
     * Returns ['lat,lng' => 'POI name, street'] for each point that has a nearby POI
     */
    private function overpassPOIs(array $points): array
    {
        if (empty($points)) return [];

        // Build Overpass query: search for named nodes/ways near each point
        $unions = [];
        foreach ($points as $p) {
            $lat = $p['lat'];
            $lng = $p['lng'];
            $unions[] = "node(around:50,{$lat},{$lng})[\"name\"][~\"^(amenity|shop|tourism|leisure|office|craft|healthcare|brand)$\"~\".\"];";
            $unions[] = "way(around:50,{$lat},{$lng})[\"name\"][~\"^(amenity|shop|tourism|leisure|office|craft|healthcare|brand)$\"~\".\"];";
        }

        $query = '[out:json][timeout:10];(' . implode('', $unions) . ');out center tags;';

        try {
            $ch = curl_init('https://overpass-api.de/api/interpreter');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => 'data=' . urlencode($query),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_HTTPHEADER => ['User-Agent: KabasGPSTracker/1.0'],
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$response) return [];

            $data = json_decode($response, true);
            if (empty($data['elements'])) return [];

            // For each input point, find the closest named POI
            $results = [];
            foreach ($points as $p) {
                $key = round($p['lat'], 5) . ',' . round($p['lng'], 5);
                $bestDist = PHP_FLOAT_MAX;
                $bestName = null;

                foreach ($data['elements'] as $el) {
                    $elLat = $el['lat'] ?? ($el['center']['lat'] ?? null);
                    $elLng = $el['lon'] ?? ($el['center']['lon'] ?? null);
                    if (!$elLat || !$elLng) continue;

                    $dist = $this->haversineMeters($p['lat'], $p['lng'], $elLat, $elLng);
                    if ($dist < $bestDist && $dist <= 50) {
                        $bestDist = $dist;
                        $tags = $el['tags'] ?? [];
                        $name = $tags['name'] ?? '';
                        $street = $tags['addr:street'] ?? '';
                        if ($name) {
                            $bestName = $street ? "{$name}, {$street}" : $name;
                        }
                    }
                }

                if ($bestName) {
                    $results[$key] = $bestName;
                }
            }

            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Nominatim reverse geocoding (fallback when no POI found)
     */
    private function nominatimReverse(float $lat, float $lng): string
    {
        try {
            usleep(1100000); // 1.1s — respect rate limit

            $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1&namedetails=1";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_HTTPHEADER => [
                    'Accept-Language: en',
                    'User-Agent: KabasGPSTracker/1.0',
                ],
            ]);
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
            if (!$data) return round($lat, 4) . ', ' . round($lng, 4);

            $addr = $data['address'] ?? [];
            $nameDetails = $data['namedetails'] ?? [];
            $poi = $nameDetails['name'] ?? $data['name'] ?? $addr['amenity'] ?? $addr['shop'] ?? $addr['tourism'] ?? $addr['leisure'] ?? $addr['building'] ?? '';
            $road = $addr['road'] ?? $addr['street'] ?? '';
            $area = $addr['suburb'] ?? $addr['neighbourhood'] ?? $addr['village'] ?? $addr['town'] ?? '';
            $city = $addr['city'] ?? $addr['state'] ?? '';

            if ($poi && $poi !== $road) {
                return $road ? "{$poi}, {$road}" : $poi;
            } elseif ($road) {
                return $area ? "{$road}, {$area}" : $road;
            } elseif ($area) {
                return $city ? "{$area}, {$city}" : $area;
            }
            return $city ?: round($lat, 4) . ', ' . round($lng, 4);
        } catch (\Exception $e) {
            return round($lat, 4) . ', ' . round($lng, 4);
        }
    }

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function devices()
    {
        $devices = GpsDevice::withCount('positions')->get();
        return view('gps-tracker.devices', compact('devices'));
    }

    public function storeDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string|unique:gps_devices,device_id',
            'name' => 'required|string|max:100',
            'model' => 'nullable|string|max:100',
            'sim_number' => 'nullable|string|max:20',
        ]);

        GpsDevice::create($request->only('device_id', 'name', 'model', 'sim_number'));

        return redirect()->route('gps-tracker.devices')->with('success', 'Device added.');
    }

    public function destroyDevice(GpsDevice $device)
    {
        $device->positions()->delete();
        $device->delete();

        return redirect()->route('gps-tracker.devices')->with('success', 'Device deleted.');
    }
}
