<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use App\Models\RawMaterialStockBatch;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RawMaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = RawMaterial::with('supplier')
            ->withSum('stockBatches as total_stock', 'quantity');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('track_stock')) {
            $query->where('track_stock', $request->track_stock === '1');
        }

        if ($request->filled('low_stock')) {
            $query->whereHas('stockBatches', function ($q) {
                $q->havingRaw('SUM(quantity) <= raw_materials.alert_quantity');
            });
        }

        $materials = $query->orderBy('name')->paginate(20)->withQueryString();
        $suppliers = Supplier::active()->rawMaterialSuppliers()->orderBy('name')->get();

        return view('factory.raw-materials.index', compact('materials', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::active()->rawMaterialSuppliers()->orderBy('name')->get();
        $units = $this->getUnits();

        return view('factory.raw-materials.create', compact('suppliers', 'units'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:raw_materials,sku',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'track_stock' => 'sometimes|boolean',
            'alert_quantity' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['track_stock'] = $request->has('track_stock');
        $data['is_active'] = $request->has('is_active');

        RawMaterial::create($data);

        return redirect()->route('factory.raw-materials.index')
            ->with('success', __('messages.common.created'));
    }

    public function edit(RawMaterial $rawMaterial)
    {
        $rawMaterial->load(['supplier', 'stockBatches' => function ($q) {
            $q->where('quantity', '>', 0)->orderBy('received_at');
        }]);

        $suppliers = Supplier::active()->rawMaterialSuppliers()->orderBy('name')->get();
        $units = $this->getUnits();

        return view('factory.raw-materials.edit', compact('rawMaterial', 'suppliers', 'units'));
    }

    public function update(Request $request, RawMaterial $rawMaterial)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:raw_materials,sku,' . $rawMaterial->id,
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'track_stock' => 'sometimes|boolean',
            'alert_quantity' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['track_stock'] = $request->has('track_stock');
        $data['is_active'] = $request->has('is_active');

        $rawMaterial->update($data);

        return redirect()->route('factory.raw-materials.index')
            ->with('success', __('messages.common.updated'));
    }

    public function destroy(RawMaterial $rawMaterial)
    {
        if ($rawMaterial->recipeItems()->exists()) {
            return back()->with('error', __('messages.factory.material_used_in_recipes'));
        }

        $rawMaterial->delete();

        return redirect()->route('factory.raw-materials.index')
            ->with('success', __('messages.common.deleted'));
    }

    /**
     * Ajouter du stock
     */
    public function addStock(Request $request, RawMaterial $rawMaterial)
    {
        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'nullable|numeric|min:0',
            'received_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:received_at',
            'batch_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $rawMaterial->addStock(
            $data['quantity'],
            $data['unit_price'] ?? 0,
            $data,
            auth()->user()
        );

        return back()->with('success', __('messages.factory.stock_added'));
    }

    /**
     * Ajuster le stock (correction)
     */
    public function adjustStock(Request $request, RawMaterial $rawMaterial)
    {
        $data = $request->validate([
            'batch_id' => 'required|exists:raw_material_stock_batches,id',
            'new_quantity' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $batch = RawMaterialStockBatch::findOrFail($data['batch_id']);

        if ($batch->raw_material_id !== $rawMaterial->id) {
            abort(403);
        }

        $difference = $data['new_quantity'] - $batch->quantity;

        DB::transaction(function () use ($rawMaterial, $batch, $data, $difference) {
            $batch->update(['quantity' => $data['new_quantity']]);

            if ($rawMaterial->track_stock && $difference != 0) {
                $rawMaterial->stockMovements()->create([
                    'raw_material_stock_batch_id' => $batch->id,
                    'quantity' => $difference,
                    'type' => 'adjustment',
                    'notes' => $data['reason'] ?? null,
                    'user_id' => auth()->id(),
                ]);
            }
        });

        return back()->with('success', __('messages.factory.stock_adjusted'));
    }

    /**
     * Liste des unités disponibles
     */
    protected function getUnits(): array
    {
        return [
            'unit' => __('messages.factory.unit_types.unit'),
            'kg' => __('messages.factory.unit_types.kg'),
            'g' => __('messages.factory.unit_types.g'),
            'l' => __('messages.factory.unit_types.l'),
            'ml' => __('messages.factory.unit_types.ml'),
            'm' => __('messages.factory.unit_types.m'),
            'cm' => __('messages.factory.unit_types.cm'),
            'box' => __('messages.factory.unit_types.box'),
            'pack' => __('messages.factory.unit_types.pack'),
        ];
    }

    /**
     * Recherche AJAX pour les recettes
     */
    public function search(Request $request)
    {
        $q = $request->q;

        $materials = RawMaterial::active()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%$q%")
                    ->orWhere('sku', 'like', "%$q%");
            })
            ->limit(20)
            ->get(['id', 'name', 'sku', 'unit', 'track_stock']);

        return response()->json($materials);
    }

    /**
     * Cloner une matière première existante
     */
    public function clone(RawMaterial $rawMaterial)
    {
        // Générer un SKU fake unique
        do {
            $fakeSku = 'FAKE-' . mt_rand(10000000, 99999999);
        } while (RawMaterial::where('sku', $fakeSku)->exists());

        $clonedMaterial = RawMaterial::create([
            'name' => 'COPY - ' . $rawMaterial->name,
            'sku' => $fakeSku,
            'description' => $rawMaterial->description,
            'unit' => $rawMaterial->unit,
            'track_stock' => $rawMaterial->track_stock,
            'alert_quantity' => $rawMaterial->alert_quantity,
            'supplier_id' => $rawMaterial->supplier_id,
            'is_active' => $rawMaterial->is_active,
        ]);

        return redirect()->route('factory.raw-materials.edit', $clonedMaterial)
            ->with('success', __('messages.factory.material_cloned'));
    }
}
