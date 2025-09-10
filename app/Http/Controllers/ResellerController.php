<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ResellerSalesReportAnomaly;

class ResellerController extends Controller
{
    public function index()
    {
        $resellers = Reseller::with('contacts')->paginate(15);
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

    public function show(Reseller $reseller)
    {
        // Charger les relations nécessaires
        $reseller->load([
            'contacts',
            'deliveries.products',
            'deliveries.invoice', // ajout pour le statut de facturation
            'reports.items.product', // rapports de vente
        ]);

        // Charger le stock une seule fois
        $stock = $reseller->getCurrentStock();

        // Produits en stock
        $products = Product::whereIn('id', $stock->keys())
            ->with('brand')
            ->orderBy('name')
            ->paginate(20);

        // Livraisons avec pagination
        $deliveries = $reseller->deliveries()
            ->with(['products', 'invoice'])
            ->latest()
            ->paginate(10);

        // Rapports de vente (uniquement pour les consignations)
        $salesReports = collect();
        if ($reseller->type === 'consignment') {
            $salesReports = $reseller->reports()
                ->with('items.product')
                ->latest()
                ->paginate(10);
        }

        // Anomalies liées aux rapports du revendeur
        $anomalies = ResellerSalesReportAnomaly::whereHas('report', function($q) use ($reseller) {
            $q->where('reseller_id', $reseller->id);
        })->latest()->paginate(10);

        return view('resellers.show', compact(
            'reseller',
            'products',
            'deliveries',
            'stock',
            'salesReports',
            'anomalies'
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
