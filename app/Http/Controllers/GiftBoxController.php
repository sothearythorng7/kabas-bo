<?php

namespace App\Http\Controllers;

use App\Models\GiftBox;
use App\Models\GiftBoxImage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GiftBoxController extends Controller
{
    public function index(Request $request)
    {
        $query = GiftBox::with('brand')->withCount('images');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('ean', 'like', "%{$q}%")
                    ->orWhere('name->fr', 'like', "%{$q}%")
                    ->orWhere('name->en', 'like', "%{$q}%");
            });
        }

        if ($request->filled('brand_id')) {
            if ($request->brand_id === 'none') {
                $query->whereNull('brand_id');
            } else {
                $query->where('brand_id', $request->brand_id);
            }
        }

        $giftBoxes = $query->orderBy('id', 'desc')->paginate(50)->withQueryString();
        $brands = Brand::orderBy('name')->get();

        return view('gift-boxes.index', compact('giftBoxes', 'brands'));
    }

    public function create()
    {
        $brands = Brand::orderBy('name')->get();
        $locales = config('app.website_locales', ['fr', 'en']);

        return view('gift-boxes.create', compact('brands', 'locales'));
    }

    public function store(Request $request)
    {
        $locales = config('app.website_locales', ['fr', 'en']);

        $data = $request->validate([
            'ean' => 'nullable|string|unique:gift_boxes,ean',
            'price' => 'required|numeric',
            'price_btob' => 'nullable|numeric',
            'brand_id' => 'nullable|exists:brands,id',
            'is_active' => 'sometimes|boolean',
            'is_best_seller' => 'sometimes|boolean',
            'name' => 'required|array',
            'name.*' => 'required|string',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
        ]);

        $giftBox = null;

        DB::transaction(function () use ($data, $locales, &$giftBox) {
            // Build translations first
            $nameTranslations = [];
            $descriptionTranslations = [];
            $slugTranslations = [];

            foreach ($locales as $locale) {
                $nameTranslations[$locale] = $data['name'][$locale] ?? '';
                $descriptionTranslations[$locale] = $data['description'][$locale] ?? '';
                $slugTranslations[$locale] = Str::slug($data['name'][$locale] ?? '');
            }

            $giftBox = GiftBox::create([
                'ean' => $data['ean'] ?? null,
                'name' => $nameTranslations,
                'description' => $descriptionTranslations,
                'slugs' => $slugTranslations,
                'price' => $data['price'],
                'price_btob' => $data['price_btob'] ?? 0,
                'brand_id' => $data['brand_id'] ?? null,
                'is_active' => $data['is_active'] ?? false,
                'is_best_seller' => $data['is_best_seller'] ?? false,
            ]);
        });

        return redirect()->route('gift-boxes.edit', $giftBox)
                         ->with('success', __('messages.gift_box.created'));
    }

    public function edit(GiftBox $giftBox)
    {
        $giftBox->load(['categories', 'images', 'products']);
        $brands = Brand::all();
        $locales = config('app.website_locales', ['fr', 'en']);

        return view('gift-boxes.edit', compact(
            'giftBox',
            'brands',
            'locales'
        ));
    }

    public function update(Request $request, GiftBox $giftBox)
    {
        $locales = config('app.website_locales', ['fr', 'en']);

        $data = $request->validate([
            'ean' => 'nullable|string|unique:gift_boxes,ean,' . $giftBox->id,
            'price' => 'required|numeric',
            'price_btob' => 'nullable|numeric',
            'brand_id' => 'nullable|exists:brands,id',
            'is_active' => 'sometimes|boolean',
            'is_best_seller' => 'sometimes|boolean',
            'name' => 'required|array',
            'name.*' => 'required|string',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $locales, $giftBox) {
            // Build translations first
            $nameTranslations = [];
            $descriptionTranslations = [];
            $slugTranslations = [];

            foreach ($locales as $locale) {
                $nameTranslations[$locale] = $data['name'][$locale] ?? '';
                $descriptionTranslations[$locale] = $data['description'][$locale] ?? '';
                $slugTranslations[$locale] = Str::slug($data['name'][$locale] ?? '');
            }

            $giftBox->update([
                'ean' => $data['ean'] ?? null,
                'name' => $nameTranslations,
                'description' => $descriptionTranslations,
                'slugs' => $slugTranslations,
                'price' => $data['price'],
                'price_btob' => $data['price_btob'] ?? 0,
                'brand_id' => $data['brand_id'] ?? null,
                'is_active' => $data['is_active'] ?? false,
                'is_best_seller' => $data['is_best_seller'] ?? false,
            ]);
        });

        return redirect()->route('gift-boxes.edit', $giftBox)
                         ->with('success', __('messages.gift_box.updated'));
    }

    public function destroy(GiftBox $giftBox)
    {
        $giftBox->delete();

        return redirect()->route('gift-boxes.index')
                         ->with('success', __('messages.gift_box.deleted'));
    }

    // Upload d'image
    public function uploadImage(Request $request, GiftBox $giftBox)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('gift-boxes', 'public');

            $isPrimary = $giftBox->images()->count() === 0;

            if ($isPrimary) {
                $giftBox->images()->update(['is_primary' => false]);
            }

            $image = $giftBox->images()->create([
                'path' => $path,
                'is_primary' => $isPrimary,
                'sort_order' => $giftBox->images()->max('sort_order') + 1,
            ]);

            return response()->json([
                'success' => true,
                'image' => $image,
                'url' => asset('storage/' . $path)
            ]);
        }

        return response()->json(['success' => false], 400);
    }

    // Supprimer une image
    public function deleteImage(GiftBox $giftBox, GiftBoxImage $image)
    {
        if ($image->gift_box_id !== $giftBox->id) {
            return response()->json(['success' => false, 'message' => 'Image non trouvée'], 404);
        }

        Storage::disk('public')->delete($image->path);
        $image->delete();

        // Si c'était l'image principale, définir la première image restante comme principale
        if ($image->is_primary && $giftBox->images()->count() > 0) {
            $giftBox->images()->first()->update(['is_primary' => true]);
        }

        return response()->json(['success' => true]);
    }

    // Définir l'image principale
    public function setPrimaryImage(GiftBox $giftBox, GiftBoxImage $image)
    {
        if ($image->gift_box_id !== $giftBox->id) {
            return response()->json(['success' => false], 404);
        }

        $giftBox->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return response()->json(['success' => true]);
    }

    // Réorganiser les images
    public function reorderImages(Request $request, GiftBox $giftBox)
    {
        $data = $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|exists:gift_box_images,id',
            'images.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($data['images'] as $imageData) {
            GiftBoxImage::where('id', $imageData['id'])
                ->where('gift_box_id', $giftBox->id)
                ->update(['sort_order' => $imageData['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    // Attacher une catégorie
    public function attachCategory(Request $request, GiftBox $giftBox)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        if (!$giftBox->categories()->where('category_id', $data['category_id'])->exists()) {
            $giftBox->categories()->attach($data['category_id']);
        }

        return redirect()->back()->with('success', __('messages.gift_box.category_added'));
    }

    // Détacher une catégorie
    public function detachCategory(GiftBox $giftBox, $categoryId)
    {
        $giftBox->categories()->detach($categoryId);
        return redirect()->back()->with('success', __('messages.gift_box.category_removed'));
    }

    // Attacher un produit
    public function attachProduct(Request $request, GiftBox $giftBox)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if (!$giftBox->products()->where('product_id', $data['product_id'])->exists()) {
            $giftBox->products()->attach($data['product_id'], ['quantity' => $data['quantity']]);
        }

        return redirect()->back()->with('success', __('messages.gift_box.product_added'));
    }

    // Détacher un produit
    public function detachProduct(GiftBox $giftBox, $productId)
    {
        $giftBox->products()->detach($productId);
        return redirect()->back()->with('success', __('messages.gift_box.product_removed'));
    }

    // Mettre à jour la quantité d'un produit
    public function updateProductQuantity(Request $request, GiftBox $giftBox, $productId)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $giftBox->products()->updateExistingPivot($productId, ['quantity' => $data['quantity']]);

        return redirect()->back()->with('success', __('messages.gift_box.quantity_updated'));
    }
}
