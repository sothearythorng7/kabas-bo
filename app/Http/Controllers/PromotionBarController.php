<?php

namespace App\Http\Controllers;

use App\Models\PromotionBar;
use Illuminate\Http\Request;

class PromotionBarController extends Controller
{
    public function index()
    {
        $promotionBar = PromotionBar::first();

        // Si aucune barre n'existe, créer une par défaut
        if (!$promotionBar) {
            $promotionBar = PromotionBar::create([
                'message' => [
                    'en' => 'Enjoy 10% off on purchases of $50 or more',
                    'fr' => 'Profitez de 10% de réduction sur les achats de 50$ et plus',
                ],
                'is_active' => true,
            ]);
        }

        return view('promotion-bar.index', compact('promotionBar'));
    }

    public function update(Request $request)
    {
        $locales = config('app.website_locales', ['en', 'fr']);

        $rules = [
            'is_active' => 'sometimes|boolean',
        ];

        foreach ($locales as $locale) {
            $rules["message.$locale"] = 'nullable|string|max:500';
        }

        $data = $request->validate($rules);

        $promotionBar = PromotionBar::first();

        if (!$promotionBar) {
            $promotionBar = new PromotionBar();
        }

        $promotionBar->is_active = $request->has('is_active');

        // Mettre à jour les messages pour chaque langue
        foreach ($locales as $locale) {
            $promotionBar->setTranslation('message', $locale, $data['message'][$locale] ?? '');
        }

        $promotionBar->save();

        return redirect()->route('promotion-bar.index')
            ->with('success', __('messages.promotion_bar_updated'));
    }
}
