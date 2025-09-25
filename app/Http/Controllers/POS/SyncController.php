<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function users()
    {
        return User::select('id', 'name', 'pin_code', 'store_id')
            ->whereNotNull('pin_code')
            ->whereNotNull('store_id')
            ->get();
    }

    public function catalog(Request $request, $storeId)
    {
        $store = Store::findOrFail($storeId);
        $defaultPhoto = asset('images/no_picture.jpg');

        // Produits
        $products = Product::with(['brand', 'categories.parent', 'images', 'stockBatches'])
            ->get()
            ->map(function ($product) use ($store, $defaultPhoto) {
                $categories = $product->categories->map(fn($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->translation()?->name ?? 'Cat' . $cat->id,
                    'slug' => $cat->slug ?? null,
                    'parent_id' => $cat->parent?->id ?? 0,
                ]);

                $photos = $product->images->count()
                    ? $product->images->map(fn($img) => [
                        'id' => $img->id,
                        'url' => asset('storage/' . $img->path),
                        'is_primary' => $img->is_primary,
                        'sort_order' => $img->sort_order,
                    ])
                    : [[
                        'id' => 0,
                        'url' => $defaultPhoto,
                        'is_primary' => true,
                        'sort_order' => 0,
                    ]];

                return [
                    'id' => $product->id,
                    'ean' => $product->ean,
                    'name' => $product->name,
                    'description' => $product->description,
                    'slugs' => $product->slugs,
                    'price' => $product->price,
                    'price_btob' => $product->price_btob,
                    'brand' => $product->brand ? [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name,
                    ] : null,
                    'categories' => $categories,
                    'photos' => $photos,
                    'total_stock' => $product->getTotalStock($store),
                ];
            });

        // Arborescence complète des catégories
        $categoriesTree = $this->buildCategoryTree();

        return response()->json([
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
            ],
            'products' => $products,
            'category_tree' => $categoriesTree, // <-- nouvelle clé
        ]);
    }

    /**
     * Génère l'arborescence complète des catégories
     */
    protected function buildCategoryTree($parentId = null)
    {
        $categories = Category::with('translations')->where('parent_id', $parentId)->get();

        return $categories->map(fn($cat) => [
            'id' => $cat->id,
            'name' => $cat->translation()?->name ?? 'Cat' . $cat->id,
            'slug' => $cat->slug,
            'children' => $this->buildCategoryTree($cat->id),
        ]);
    }
}
