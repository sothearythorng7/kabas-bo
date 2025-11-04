<?php

namespace App\Http\Controllers;

use App\Models\GiftCard;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class GiftCardController extends Controller
{
    public function index(Request $request)
    {
        $query = GiftCard::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('name->fr', 'like', "%{$q}%")
                    ->orWhere('name->en', 'like', "%{$q}%");
            });
        }

        $giftCards = $query->orderBy('id', 'desc')->paginate(50)->withQueryString();

        return view('gift-cards.index', compact('giftCards'));
    }

    public function create()
    {
        $locales = config('app.website_locales', ['fr', 'en']);

        return view('gift-cards.create', compact('locales'));
    }

    public function store(Request $request)
    {
        $locales = config('app.website_locales', ['fr', 'en']);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'name' => 'required|array',
            'name.*' => 'required|string',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
        ]);

        $giftCard = null;

        DB::transaction(function () use ($data, $locales, &$giftCard) {
            // Build translations first
            $nameTranslations = [];
            $descriptionTranslations = [];

            foreach ($locales as $locale) {
                $nameTranslations[$locale] = $data['name'][$locale] ?? '';
                $descriptionTranslations[$locale] = $data['description'][$locale] ?? '';
            }

            $giftCard = GiftCard::create([
                'name' => $nameTranslations,
                'description' => $descriptionTranslations,
                'amount' => $data['amount'],
                'is_active' => $data['is_active'] ?? false,
            ]);
        });

        return redirect()->route('gift-cards.edit', $giftCard)
                         ->with('success', 'Carte cadeau créée avec succès');
    }

    public function edit(GiftCard $giftCard)
    {
        $locales = config('app.website_locales', ['fr', 'en']);
        $allCategories = Category::whereNull('parent_id')
            ->with('children.children')
            ->orderBy('id')
            ->get();

        return view('gift-cards.edit', compact('giftCard', 'locales', 'allCategories'));
    }

    public function update(Request $request, GiftCard $giftCard)
    {
        $locales = config('app.website_locales', ['fr', 'en']);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'name' => 'required|array',
            'name.*' => 'required|string',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $locales, $giftCard) {
            // Build translations first
            $nameTranslations = [];
            $descriptionTranslations = [];

            foreach ($locales as $locale) {
                $nameTranslations[$locale] = $data['name'][$locale] ?? '';
                $descriptionTranslations[$locale] = $data['description'][$locale] ?? '';
            }

            $giftCard->update([
                'name' => $nameTranslations,
                'description' => $descriptionTranslations,
                'amount' => $data['amount'],
                'is_active' => $data['is_active'] ?? false,
            ]);
        });

        return redirect()->route('gift-cards.edit', $giftCard)
                         ->with('success', 'Carte cadeau mise à jour avec succès');
    }

    public function destroy(GiftCard $giftCard)
    {
        $giftCard->delete();

        return redirect()->route('gift-cards.index')
                         ->with('success', 'Carte cadeau supprimée avec succès');
    }

    // Gestion des catégories
    public function attachCategory(Request $request, GiftCard $giftCard)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        if (!$giftCard->categories()->where('category_id', $data['category_id'])->exists()) {
            $giftCard->categories()->attach($data['category_id']);
        }

        return redirect()->back()->with('success', 'Catégorie ajoutée avec succès');
    }

    public function detachCategory(GiftCard $giftCard, $categoryId)
    {
        $giftCard->categories()->detach($categoryId);
        return redirect()->back()->with('success', 'Catégorie retirée avec succès');
    }
}
