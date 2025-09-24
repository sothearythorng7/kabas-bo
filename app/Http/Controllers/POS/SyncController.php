<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    /**
     * Retourne tous les utilisateurs avec leur code PIN et store_id
     */
    public function users()
    {
        return User::select('id', 'name', 'pin_code', 'store_id')
            ->whereNotNull('pin_code')
            ->whereNotNull('store_id')
            ->get();
    }

    /**
     * Retourne le catalogue d'un magasin avec produits, catégories et photos
     */
    public function catalog(Request $request, $storeId)
    {
        $store = Store::findOrFail($storeId);
        $defaultPhoto = asset('images/no_picture.jpg');

        $products = Product::with(['brand', 'categories.parent', 'images', 'stockBatches'])
            ->get()
            ->map(function ($product) use ($store, $defaultPhoto) {

                // Catégories du produit : uniquement celles auxquelles le produit appartient
                $categories = $product->categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->translation()?->name ?? 'Cat' . $cat->id,
                        'slug' => $cat->slug ?? null,
                        'parent_id' => $cat->parent?->id ?? 0,
                    ];
                });

                // Photos
                $photos = $product->images->count()
                    ? $product->images->map(fn($img) => [
                        'id' => $img->id,
                        'url' => asset('storage/' . $img->path),
                        'is_primary' => $img->is_primary,
                        'sort_order' => $img->sort_order,
                    ])
                    : [
                        [
                            'id' => 0,
                            'url' => $defaultPhoto,
                            'is_primary' => true,
                            'sort_order' => 0,
                        ]
                    ];

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

        return response()->json([
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
            ],
            'products' => $products,
        ]);
    }
}
