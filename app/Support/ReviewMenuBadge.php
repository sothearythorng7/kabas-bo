<?php

namespace App\Support;

use App\Models\Review;
use Illuminate\Support\Facades\Cache;

/**
 * Helper pour afficher le nombre de reviews "pending" dans le menu BO ou le dashboard.
 *
 * Utilisation côté Blade :
 *   {{-- Dans une vue admin/dashboard --}}
 *   @php $pending = \App\Support\ReviewMenuBadge::pendingCount(); @endphp
 *   @if($pending > 0)
 *     <span class="badge bg-warning text-dark">{{ $pending }}</span>
 *   @endif
 *
 * Cache 60s pour éviter de hammerer la DB sur chaque page render.
 */
class ReviewMenuBadge
{
    public const CACHE_TTL = 60;

    public static function pendingCount(): int
    {
        return (int) Cache::remember(
            'reviews.menu.pending_count',
            self::CACHE_TTL,
            fn () => Review::where('status', 'pending')->count()
        );
    }

    public static function invalidate(): void
    {
        Cache::forget('reviews.menu.pending_count');
    }
}
