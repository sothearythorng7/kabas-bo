<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Cache;

class ReviewAggregator
{
    public const CACHE_TTL = 3600;

    public static function for(Product $product): array
    {
        return Cache::remember(
            "reviews.aggregate.product.{$product->id}",
            self::CACHE_TTL,
            fn () => self::compute($product->id)
        );
    }

    private static function compute(int $productId): array
    {
        $rows = Review::approved()
            ->where('product_id', $productId)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $total = array_sum($rows);
        if ($total === 0) {
            return [
                'count' => 0,
                'average' => null,
                'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
                'recommend_pct' => null,
            ];
        }

        $sumRatings = 0;
        foreach ($rows as $rating => $count) {
            $sumRatings += $rating * $count;
        }
        $average = round($sumRatings / $total, 2);

        $distribution = [];
        foreach ([5, 4, 3, 2, 1] as $r) {
            $distribution[$r] = $rows[$r] ?? 0;
        }

        $recommend = ($distribution[4] + $distribution[5]);
        $recommendPct = (int) round(($recommend / $total) * 100);

        return [
            'count' => $total,
            'average' => $average,
            'distribution' => $distribution,
            'recommend_pct' => $recommendPct,
        ];
    }

    public static function invalidate(int $productId): void
    {
        Cache::forget("reviews.aggregate.product.{$productId}");
        // Invalide aussi le cache JSON-LD schema (par locale)
        foreach (['en', 'fr'] as $locale) {
            Cache::forget("reviews.schema.product.{$productId}.{$locale}");
        }
    }
}
