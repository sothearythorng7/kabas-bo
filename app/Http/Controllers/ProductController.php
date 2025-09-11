<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Store;
use App\Models\ProductImage;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('brand', 'stores');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('ean', 'like', "%{$q}%")
                    ->orWhere('name->fr', 'like', "%{$q}%")
                    ->orWhere('name->en', 'like', "%{$q}%");
            });
        }

        $products = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $brands     = Brand::orderBy('name')->get();
        $suppliers  = Supplier::orderBy('name')->get();
        $stores     = Store::orderBy('name')->get();
        $allCategories = Category::with('parent')->orderBy('id')->get();
        $categoryOptions = $this->buildCategoryPathOptions();
        $locales = config('app.website_locales', ['en']);

        return view('products.create', compact(
            'brands',
            'suppliers',
            'stores',
            'categoryOptions',
            'locales',
            'allCategories'
        ));
    }

    public function store(Request $request)
    {
        $locales = config('app.website_locales', ['en']);

        $data = $request->validate([
            'ean' => 'required|string|unique:products,ean',
            'price' => 'nullable|numeric',
            'price_btob' => 'nullable|numeric',
            'brand_id' => 'nullable|exists:brands,id',
            'color' => 'nullable|string',
            'size' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'is_best_seller' => 'sometimes|boolean',
            'name' => 'required|array',
            'name.*' => 'required|string',
        ]);

        $slugs = [];
        foreach ($data['name'] as $locale => $name) {
            $slugs[$locale] = Str::slug($name);
        }

        $product = null;

        DB::transaction(function () use ($data, $slugs, &$product) {
            $product = Product::create([
                'ean' => $data['ean'],
                'name' => $data['name'],
                'slugs' => $slugs,
                'price' => $data['price'] ?? 0,
                'price_btob' => $data['price_btob'] ?? 0,
                'brand_id' => $data['brand_id'] ?? null,
                'color' => $data['color'] ?? null,
                'size' => $data['size'] ?? null,
                'is_active' => $data['is_active'] ?? false,
                'is_best_seller' => $data['is_best_seller'] ?? false,
                'is_resalable' => false
            ]);

            $stores = Store::all();
            $syncData = [];
            foreach ($stores as $store) {
                $syncData[$store->id] = [
                    'stock_quantity' => 0,
                    'alert_stock_quantity' => 0
                ];
            }
            $product->stores()->attach($syncData);
        });

        return redirect()->route('products.edit', $product)
                        ->with('success', __('messages.common.created'));
    }

    public function edit(Product $product)
    {
        $product->load(['categories', 'suppliers', 'stores', 'images']);
        $allCategories = Category::all();
        $allSuppliers  = Supplier::all();
        $stores        = Store::all();
        $brands        = Brand::all();

        $supplierPivot = $product->suppliers
            ->pluck('pivot.purchase_price', 'id')
            ->toArray();

        $storePivot = $product->stores
            ->mapWithKeys(fn($s) => [$s->id => [
                'stock_quantity' => $s->pivot->stock_quantity,
                'alert_stock_quantity' => $s->pivot->alert_stock_quantity
            ]])
            ->toArray();

        return view('products.edit', compact(
            'product',
            'allCategories',
            'allSuppliers',
            'stores',
            'brands',
            'supplierPivot',
            'storePivot'
        ));
    }

    public function update(Request $request, Product $product)
    {
        $locales = config('app.website_locales', ['en']);

        $data = $request->validate([
            'ean' => 'required|string|unique:products,ean,'.$product->id,
            'name' => 'required|array',
            'name.*' => 'required|string',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'price_btob' => 'nullable|numeric|min:0',
            'brand_id' => 'nullable|exists:brands,id',
            'color' => 'nullable|string',
            'size'  => 'nullable|string',
            'is_active' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_resalable' => 'sometimes|boolean',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'suppliers' => 'nullable|array',
            'suppliers.*.id' => 'exists:suppliers,id',
            'suppliers.*.purchase_price' => 'nullable|numeric|min:0',
            'stores' => 'nullable|array',
            'stores.*.id' => 'exists:stores,id',
            'stores.*.stock_quantity' => 'nullable|integer|min:0',
            'stores.*.alert_stock_quantity' => 'nullable|integer|min:0',
            'photos.*' => 'nullable|image|max:4096',
            'primary_image_id' => 'nullable|integer',
            'primary_image_index' => 'nullable|integer',
        ]);

        $slugs = [];
        foreach ($locales as $locale) {
            $slugs[$locale] = Str::slug($data['name'][$locale] ?? '');
        }
        $data['slugs'] = $slugs;

        DB::transaction(function () use ($request, $product, $data) {
            $product->update([
                'ean'            => $data['ean'],
                'name'           => $data['name'],
                'description'    => $data['description'] ?? [],
                'slugs'          => $data['slugs'],
                'price'          => $data['price'],
                'price_btob'     => $data['price_btob'],
                'brand_id'       => $data['brand_id'] ?? null,
                'color'          => $data['color'] ?? null,
                'size'           => $data['size'] ?? null,
                'is_active'      => $data['is_active'] ?? false,
                'is_resalable'   => $data['is_resalable'] ?? false,
                'is_best_seller' => $data['is_best_seller'] ?? false,
            ]);

            $product->categories()->sync($data['categories'] ?? []);

            $syncSup = [];
            foreach (($data['suppliers'] ?? []) as $sup) {
                if (!empty($sup['id'])) {
                    $syncSup[$sup['id']] = ['purchase_price' => $sup['purchase_price'] ?? null];
                }
            }
            $product->suppliers()->sync($syncSup);

            $syncStores = [];
            foreach (($data['stores'] ?? []) as $st) {
                if (!empty($st['id'])) {
                    $syncStores[$st['id']] = [
                        'stock_quantity' => $st['stock_quantity'] ?? 0,
                        'alert_stock_quantity' => $st['alert_stock_quantity'] ?? 0,
                    ];
                }
            }
            $product->stores()->sync($syncStores);

            $files = $request->file('photos', []);
            $startIndex = $product->images()->max('sort_order') + 1;
            $newPrimaryIndex = $request->input('primary_image_index', null);

            foreach ($files as $i => $file) {
                $path = $file->store('products', 'public');
                $product->images()->create([
                    'path' => $path,
                    'is_primary' => false,
                    'sort_order' => $startIndex + $i,
                ]);
            }

            $primaryImageId = $request->input('primary_image_id');
            if ($primaryImageId) {
                $product->images()->update(['is_primary' => false]);
                $img = $product->images()->where('id', $primaryImageId)->first();
                if ($img) $img->update(['is_primary' => true]);
            } elseif ($newPrimaryIndex !== null && count($files) > 0) {
                $product->images()->update(['is_primary' => false]);
                $target = $product->images()->orderBy('sort_order')->skip($startIndex + (int)$newPrimaryIndex)->first();
                ($target ?? $product->images()->orderBy('sort_order')->first())
                    ?->update(['is_primary' => true]);
            } else {
                if (!$product->images()->where('is_primary', true)->exists()) {
                    $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
                }
            }
        });

        return redirect()->route('products.index')->with('success', __('messages.common.updated'));
    }

    public function destroy(Product $product)
    {
        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->path);
        }
        $product->delete();
        return back()->with('success', __('messages.common.deleted'));
    }

    public function buildCategoryPathOptions(): array
    {
        $all = Category::with('children','parent')->get();
        $map = [];
        $makePath = function ($cat) use (&$makePath, &$map) {
            if (isset($map[$cat->id])) return $map[$cat->id];
            $name = $cat->translation()?->name ?? ('#'.$cat->id);
            if ($cat->parent) {
                $map[$cat->id] = $makePath($cat->parent) . ' > ' . $name;
            } else {
                $map[$cat->id] = $name;
            }
            return $map[$cat->id];
        };
        $options = [];
        foreach ($all as $cat) {
            $options[$cat->id] = $makePath($cat);
        }
        asort($options, SORT_NATURAL | SORT_FLAG_CASE);
        return $options;
    }

    public function attachCategory(Request $request, Product $product)
    {
        $request->validate(['category_id' => 'required|exists:categories,id']);
        $product->categories()->syncWithoutDetaching([$request->category_id]);
        return back()->with('success', 'Category added.')->withFragment('tab-categories');
    }

    public function detachCategory(Product $product, Category $category)
    {
        $product->categories()->detach($category->id);
        return back()->with('success', 'Category removed.')->withFragment('tab-categories');
    }

    public function attachSupplier(Request $request, Product $product)
    {
        $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'purchase_price' => 'required|numeric|min:0',
        ]);
        $product->suppliers()->syncWithoutDetaching([ $request->supplier_id => ['purchase_price' => $request->purchase_price] ]);
        return back()->with('success', 'Supplier added.')->withFragment('tab-suppliers');
    }

    public function detachSupplier(Product $product, Supplier $supplier)
    {
        $product->suppliers()->detach($supplier->id);
        return back()->with('success', 'Supplier removed.')->withFragment('tab-suppliers');
    }

    public function updateSupplierPrice(Request $request, Product $product, Supplier $supplier)
    {
        $request->validate(['purchase_price' => 'required|numeric|min:0']);
        $product->suppliers()->updateExistingPivot($supplier->id, ['purchase_price' => $request->purchase_price]);
        return back()->with('success', 'Supplier price updated.')->withFragment('tab-suppliers');
    }

    public function updateStoreStock(Request $request, Product $product, Store $store)
    {
        $request->validate([
            'stock_quantity' => 'required|integer|min:0',
            'alert_stock_quantity' => 'nullable|integer|min:0',
        ]);

        $newQuantity = $request->stock_quantity;
        $alertQuantity = $request->alert_stock_quantity ?? 0;

        DB::transaction(function() use ($product, $store, $newQuantity, $alertQuantity) {
            $currentStock = $product->stockBatches()
                ->where('store_id', $store->id)
                ->sum('quantity');

            $difference = $newQuantity - $currentStock;

            if ($difference != 0) {
                if ($difference > 0) {
                    StockBatch::create([
                        'product_id' => $product->id,
                        'store_id'   => $store->id,
                        'quantity'   => $difference,
                        'label'      => 'Ajustement manuel',
                    ]);
                } else {
                    $remainingToDeduct = abs($difference);
                    $batches = $product->stockBatches()
                        ->where('store_id', $store->id)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    foreach ($batches as $batch) {
                        if ($remainingToDeduct <= 0) break;
                        $deduct = min($batch->quantity, $remainingToDeduct);
                        $batch->quantity -= $deduct;
                        $batch->save();
                        $remainingToDeduct -= $deduct;
                    }
                }

                $movement = StockMovement::create([
                    'type'    => StockMovement::TYPE_ADJUSTMENT,
                    'note'    => 'Ajustement manuel',
                    'user_id' => auth()->id(),
                    'status'  => StockMovement::STATUS_VALIDATED,
                    'to_store_id' => $difference > 0 ? $store->id : null,
                    'from_store_id' => $difference < 0 ? $store->id : null,
                ]);

                $movement->items()->create([
                    'product_id' => $product->id,
                    'quantity'   => $difference,
                ]);
            }

            $product->stores()->syncWithoutDetaching([
                $store->id => ['alert_stock_quantity' => $alertQuantity]
            ]);
        });

        return back()->with('success', 'Store stock updated.')->withFragment('tab-stores');
    }

    public function removeStock(Store $store, int $quantity): bool
    {
        $batches = $this->stockBatches()
            ->where('store_id', $store->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $toDeduct = min($batch->quantity, $remaining);
            $batch->quantity -= $toDeduct;
            $batch->save();
            $remaining -= $toDeduct;
        }

        $totalStock = $this->stockBatches()
            ->where('store_id', $store->id)
            ->sum('quantity');

        $store->products()->syncWithoutDetaching([
            $this->id => ['stock_quantity' => $totalStock]
        ]);

        return $remaining === 0;
    }
}
