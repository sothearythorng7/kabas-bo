<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\Production;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class FactoryDashboardController extends Controller
{
    public function index()
    {
        // Statistiques générales
        $stats = [
            'suppliers_count' => Supplier::active()->rawMaterialSuppliers()->count(),
            'materials_count' => RawMaterial::active()->count(),
            'recipes_count' => Recipe::active()->count(),
            'productions_this_month' => Production::completed()
                ->whereMonth('produced_at', now()->month)
                ->whereYear('produced_at', now()->year)
                ->count(),
            'units_produced_this_month' => (int) Production::completed()
                ->whereMonth('produced_at', now()->month)
                ->whereYear('produced_at', now()->year)
                ->sum('quantity_produced'),
        ];

        // Matières premières en alerte de stock
        $lowStockMaterials = RawMaterial::active()
            ->tracked()
            ->whereNotNull('alert_quantity')
            ->get()
            ->filter(fn($m) => $m->isLowStock())
            ->take(10);

        // Dernières productions
        $recentProductions = Production::with(['recipe.product', 'user'])
            ->completed()
            ->latest('produced_at')
            ->limit(5)
            ->get();

        // Productions par recette ce mois-ci
        $productionsByRecipe = Production::completed()
            ->whereMonth('produced_at', now()->month)
            ->whereYear('produced_at', now()->year)
            ->select('recipe_id', DB::raw('SUM(quantity_produced) as total'))
            ->groupBy('recipe_id')
            ->with('recipe.product')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Overview du stock de matières premières
        $rawMaterialsStock = RawMaterial::active()
            ->tracked()
            ->with('supplier')
            ->orderBy('name')
            ->get()
            ->map(function ($material) {
                return [
                    'id' => $material->id,
                    'name' => $material->name,
                    'sku' => $material->sku,
                    'unit' => $material->unit,
                    'total_stock' => $material->total_stock,
                    'alert_quantity' => $material->alert_quantity,
                    'is_low_stock' => $material->isLowStock(),
                    'supplier_name' => $material->supplier?->name,
                ];
            });

        return view('factory.dashboard', compact(
            'stats',
            'lowStockMaterials',
            'recentProductions',
            'productionsByRecipe',
            'rawMaterialsStock'
        ));
    }
}
