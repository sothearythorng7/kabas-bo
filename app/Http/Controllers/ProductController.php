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
use App\Models\StockTransaction;
use App\Models\ProductVariationAttribute;
use App\Models\VariationType;
use App\Models\VariationValue;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 100);
        $perPage = in_array($perPage, [25, 50, 100]) ? $perPage : 100;

        // Si recherche textuelle, utiliser Meilisearch via Scout
        if ($request->filled('q')) {
            $searchQuery = Product::search($request->q)
                ->query(function ($builder) use ($request) {
                    $builder->with('brand', 'stores')->withCount('images');
                });

            // Filtre par marque si nécessaire
            if ($request->filled('brand_id')) {
                if ($request->brand_id === 'none') {
                    $searchQuery->where('brand_id', null);
                } else {
                    $searchQuery->where('brand_id', $request->brand_id);
                }
            }

            $products = $searchQuery->paginate($perPage)->withQueryString();
        } else {
            // Pas de recherche : requête SQL classique
            $query = Product::with('brand', 'stores')->withCount('images');

            if ($request->filled('brand_id')) {
                if ($request->brand_id === 'none') {
                    $query->whereNull('brand_id');
                } else {
                    $query->where('brand_id', $request->brand_id);
                }
            }

            $products = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
        }

        $brands = Brand::orderBy('name')->get();

        return view('products.index', compact('products', 'brands'));
    }

    public function checkEan(Request $request)
    {
        $ean = $request->query('ean');
        $exists = Product::where('ean', $ean)->exists();

        return response()->json(['exists' => $exists]);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function create()
    {
        $brands     = Brand::orderBy('name')->get();
        $suppliers  = Supplier::orderBy('name')->get();
        $stores     = Store::orderBy('name')->get();
        $categoryOptions = $this->buildCategoryPathOptions();
        $locales = config('app.website_locales', ['en']);

        return view('products.create', compact(
            'brands',
            'suppliers',
            'stores',
            'categoryOptions',
            'locales'
        ));
    }

public function store(Request $request)
{
    $locales = config('app.website_locales', ['en']);

    $data = $request->validate([
        'ean' => 'required|string|unique:products,ean',
        'price' => 'nullable|numeric',
        'price_btob' => 'nullable|numeric',
        'shipping_weight' => 'nullable|integer|min:0',
        'brand_id' => 'nullable|exists:brands,id',
        'is_active' => 'sometimes|boolean',
        'is_active_pos' => 'sometimes|boolean',
        'is_best_seller' => 'sometimes|boolean',
        'name' => 'required|array',
        'name.en' => 'required|string',
        'name.*' => 'nullable|string',
    ]);

    $product = null;

    DB::transaction(function () use ($data, $locales, &$product) {
        // Préparer les traductions pour name et slugs
        $nameTranslations = [];
        $slugsTranslations = [];
        foreach ($locales as $locale) {
            $nameTranslations[$locale] = $data['name'][$locale] ?? '';
            $slugsTranslations[$locale] = Str::slug($data['name'][$locale] ?? '');
        }

        $product = Product::create([
            'ean' => $data['ean'],
            'name' => $nameTranslations,
            'slugs' => $slugsTranslations,
            'price' => $data['price'] ?? 0,
            'price_btob' => $data['price_btob'] ?? 0,
            'shipping_weight' => $data['shipping_weight'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'is_active' => $data['is_active'] ?? false,
            'is_active_pos' => $data['is_active_pos'] ?? true,
            'is_best_seller' => $data['is_best_seller'] ?? false,
            'is_resalable' => false,
            'allow_overselling' => $data['allow_overselling'] ?? false,
        ]);

        // Attacher tous les magasins avec alerte de stock par défaut
        $stores = Store::all();
        $syncData = [];
        foreach ($stores as $store) {
            $syncData[$store->id] = [
                'alert_stock_quantity' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $product->stores()->attach($syncData);
    });

    return redirect()->route('products.edit', $product)
                     ->with('success', __('messages.common.created'));
}


    public function edit(Product $product)
    {
        $product->load(['categories', 'suppliers', 'stores', 'images', 'stockBatches']);
        $allCategories = Category::all();
        $allSuppliers  = Supplier::all();
        $stores        = Store::all();
        $brands        = Brand::all();
        $types          = VariationType::with('values')->get();

        $supplierPivot = $product->suppliers
            ->pluck('pivot.purchase_price', 'id')
            ->toArray();

        $storePivot = $product->stores
            ->mapWithKeys(fn($s) => [$s->id => [
                'stock_quantity' => $s->pivot->stock_quantity,
                'alert_stock_quantity' => $s->pivot->alert_stock_quantity
            ]])
            ->toArray();

        // Calcul des alertes produit
        $productAlerts = $this->getProductAlerts($product);

        // Variation group members
        $groupProducts = collect();
        if ($product->variation_group_id) {
            $product->load(['variationGroup', 'variationAttributes.type', 'variationAttributes.value']);
            $groupProducts = $product->variationGroup->products()
                ->with(['variationAttributes.type', 'variationAttributes.value'])
                ->orderBy('id')
                ->get();
        }

        return view('products.edit', compact(
            'product',
            'allCategories',
            'allSuppliers',
            'stores',
            'brands',
            'supplierPivot',
            'storePivot',
            'types',
            'productAlerts',
            'groupProducts'
        ));
    }

    /**
     * Calcule les alertes pour un produit donné
     */
    private function getProductAlerts(Product $product): array
    {
        $alerts = [];

        // Sans images
        if ($product->images->isEmpty()) {
            $alerts[] = [
                'type' => 'no_image',
                'icon' => 'bi-image',
                'color' => 'warning',
                'message' => __('messages.product_alerts.no_image'),
            ];
        }

        // Sans description FR
        $descFr = $product->description['fr'] ?? '';
        if (empty(trim($descFr))) {
            $alerts[] = [
                'type' => 'no_description_fr',
                'icon' => 'bi-file-text',
                'color' => 'danger',
                'message' => __('messages.product_alerts.no_description_fr'),
            ];
        }

        // Sans description EN
        $descEn = $product->description['en'] ?? '';
        if (empty(trim($descEn))) {
            $alerts[] = [
                'type' => 'no_description_en',
                'icon' => 'bi-file-text',
                'color' => 'info',
                'message' => __('messages.product_alerts.no_description_en'),
            ];
        }

        // Hors stock
        $totalStock = $product->stockBatches->sum('quantity');
        if ($totalStock == 0) {
            $alerts[] = [
                'type' => 'out_of_stock',
                'icon' => 'bi-box-seam',
                'color' => 'danger',
                'message' => __('messages.product_alerts.out_of_stock'),
            ];
        }

        // EAN fake ou vide
        if (empty($product->ean) || str_starts_with($product->ean, 'FAKE-')) {
            $alerts[] = [
                'type' => 'fake_or_empty_ean',
                'icon' => 'bi-upc-scan',
                'color' => 'warning',
                'message' => __('messages.product_alerts.fake_or_empty_ean'),
            ];
        }

        // Sans catégorie
        if ($product->categories->isEmpty()) {
            $alerts[] = [
                'type' => 'no_category',
                'icon' => 'bi-bookmarks',
                'color' => 'primary',
                'message' => __('messages.product_alerts.no_category'),
            ];
        }

        return $alerts;
    }

public function update(Request $request, Product $product)
{
    $locales = config('app.website_locales', ['en']);

    $data = $request->validate([
        'ean' => 'required|string|unique:products,ean,'.$product->id,
        'name' => 'required|array',
        'name.en' => 'required|string',
        'name.*' => 'nullable|string',
        'description' => 'nullable|array',
        'description.*' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'price_btob' => 'nullable|numeric|min:0',
        'shipping_weight' => 'nullable|integer|min:0',
        'brand_id' => 'nullable|exists:brands,id',
        'is_active' => 'boolean',
        'is_active_pos' => 'boolean',
        'is_best_seller' => 'boolean',
        'is_resalable' => 'sometimes|boolean',
        'allow_overselling' => 'sometimes|boolean',
        'gender' => 'nullable|in:male,female,unisex',
        'age_group' => 'nullable|in:adult,kids,toddler,infant,newborn',
        'categories' => 'nullable|array',
        'categories.*' => 'exists:categories,id',
        'suppliers' => 'nullable|array',
        'suppliers.*.id' => 'exists:suppliers,id',
        'suppliers.*.purchase_price' => 'nullable|numeric|min:0',
        'stores' => 'nullable|array',
        'stores.*.id' => 'exists:stores,id',
        'stores.*.stock_quantity' => 'nullable|integer|min:0',
        'stores.*.alert_stock_quantity' => 'nullable|integer|min:0',
    ]);

    DB::transaction(function () use ($product, $data, $locales) {
        $product->update([
            'ean' => $data['ean'],
            'price' => $data['price'],
            'price_btob' => $data['price_btob'],
            'shipping_weight' => $data['shipping_weight'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'is_active' => $data['is_active'] ?? false,
            'is_active_pos' => $data['is_active_pos'] ?? true,
            'is_resalable' => $data['is_resalable'] ?? false,
            'is_best_seller' => $data['is_best_seller'] ?? false,
            'allow_overselling' => $data['allow_overselling'] ?? false,
            'gender' => $data['gender'] ?? null,
            'age_group' => $data['age_group'] ?? null,
        ]);

        foreach ($locales as $locale) {
            $product->setTranslation('name', $locale, $data['name'][$locale] ?? '');
            $product->setTranslation('slugs', $locale, Str::slug($data['name'][$locale] ?? ''));
            // Only update description if explicitly provided in the request
            if (isset($data['description'])) {
                $product->setTranslation('description', $locale, $data['description'][$locale] ?? '');
            }
        }

        $product->save();

        /*
        // Catégories, suppliers et stores
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
        */
    });

    return redirect()->back()->with('success', __('messages.common.updated'));
}

    public function updateDescriptions(Request $request, Product $product)
    {
        $locales = config('app.website_locales', ['en']);

        $data = $request->validate([
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
        ]);

        foreach ($locales as $locale) {
            $product->setTranslation('description', $locale, $data['description'][$locale] ?? '');
        }

        $product->save();

        return redirect()->back()->with('success', __('messages.common.updated'));
    }

    public function updateSeo(Request $request, Product $product)
    {
        $locales = config('app.website_locales', ['en']);

        $request->validate([
            'seo_title' => 'nullable|array',
            'seo_title.*' => 'nullable|string|max:70',
            'meta_description' => 'nullable|array',
            'meta_description.*' => 'nullable|string|max:160',
        ]);

        $seoTitle = $product->seo_title ?? [];
        $metaDesc = $product->meta_description ?? [];

        foreach ($locales as $locale) {
            $seoTitle[$locale] = $request->input("seo_title.$locale", '');
            $metaDesc[$locale] = $request->input("meta_description.$locale", '');
        }

        $product->update([
            'seo_title' => $seoTitle,
            'meta_description' => $metaDesc,
        ]);

        return redirect()->back()->with('success', __('messages.common.updated'))->withFragment('tab-seo');
    }

    public function uploadPhotos(Request $request, Product $product)
    {
        $request->validate([
            'photos.*' => 'required|image|max:4096',
        ]);

        $startIndex = $product->images()->max('sort_order') + 1;

        foreach ($request->file('photos', []) as $i => $file) {
            $path = $file->store('products', 'public');
            $product->images()->create([
                'path' => $path,
                'is_primary' => false,
                'sort_order' => $startIndex + $i,
            ]);
        }

        if (!$product->images()->where('is_primary', true)->exists()) {
            $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', __('messages.product.photos_uploaded'))->withFragment('tab-photos');
    }

    public function deletePhoto(Product $product, ProductImage $photo)
    {
        if ($photo->product_id != $product->id) {
            abort(404);
        }

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        if ($photo->is_primary) {
            $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', __('messages.product.photo_deleted'))->withFragment('tab-photos');
    }

    /**
     * Inline-update a single field via AJAX.
     */
    public function toggleField(Request $request, Product $product)
    {
        $data = $request->validate([
            'field' => 'required|in:is_active,is_active_pos,is_best_seller,is_resalable,shipping_weight',
            'value' => 'required',
        ]);

        $field = $data['field'];
        $value = $data['value'];

        if (in_array($field, ['is_active', 'is_active_pos', 'is_best_seller', 'is_resalable'])) {
            $product->update([$field => (bool) $value]);
        } elseif ($field === 'shipping_weight') {
            $product->update([$field => $value !== '' && $value !== null ? max(0, (int) $value) : null]);
        }

        return response()->json(['success' => true]);
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

    // --- Category / Supplier / Store methods remain unchanged ---
    public function attachCategory(Request $request, Product $product)
    {
        $request->validate(['category_id' => 'required|exists:categories,id']);
        $product->categories()->syncWithoutDetaching([$request->category_id]);
        return back()->with('success', __('messages.product.category_added'))->withFragment('tab-categories');
    }

    public function detachCategory(Product $product, Category $category)
    {
        $product->categories()->detach($category->id);
        return back()->with('success', __('messages.product.category_removed'))->withFragment('tab-categories');
    }

    public function attachSupplier(Request $request, Product $product)
    {
        $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'purchase_price' => 'required|numeric|min:0',
        ]);
        $product->suppliers()->syncWithoutDetaching([ $request->supplier_id => ['purchase_price' => $request->purchase_price] ]);
        return back()->with('success', __('messages.product.supplier_added'))->withFragment('tab-suppliers');
    }

    public function detachSupplier(Product $product, Supplier $supplier)
    {
        $product->suppliers()->detach($supplier->id);
        return back()->with('success', __('messages.product.supplier_removed'))->withFragment('tab-suppliers');
    }

    public function updateSupplierPrice(Request $request, Product $product, Supplier $supplier)
    {
        $request->validate(['purchase_price' => 'required|numeric|min:0']);
        $product->suppliers()->updateExistingPivot($supplier->id, ['purchase_price' => $request->purchase_price]);
        return back()->with('success', __('messages.product.supplier_price_updated'))->withFragment('tab-suppliers');
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
                    // Création d'un batch pour l'ajustement positif
                    $batch = StockBatch::create([
                        'product_id' => $product->id,
                        'store_id'   => $store->id,
                        'quantity'   => $difference,
                        'label'      => 'Ajustement manuel',
                    ]);

                    StockTransaction::create([
                        'stock_batch_id' => $batch->id,
                        'store_id'       => $store->id,
                        'product_id'     => $product->id,
                        'type'           => 'in',
                        'quantity'       => $difference,
                        'reason'         => 'manual_adjustment',
                        'user_id'        => auth()->id(),
                    ]);
                } else {
                    // FIFO pour sortie
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

                        StockTransaction::create([
                            'stock_batch_id' => $batch->id,
                            'store_id'       => $store->id,
                            'product_id'     => $product->id,
                            'type'           => 'out',
                            'quantity'       => $deduct,
                            'reason'         => 'manual_adjustment',
                            'user_id'        => auth()->id(),
                        ]);

                        $remainingToDeduct -= $deduct;
                    }
                }
            }

            // Mise à jour de l'alerte
            $product->stores()->syncWithoutDetaching([
                $store->id => ['alert_stock_quantity' => $alertQuantity]
            ]);
        });

        return back()->with('success', __('messages.product.store_stock_updated'))->withFragment('tab-stores');
    }

    public function setPrimaryPhoto(Product $product, ProductImage $photo)
    {
        if ($photo->product_id !== $product->id) {
            abort(404);
        }

        // On met toutes les photos à false
        $product->images()->update(['is_primary' => false]);

        // On met celle sélectionnée en true
        $photo->update(['is_primary' => true]);

        return back()->with('success', __('messages.product.primary_photo_updated'))->withFragment('tab-photos');
    }

    /**
     * Store a new barcode for a product
     */
    public function storeBarcode(Request $request, Product $product)
    {
        $request->validate([
            'barcode' => 'required|string|max:50|unique:product_barcodes,barcode',
            'type' => 'required|string|in:ean13,ean8,upc,internal',
            'is_primary' => 'nullable|boolean',
        ]);

        $barcode = $product->barcodes()->create([
            'barcode' => $request->barcode,
            'type' => $request->type,
            'is_primary' => $request->boolean('is_primary'),
        ]);

        // Si c'est le premier barcode ou s'il est défini comme principal
        if ($request->boolean('is_primary') || $product->barcodes()->count() === 1) {
            $barcode->setAsPrimary();
        }

        return back()->with('success', __('messages.product.barcode_added') ?? 'Code-barre ajouté')->withFragment('tab-barcodes');
    }

    /**
     * Set a barcode as primary
     */
    public function setPrimaryBarcode(Product $product, \App\Models\ProductBarcode $barcode)
    {
        if ($barcode->product_id !== $product->id) {
            abort(404);
        }

        $barcode->setAsPrimary();

        return back()->with('success', __('messages.product.barcode_set_primary') ?? 'Code-barre défini comme principal')->withFragment('tab-barcodes');
    }

    /**
     * Delete a barcode
     */
    public function destroyBarcode(Product $product, \App\Models\ProductBarcode $barcode)
    {
        if ($barcode->product_id !== $product->id) {
            abort(404);
        }

        if ($barcode->is_primary) {
            return back()->with('error', __('messages.product.cannot_delete_primary_barcode') ?? 'Impossible de supprimer le code-barre principal')->withFragment('tab-barcodes');
        }

        $barcode->delete();

        return back()->with('success', __('messages.product.barcode_deleted') ?? 'Code-barre supprimé')->withFragment('tab-barcodes');
    }

    public function variationsIndex(Product $product)
    {
        $product->load(['variationGroup.products.variationAttributes.type', 'variationGroup.products.variationAttributes.value', 'variationAttributes.type', 'variationAttributes.value']);
        $types = \App\Models\VariationType::orderBy('name')->get();

        $groupProducts = collect();
        if ($product->variationGroup) {
            $groupProducts = $product->variationGroup->products()
                ->with(['variationAttributes.type', 'variationAttributes.value'])
                ->orderBy('id')
                ->get();
        }

        return view('products.variations', compact('product', 'types', 'groupProducts'));
    }

    public function variationsStore(Request $request, Product $product)
    {
        $data = $request->validate([
            'linked_product_id'  => 'required|exists:products,id|not_in:'.$product->id,
            'attributes'         => 'required|array|min:1',
            'attributes.*.variation_type_id'  => 'required|exists:variation_types,id',
            'attributes.*.variation_value_id' => 'required|exists:variation_values,id',
        ]);

        DB::transaction(function () use ($data, $product) {
            // Get or create the variation group
            if (!$product->variation_group_id) {
                $group = \App\Models\VariationGroup::create([
                    'name' => $product->name['fr'] ?? $product->name['en'] ?? reset($product->name),
                ]);
                $product->update(['variation_group_id' => $group->id]);

                // If the current product has no attributes, assign the first set of own attributes from user
                // (handled separately via variationsUpdateSelf)
            } else {
                $group = $product->variationGroup;
            }

            // Add the linked product to the group
            $linkedProduct = Product::findOrFail($data['linked_product_id']);
            $linkedProduct->update(['variation_group_id' => $group->id]);

            // Set attributes for the linked product
            // Remove old attributes for this product in this group first
            \App\Models\ProductVariationAttribute::where('product_id', $linkedProduct->id)
                ->where('variation_group_id', $group->id)
                ->delete();

            foreach ($data['attributes'] as $attr) {
                \App\Models\ProductVariationAttribute::create([
                    'product_id'         => $linkedProduct->id,
                    'variation_group_id' => $group->id,
                    'variation_type_id'  => $attr['variation_type_id'],
                    'variation_value_id' => $attr['variation_value_id'],
                ]);
            }
        });

        return back()->with('success', __('messages.product.variation_added'))->withFragment('tab-variations');
    }

    /**
     * Update the current product's own attributes within its group.
     */
    public function variationsUpdateSelf(Request $request, Product $product)
    {
        $data = $request->validate([
            'attributes'         => 'required|array|min:1',
            'attributes.*.variation_type_id'  => 'required|exists:variation_types,id',
            'attributes.*.variation_value_id' => 'required|exists:variation_values,id',
        ]);

        if (!$product->variation_group_id) {
            return back()->with('error', 'Product is not in a variation group.');
        }

        DB::transaction(function () use ($data, $product) {
            \App\Models\ProductVariationAttribute::where('product_id', $product->id)
                ->where('variation_group_id', $product->variation_group_id)
                ->delete();

            foreach ($data['attributes'] as $attr) {
                \App\Models\ProductVariationAttribute::create([
                    'product_id'         => $product->id,
                    'variation_group_id' => $product->variation_group_id,
                    'variation_type_id'  => $attr['variation_type_id'],
                    'variation_value_id' => $attr['variation_value_id'],
                ]);
            }
        });

        return back()->with('success', __('messages.product.variation_updated'))->withFragment('tab-variations');
    }

    /**
     * Remove a product from the variation group.
     */
    public function variationsDestroy(Product $product, $targetProductId)
    {
        $targetProduct = Product::findOrFail($targetProductId);

        if ($targetProduct->variation_group_id !== $product->variation_group_id) {
            return back()->with('error', 'Product is not in the same group.');
        }

        DB::transaction(function () use ($product, $targetProduct) {
            $groupId = $targetProduct->variation_group_id;

            // Remove attributes and unlink from group
            \App\Models\ProductVariationAttribute::where('product_id', $targetProduct->id)
                ->where('variation_group_id', $groupId)
                ->delete();
            $targetProduct->update(['variation_group_id' => null]);

            // If only 1 or 0 products remain, dissolve the group
            $remaining = Product::where('variation_group_id', $groupId)->count();
            if ($remaining <= 1) {
                Product::where('variation_group_id', $groupId)
                    ->update(['variation_group_id' => null]);
                \App\Models\ProductVariationAttribute::where('variation_group_id', $groupId)->delete();
                \App\Models\VariationGroup::where('id', $groupId)->delete();
            }
        });

        return back()->with('success', __('messages.product.variation_removed'))->withFragment('tab-variations');
    }

// Ajax pour récupérer les valeurs d’un type
public function values(VariationType $type)
{
    return response()->json($type->values()->orderBy('label')->get());
}

// Ajax pour rechercher un produit par EAN ou nom
public function search(Request $request)
{
    $q = $request->q;

    // Utiliser Meilisearch si disponible et si une requête existe
    if ($q && config('scout.driver') === 'meilisearch') {
        $products = Product::search($q)
            ->take(20)
            ->get(['id', 'ean', 'name']);
    } else {
        // Fallback vers recherche SQL classique
        $products = Product::where('ean', 'like', "%$q%")
            ->orWhere('name->fr', 'like', "%$q%")
            ->orWhere('name->en', 'like', "%$q%")
            ->limit(20)
            ->get(['id','ean','name']);
    }

    return response()->json($products);
}

/**
 * Duplique un produit avec toutes ses relations
 */
public function duplicate(Product $product)
{
    $product->load(['categories', 'suppliers', 'stores', 'images', 'variations']);

    $newProduct = DB::transaction(function () use ($product) {
        // Générer un EAN fake unique
        do {
            $fakeEan = 'FAKE-' . mt_rand(10000000, 99999999);
        } while (Product::where('ean', $fakeEan)->exists());

        // Dupliquer les attributs de base
        $newName = [];
        foreach ($product->name as $locale => $name) {
            $newName[$locale] = 'COPY - ' . $name;
        }

        // Dupliquer les slugs avec préfixe "copy-"
        $newSlugs = [];
        if ($product->slugs) {
            foreach ($product->slugs as $locale => $slug) {
                $newSlugs[$locale] = 'copy-' . $slug;
            }
        }

        $newProduct = Product::create([
            'ean' => $fakeEan,
            'name' => $newName,
            'description' => $product->description,
            'slugs' => $newSlugs,
            'price' => $product->price,
            'price_btob' => $product->price_btob,
            'shipping_weight' => $product->shipping_weight,
            'brand_id' => $product->brand_id,
            'color' => $product->color,
            'size' => $product->size,
            'is_active' => false, // Désactivé par défaut
            'is_active_pos' => false, // Désactivé par défaut
            'is_best_seller' => $product->is_best_seller,
            'is_resalable' => $product->is_resalable,
            'allow_overselling' => $product->allow_overselling,
            'attributes' => $product->attributes,
        ]);

        // Dupliquer les catégories
        if ($product->categories->isNotEmpty()) {
            $newProduct->categories()->attach($product->categories->pluck('id'));
        }

        // Dupliquer les fournisseurs avec leurs prix
        if ($product->suppliers->isNotEmpty()) {
            $supplierData = [];
            foreach ($product->suppliers as $supplier) {
                $supplierData[$supplier->id] = ['purchase_price' => $supplier->pivot->purchase_price];
            }
            $newProduct->suppliers()->attach($supplierData);
        }

        // Dupliquer les liens vers les stores (sans stock)
        if ($product->stores->isNotEmpty()) {
            $storeData = [];
            foreach ($product->stores as $store) {
                $storeData[$store->id] = ['alert_stock_quantity' => $store->pivot->alert_stock_quantity];
            }
            $newProduct->stores()->attach($storeData);
        }

        // Dupliquer les photos
        if ($product->images->isNotEmpty()) {
            foreach ($product->images as $image) {
                // Copier le fichier physique
                $originalPath = $image->path;
                if (Storage::disk('public')->exists($originalPath)) {
                    $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
                    $newFileName = 'products/' . uniqid() . '.' . $extension;
                    Storage::disk('public')->copy($originalPath, $newFileName);

                    ProductImage::create([
                        'product_id' => $newProduct->id,
                        'path' => $newFileName,
                        'is_primary' => $image->is_primary,
                        'sort_order' => $image->sort_order,
                    ]);
                }
            }
        }

        // Note: duplicated product is NOT added to the same variation group
        // (it's a copy, not a sibling). Attributes are copied for reference.
        if ($product->variationAttributes->isNotEmpty()) {
            $newGroup = \App\Models\VariationGroup::create([
                'name' => ($newProduct->name['fr'] ?? reset($newProduct->name)) . ' (copie)',
            ]);
            $newProduct->update(['variation_group_id' => $newGroup->id]);
            foreach ($product->variationAttributes as $attr) {
                \App\Models\ProductVariationAttribute::create([
                    'product_id' => $newProduct->id,
                    'variation_group_id' => $newGroup->id,
                    'variation_type_id' => $attr->variation_type_id,
                    'variation_value_id' => $attr->variation_value_id,
                ]);
            }
        }

        return $newProduct;
    });

    return redirect()->route('products.edit', $newProduct)
        ->with('success', __('messages.product.duplicated'));
}

}
