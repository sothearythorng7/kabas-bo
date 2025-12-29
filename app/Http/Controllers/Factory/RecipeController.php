<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\RawMaterial;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $query = Recipe::with(['product', 'items.rawMaterial']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%$q%")
                    ->orWhereHas('product', function ($pq) use ($q) {
                        $pq->where('name->fr', 'like', "%$q%")
                            ->orWhere('name->en', 'like', "%$q%")
                            ->orWhere('ean', 'like', "%$q%");
                    });
            });
        }

        $recipes = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('factory.recipes.index', compact('recipes'));
    }

    public function create()
    {
        $materials = RawMaterial::active()->orderBy('name')->get();

        return view('factory.recipes.create', compact('materials'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_id' => 'required|exists:products,id',
            'instructions' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.is_optional' => 'sometimes|boolean',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data, $request) {
            $recipe = Recipe::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'product_id' => $data['product_id'],
                'instructions' => $data['instructions'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            foreach ($data['items'] as $item) {
                $recipe->items()->create([
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity' => $item['quantity'],
                    'is_optional' => $item['is_optional'] ?? false,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            // Associer automatiquement le produit au fournisseur Warehouse (Factory)
            $this->linkProductToWarehouseSupplier($data['product_id']);
        });

        return redirect()->route('factory.recipes.index')
            ->with('success', __('messages.common.created'));
    }

    public function show(Recipe $recipe)
    {
        $recipe->load(['product', 'items.rawMaterial', 'productions' => function ($q) {
            $q->latest()->limit(10);
        }]);

        return view('factory.recipes.show', compact('recipe'));
    }

    public function edit(Recipe $recipe)
    {
        $recipe->load(['product', 'items.rawMaterial']);
        $materials = RawMaterial::active()->orderBy('name')->get();

        return view('factory.recipes.edit', compact('recipe', 'materials'));
    }

    public function update(Request $request, Recipe $recipe)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_id' => 'required|exists:products,id',
            'instructions' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:recipe_items,id',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.is_optional' => 'sometimes|boolean',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($recipe, $data, $request) {
            $recipe->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'product_id' => $data['product_id'],
                'instructions' => $data['instructions'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Collecter les IDs des items à conserver
            $keepIds = collect($data['items'])->pluck('id')->filter()->toArray();

            // Supprimer les items qui ne sont plus dans la liste
            $recipe->items()->whereNotIn('id', $keepIds)->delete();

            // Mettre à jour ou créer les items
            foreach ($data['items'] as $item) {
                if (!empty($item['id'])) {
                    RecipeItem::where('id', $item['id'])->update([
                        'raw_material_id' => $item['raw_material_id'],
                        'quantity' => $item['quantity'],
                        'is_optional' => $item['is_optional'] ?? false,
                        'notes' => $item['notes'] ?? null,
                    ]);
                } else {
                    $recipe->items()->create([
                        'raw_material_id' => $item['raw_material_id'],
                        'quantity' => $item['quantity'],
                        'is_optional' => $item['is_optional'] ?? false,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            // Associer automatiquement le produit au fournisseur Warehouse (Factory)
            $this->linkProductToWarehouseSupplier($data['product_id']);
        });

        return redirect()->route('factory.recipes.index')
            ->with('success', __('messages.common.updated'));
    }

    public function destroy(Recipe $recipe)
    {
        if ($recipe->productions()->exists()) {
            return back()->with('error', __('messages.factory.recipe_has_productions'));
        }

        $recipe->delete();

        return redirect()->route('factory.recipes.index')
            ->with('success', __('messages.common.deleted'));
    }

    /**
     * Calcule la quantité max productible
     */
    public function maxProducible(Recipe $recipe)
    {
        $recipe->load('items.rawMaterial');

        return response()->json([
            'max_producible' => $recipe->maxProducible(),
            'can_produce' => $recipe->canProduce(1),
        ]);
    }

    /**
     * Cloner une recette existante
     */
    public function clone(Recipe $recipe)
    {
        $clonedRecipe = DB::transaction(function () use ($recipe) {
            // Créer la copie de la recette
            $newRecipe = Recipe::create([
                'name' => 'COPY - ' . $recipe->name,
                'description' => $recipe->description,
                'product_id' => $recipe->product_id,
                'instructions' => $recipe->instructions,
                'is_active' => $recipe->is_active,
            ]);

            // Copier tous les items (raw materials)
            foreach ($recipe->items as $item) {
                $newRecipe->items()->create([
                    'raw_material_id' => $item->raw_material_id,
                    'quantity' => $item->quantity,
                    'is_optional' => $item->is_optional,
                    'notes' => $item->notes,
                ]);
            }

            return $newRecipe;
        });

        return redirect()->route('factory.recipes.edit', $clonedRecipe)
            ->with('success', __('messages.factory.recipe_cloned'));
    }

    /**
     * Associe un produit au fournisseur Warehouse (Factory)
     */
    protected function linkProductToWarehouseSupplier(int $productId): void
    {
        $warehouseSupplier = Supplier::firstOrCreate(
            ['name' => 'Warehouse (Factory)'],
            ['address' => 'Internal - Factory Production', 'type' => 'buyer']
        );

        // Associer le produit s'il ne l'est pas déjà
        $warehouseSupplier->products()->syncWithoutDetaching([
            $productId => ['purchase_price' => 0]
        ]);
    }
}
