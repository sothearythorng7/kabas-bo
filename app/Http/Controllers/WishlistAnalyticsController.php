<?php

namespace App\Http\Controllers;

use App\Models\GiftBox;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WishlistAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $stockFilter = $request->get('stock'); // '', 'in_stock', 'out_of_stock'

        $warehouseId = Store::warehouseId();

        $totals = [
            'items' => DB::table('wishlist_items')->count(),
            'customers' => DB::table('wishlist_items')->distinct('customer_id')->count('customer_id'),
            'products' => DB::table('wishlist_items')->where('item_type', 'product')->distinct('product_id')->count('product_id'),
            'gift_boxes' => DB::table('wishlist_items')->where('item_type', 'gift_box')->distinct('gift_box_id')->count('gift_box_id'),
        ];

        // Top products (grouped + joined with stock in warehouse)
        $productRows = DB::table('wishlist_items as wi')
            ->join('products as p', 'p.id', '=', 'wi.product_id')
            ->leftJoin('stock_batches as sb', function ($j) use ($warehouseId) {
                $j->on('sb.product_id', '=', 'p.id')->where('sb.store_id', $warehouseId);
            })
            ->where('wi.item_type', 'product')
            ->groupBy('p.id', 'p.name', 'p.price', 'p.allow_overselling')
            ->select([
                'p.id',
                'p.name',
                'p.price',
                'p.allow_overselling',
                DB::raw('COUNT(DISTINCT wi.customer_id) as wishlist_count'),
                DB::raw('COALESCE(SUM(sb.quantity), 0) as stock_qty'),
                DB::raw('MAX(wi.added_at) as last_added_at'),
            ])
            ->orderByDesc('wishlist_count')
            ->get();

        $productIds = $productRows->pluck('id')->all();
        $productImages = $productIds
            ? DB::table('product_images')
                ->whereIn('product_id', $productIds)
                ->where('is_primary', true)
                ->pluck('path', 'product_id')
            : collect();

        $products = $productRows->map(function ($row) use ($productImages) {
            $nameArr = json_decode($row->name ?? '[]', true) ?: [];
            $nameFr = $nameArr['fr'] ?? $nameArr['en'] ?? '—';
            $nameEn = $nameArr['en'] ?? $nameArr['fr'] ?? '—';
            $displayName = app()->getLocale() === 'fr' ? $nameFr : $nameEn;
            $price = (float) ($row->price ?? 0);
            $stock = (int) $row->stock_qty;
            $inStock = $stock > 0 || (int) ($row->allow_overselling ?? 0) === 1;
            return (object) [
                'id' => (int) $row->id,
                'name' => $displayName,
                'image' => $productImages[$row->id] ?? null,
                'price' => $price,
                'stock' => $stock,
                'in_stock' => $inStock,
                'allow_overselling' => (bool) $row->allow_overselling,
                'wishlist_count' => (int) $row->wishlist_count,
                'last_added_at' => $row->last_added_at,
            ];
        });

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $products = $products->filter(fn ($p) => str_contains(mb_strtolower($p->name), $needle))->values();
        }
        if ($stockFilter === 'in_stock') {
            $products = $products->filter(fn ($p) => $p->in_stock)->values();
        } elseif ($stockFilter === 'out_of_stock') {
            $products = $products->filter(fn ($p) => ! $p->in_stock)->values();
        }

        // Top gift boxes
        $giftBoxRows = DB::table('wishlist_items as wi')
            ->join('gift_boxes as g', 'g.id', '=', 'wi.gift_box_id')
            ->where('wi.item_type', 'gift_box')
            ->groupBy('g.id', 'g.name', 'g.price')
            ->select([
                'g.id',
                'g.name',
                'g.price',
                DB::raw('COUNT(DISTINCT wi.customer_id) as wishlist_count'),
                DB::raw('MAX(wi.added_at) as last_added_at'),
            ])
            ->orderByDesc('wishlist_count')
            ->get();

        $giftBoxIds = $giftBoxRows->pluck('id')->all();
        $giftBoxImages = $giftBoxIds
            ? DB::table('gift_box_images')
                ->whereIn('gift_box_id', $giftBoxIds)
                ->where('is_primary', true)
                ->pluck('path', 'gift_box_id')
            : collect();

        $giftBoxes = $giftBoxRows->map(function ($row) use ($giftBoxImages) {
            $nameArr = json_decode($row->name ?? '[]', true) ?: [];
            $display = app()->getLocale() === 'fr'
                ? ($nameArr['fr'] ?? $nameArr['en'] ?? '—')
                : ($nameArr['en'] ?? $nameArr['fr'] ?? '—');
            return (object) [
                'id' => (int) $row->id,
                'name' => $display,
                'image' => $giftBoxImages[$row->id] ?? null,
                'price' => (float) ($row->price ?? 0),
                'wishlist_count' => (int) $row->wishlist_count,
                'last_added_at' => $row->last_added_at,
            ];
        });

        // Daily additions (last 30 days) for chart
        $monthStart = now()->subDays(29)->startOfDay();
        $raw = DB::table('wishlist_items')
            ->where('added_at', '>=', $monthStart)
            ->selectRaw('DATE(added_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day')
            ->all();

        $daily = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $daily[] = ['date' => $date, 'count' => (int) ($raw[$date] ?? 0)];
        }

        return view('wishlist_analytics.index', [
            'totals' => $totals,
            'products' => $products,
            'giftBoxes' => $giftBoxes,
            'daily' => $daily,
            'search' => $search,
            'stockFilter' => $stockFilter,
        ]);
    }
}
