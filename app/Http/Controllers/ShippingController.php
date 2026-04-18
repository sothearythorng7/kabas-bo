<?php

namespace App\Http\Controllers;

use App\Models\ShippingCarrier;
use App\Models\ShippingCountry;
use App\Models\ShippingRate;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function countries()
    {
        $countries = ShippingCountry::orderBy('continent')->orderBy('name')->get()->groupBy('continent');
        $activeCount = ShippingCountry::where('is_active', true)->count();

        return view('shipping.countries', compact('countries', 'activeCount'));
    }

    public function updateCountries(Request $request)
    {
        $activeIds = $request->input('countries', []);

        ShippingCountry::query()->update(['is_active' => false]);

        if (!empty($activeIds)) {
            ShippingCountry::whereIn('id', $activeIds)->update(['is_active' => true]);
        }

        return redirect()->route('shipping-countries.index')
            ->with('success', __('messages.shipping.countries_updated'));
    }

    public function rates(Request $request)
    {
        $activeCountries = ShippingCountry::active()->orderBy('name')->get();
        $carriers = ShippingCarrier::orderBy('name')->get();
        $selectedCountry = null;
        $selectedCarrier = null;
        $rates = collect();

        if ($request->has('country_id') && $request->country_id) {
            $selectedCountry = ShippingCountry::find($request->country_id);
        }

        if ($request->has('carrier_id') && $request->carrier_id) {
            $selectedCarrier = ShippingCarrier::find($request->carrier_id);
        }

        if ($selectedCountry && $selectedCarrier) {
            $rates = ShippingRate::where('shipping_country_id', $selectedCountry->id)
                ->where('shipping_carrier_id', $selectedCarrier->id)
                ->orderBy('weight_from')
                ->get();
        }

        return view('shipping.rates', compact('activeCountries', 'carriers', 'selectedCountry', 'selectedCarrier', 'rates'));
    }

    public function duplicateRates(Request $request)
    {
        $data = $request->validate([
            'source_country_id'   => 'required|exists:shipping_countries,id',
            'target_country_id'   => 'required|exists:shipping_countries,id|different:source_country_id',
            'shipping_carrier_id' => 'required|exists:shipping_carriers,id',
        ]);

        $sourceRates = ShippingRate::where('shipping_country_id', $data['source_country_id'])
            ->where('shipping_carrier_id', $data['shipping_carrier_id'])
            ->get();

        if ($sourceRates->isEmpty()) {
            return redirect()->back()->with('error', __('messages.shipping.no_rates_to_duplicate'));
        }

        foreach ($sourceRates as $rate) {
            ShippingRate::updateOrCreate(
                [
                    'shipping_country_id'  => $data['target_country_id'],
                    'shipping_carrier_id'  => $data['shipping_carrier_id'],
                    'weight_from'          => $rate->weight_from,
                    'weight_to'            => $rate->weight_to,
                ],
                [
                    'price' => $rate->price,
                ]
            );
        }

        return redirect()
            ->route('shipping-rates.index', ['country_id' => $data['target_country_id'], 'carrier_id' => $data['shipping_carrier_id']])
            ->with('success', __('messages.shipping.rates_duplicated'));
    }

    public function storeRate(Request $request)
    {
        $validated = $request->validate([
            'shipping_country_id' => 'required|exists:shipping_countries,id',
            'shipping_carrier_id' => 'required|exists:shipping_carriers,id',
            'weight_from' => 'required|numeric|min:0',
            'weight_to' => 'required|numeric|gt:weight_from',
            'price' => 'required|numeric|min:0',
        ]);

        ShippingRate::create($validated);

        return redirect()->route('shipping-rates.index', [
            'country_id' => $validated['shipping_country_id'],
            'carrier_id' => $validated['shipping_carrier_id'],
        ])->with('success', __('messages.shipping.rate_created'));
    }

    public function updateRate(Request $request, ShippingRate $rate)
    {
        $validated = $request->validate([
            'weight_from' => 'required|numeric|min:0',
            'weight_to' => 'required|numeric|gt:weight_from',
            'price' => 'required|numeric|min:0',
        ]);

        $rate->update($validated);

        return redirect()->route('shipping-rates.index', [
            'country_id' => $rate->shipping_country_id,
            'carrier_id' => $rate->shipping_carrier_id,
        ])->with('success', __('messages.shipping.rate_updated'));
    }

    public function destroyRate(ShippingRate $rate)
    {
        $countryId = $rate->shipping_country_id;
        $carrierId = $rate->shipping_carrier_id;
        $rate->delete();

        return redirect()->route('shipping-rates.index', [
            'country_id' => $countryId,
            'carrier_id' => $carrierId,
        ])->with('success', __('messages.shipping.rate_deleted'));
    }

    // ---- Carrier CRUD ----

    public function carriers()
    {
        $carriers = ShippingCarrier::orderBy('name')->get();

        return view('shipping.carriers', compact('carriers'));
    }

    public function storeCarrier(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:shipping_carriers,name',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        ShippingCarrier::create($validated);

        return redirect()->route('shipping-carriers.index')
            ->with('success', __('messages.shipping.carrier_created'));
    }

    public function toggleCarrier(ShippingCarrier $carrier)
    {
        $carrier->update(['is_active' => !$carrier->is_active]);

        return redirect()->route('shipping-carriers.index')
            ->with('success', __('messages.shipping.carrier_updated'));
    }

    public function updateCarrier(Request $request, ShippingCarrier $carrier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:shipping_carriers,name,' . $carrier->id,
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $carrier->update($validated);

        return redirect()->route('shipping-carriers.index')
            ->with('success', __('messages.shipping.carrier_updated'));
    }

    public function destroyCarrier(ShippingCarrier $carrier)
    {
        $carrier->delete();

        return redirect()->route('shipping-carriers.index')
            ->with('success', __('messages.shipping.carrier_deleted'));
    }
}
