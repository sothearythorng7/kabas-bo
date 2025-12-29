<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\ProductionConsumption;
use App\Models\Recipe;
use App\Models\RawMaterial;
use App\Models\StockBatch;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $query = Production::with(['recipe.product', 'user'])
            ->withCount('consumptions');

        if ($request->filled('recipe_id')) {
            $query->where('recipe_id', $request->recipe_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('produced_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('produced_at', '<=', $request->date_to);
        }

        $productions = $query->latest('produced_at')->paginate(20)->withQueryString();
        $recipes = Recipe::active()->orderBy('name')->get();

        return view('factory.productions.index', compact('productions', 'recipes'));
    }

    public function create(Request $request)
    {
        $recipes = Recipe::active()->with(['product', 'items.rawMaterial'])->orderBy('name')->get();
        $selectedRecipe = null;

        if ($request->filled('recipe_id')) {
            $selectedRecipe = Recipe::with(['product', 'items.rawMaterial'])->find($request->recipe_id);
        }

        // Trouver le warehouse (store de type warehouse)
        $warehouse = Store::where('type', 'warehouse')->first();

        return view('factory.productions.create', compact('recipes', 'selectedRecipe', 'warehouse'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'quantity_produced' => 'required|integer|min:1',
            'produced_at' => 'required|date',
            'batch_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'consumptions' => 'required|array',
            'consumptions.*.raw_material_id' => 'required|exists:raw_materials,id',
            'consumptions.*.quantity_consumed' => 'required|numeric|min:0',
        ]);

        $recipe = Recipe::with('product')->findOrFail($data['recipe_id']);

        // Trouver le warehouse
        $warehouse = Store::where('type', 'warehouse')->first();
        if (!$warehouse) {
            return back()->with('error', __('messages.factory.no_warehouse'))->withInput();
        }

        // Trouver ou créer le fournisseur "Warehouse (Factory)"
        $factorySupplier = Supplier::firstOrCreate(
            ['name' => 'Warehouse (Factory)'],
            ['address' => 'Internal - Factory Production', 'type' => 'consignment']
        );

        DB::transaction(function () use ($data, $recipe, $warehouse, $request) {
            // Créer la production
            $production = Production::create([
                'recipe_id' => $data['recipe_id'],
                'quantity_produced' => $data['quantity_produced'],
                'produced_at' => $data['produced_at'],
                'batch_number' => $data['batch_number'] ?? Production::generateBatchNumber(),
                'status' => Production::STATUS_COMPLETED,
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);

            // Enregistrer les consommations et décrémenter le stock
            foreach ($data['consumptions'] as $consumption) {
                $rawMaterial = RawMaterial::find($consumption['raw_material_id']);
                $quantityConsumed = $consumption['quantity_consumed'];

                // Créer l'enregistrement de consommation
                ProductionConsumption::create([
                    'production_id' => $production->id,
                    'raw_material_id' => $consumption['raw_material_id'],
                    'quantity_consumed' => $quantityConsumed,
                ]);

                // Décrémenter le stock si la matière est gérée en stock
                if ($rawMaterial && $rawMaterial->track_stock && $quantityConsumed > 0) {
                    $rawMaterial->removeStock($quantityConsumed, $production, auth()->user());
                }
            }

            // Créer le stock batch pour le produit fini dans le warehouse
            StockBatch::create([
                'product_id' => $recipe->product_id,
                'store_id' => $warehouse->id,
                'quantity' => $data['quantity_produced'],
                'unit_price' => 0, // Pas de calcul de coût pour le moment
                'source_production_id' => $production->id,
            ]);
        });

        return redirect()->route('factory.productions.index')
            ->with('success', __('messages.factory.production_created'));
    }

    public function show(Production $production)
    {
        $production->load([
            'recipe.product',
            'consumptions.rawMaterial',
            'user',
            'stockBatches.store',
        ]);

        return view('factory.productions.show', compact('production'));
    }

    public function destroy(Production $production)
    {
        // On ne peut supprimer que les productions récentes (moins de 24h) et si le stock n'a pas été utilisé
        if ($production->created_at->diffInHours(now()) > 24) {
            return back()->with('error', __('messages.factory.production_too_old'));
        }

        // Vérifier si le stock a été utilisé
        $stockBatch = $production->stockBatches()->first();
        if ($stockBatch && $stockBatch->quantity < $production->quantity_produced) {
            return back()->with('error', __('messages.factory.production_stock_used'));
        }

        DB::transaction(function () use ($production) {
            // Restaurer le stock des matières premières
            foreach ($production->consumptions as $consumption) {
                $rawMaterial = $consumption->rawMaterial;
                if ($rawMaterial && $rawMaterial->track_stock) {
                    $rawMaterial->addStock(
                        $consumption->quantity_consumed,
                        0,
                        ['notes' => 'Restauration suite à annulation production #' . $production->id],
                        auth()->user()
                    );
                }
            }

            // Supprimer le stock batch créé
            $production->stockBatches()->delete();

            // Supprimer la production
            $production->delete();
        });

        return redirect()->route('factory.productions.index')
            ->with('success', __('messages.common.deleted'));
    }
}
