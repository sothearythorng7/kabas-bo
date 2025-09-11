<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ResellerSalesReportAnomaly;
use App\Models\Store; // N'oublie pas d'importer Store si tu l'utilises
use Illuminate\Pagination\LengthAwarePaginator;

class ResellerController extends Controller
{
    public function index()
    {
        // Récupération des resellers et shops
        $allResellers = Reseller::allWithShops();

        // Pagination manuelle
        $page = request()->get('page', 1);
        $perPage = 15;
        $items = $allResellers->forPage($page, $perPage);

        $resellers = new LengthAwarePaginator(
            $items,
            $allResellers->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('resellers.index', compact('resellers'));
    }

    public function create()
    {
        return view('resellers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:buyer,consignment',
        ]);

        $reseller = Reseller::create($data);

        return redirect()->route('resellers.show', $reseller)->with('success', 'Reseller created.');
    }

    public function show($id)
    {
        // Cas particulier pour un shop
        if (str_starts_with($id, 'shop-')) {
            $shopId = (int) str_replace('shop-', '', $id);
            $shop = Store::findOrFail($shopId);

            $reseller = (object)[
                'id' => $id,
                'name' => $shop->name,
                'type' => 'consignment',
                'contacts' => collect(),
                'is_shop' => true,
                'store' => $shop,
            ];

            $stock = $shop->getCurrentStock();

            $products = Product::whereIn('id', $stock->keys())
                ->with('brand')
                ->orderBy('name')
                ->paginate(20);

            // Récupération des livraisons pour ce shop
            $deliveries = \App\Models\ResellerStockDelivery::where('store_id', $shopId)
                ->with(['products','reseller'])
                ->latest()
                ->paginate(10);

            $salesReports = collect(); // Pas de rapports pour les shops
            $anomalies = collect(); // Pas d’anomalies pour les shops

            return view('resellers.show', compact(
                'reseller','products','deliveries','stock','salesReports','anomalies'
            ));
        }

        // Reseller classique
        $reseller = Reseller::with([
            'contacts',
            'deliveries.products',
            'deliveries.invoice',
            'reports.items.product',
        ])->findOrFail($id);

        $stock = $reseller->getCurrentStock();

        $products = Product::whereIn('id', $stock->keys())
            ->with('brand')
            ->orderBy('name')
            ->paginate(20);

        $deliveries = $reseller->deliveries()
            ->with(['products','invoice'])
            ->latest()
            ->paginate(10);

        $salesReports = $reseller->type === 'consignment'
            ? $reseller->reports()->with('items.product')->latest()->paginate(10)
            : collect();

        $anomalies = ResellerSalesReportAnomaly::whereHas('report', function($q) use($reseller){
            $q->where('reseller_id', $reseller->id);
        })->latest()->paginate(10);

        return view('resellers.show', compact(
            'reseller','products','deliveries','stock','salesReports','anomalies'
        ));
    }

    public function edit(Reseller $reseller)
    {
        return view('resellers.edit', compact('reseller'));
    }

    public function update(Request $request, Reseller $reseller)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:buyer,consignment',
        ]);

        $reseller->update($data);

        return redirect()->route('resellers.show', $reseller)->with('success', 'Reseller updated.');
    }

    public function destroy(Reseller $reseller)
    {
        $reseller->delete();
        return redirect()->route('resellers.index')->with('success', 'Reseller deleted.');
    }
}
