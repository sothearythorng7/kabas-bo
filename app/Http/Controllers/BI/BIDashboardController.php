<?php

namespace App\Http\Controllers\BI;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\Reseller;
use App\Models\ResellerSalesReport;
use App\Models\StockBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BIDashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);
        $endDate = Carbon::now();

        // Récupérer les magasins
        $stores = Store::all();

        // === TOP 10 PRODUITS LES PLUS VENDUS ===
        $topProductsAll = $this->getTopProducts($startDate, $endDate);
        $topProductsByStore = [];
        foreach ($stores as $store) {
            $topProductsByStore[$store->id] = $this->getTopProducts($startDate, $endDate, $store->id);
        }

        // === TOP 10 REVENDEURS ===
        $topResellersByQuantity = $this->getTopResellersByQuantity($startDate, $endDate);
        $topResellersByRevenue = $this->getTopResellersByRevenue($startDate, $endDate);

        // === KPI GLOBAUX ===
        // Chiffre d'affaires total
        $totalRevenue = Sale::whereBetween('created_at', [$startDate, $endDate])->sum('total');
        $totalRevenueByStore = [];
        foreach ($stores as $store) {
            $totalRevenueByStore[$store->id] = Sale::where('store_id', $store->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total');
        }

        // Nombre de ventes
        $totalSales = Sale::whereBetween('created_at', [$startDate, $endDate])->count();

        // Panier moyen
        $averageBasket = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
        $averageBasketByStore = [];
        $averageItemsPerSaleByStore = [];
        foreach ($stores as $store) {
            $storeSales = Sale::where('store_id', $store->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $averageBasketByStore[$store->id] = $storeSales > 0 ? $totalRevenueByStore[$store->id] / $storeSales : 0;

            // Nombre moyen d'articles par vente par boutique
            $storeItemsSold = SaleItem::whereHas('sale', function($q) use ($startDate, $endDate, $store) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->where('store_id', $store->id);
            })->sum('quantity');
            $averageItemsPerSaleByStore[$store->id] = $storeSales > 0 ? $storeItemsSold / $storeSales : 0;
        }

        // Nombre d'articles vendus
        $totalItemsSold = SaleItem::whereHas('sale', function($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        })->sum('quantity');

        // === MARGE TOTALE ===
        $totalMargin = $this->calculateTotalMargin($startDate, $endDate);
        $marginByStore = [];
        foreach ($stores as $store) {
            $marginByStore[$store->id] = $this->calculateTotalMargin($startDate, $endDate, $store->id);
        }

        // === TOP MARQUES ===
        $topBrands = $this->getTopBrands($startDate, $endDate);

        // === TOP CATEGORIES ===
        $topCategories = $this->getTopCategories($startDate, $endDate);

        // === EVOLUTION MENSUELLE ===
        $monthlyEvolution = $this->getMonthlyEvolution();

        // === COMPARAISON PERIODE PRECEDENTE ===
        $previousPeriodStart = $this->getPreviousPeriodStart($period, $startDate);
        $previousPeriodEnd = $startDate->copy()->subSecond();

        $previousRevenue = Sale::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->sum('total');
        $revenueGrowth = $previousRevenue > 0 ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

        $previousSales = Sale::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();
        $salesGrowth = $previousSales > 0 ? (($totalSales - $previousSales) / $previousSales) * 100 : 0;

        // === REPARTITION PAR MODE DE PAIEMENT ===
        $paymentDistribution = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->select('payment_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('payment_type')
            ->get()
            ->keyBy('payment_type');

        // === VALEUR DU STOCK ===
        $stockValue = $this->calculateStockValue();
        $stockValueByStore = [];
        foreach ($stores as $store) {
            $stockValueByStore[$store->id] = $this->calculateStockValue($store->id);
        }

        return view('bi.dashboard', compact(
            'stores',
            'period',
            'startDate',
            'endDate',
            'topProductsAll',
            'topProductsByStore',
            'topResellersByQuantity',
            'topResellersByRevenue',
            'totalRevenue',
            'totalRevenueByStore',
            'totalSales',
            'averageBasket',
            'averageBasketByStore',
            'averageItemsPerSaleByStore',
            'totalItemsSold',
            'totalMargin',
            'marginByStore',
            'topBrands',
            'topCategories',
            'monthlyEvolution',
            'revenueGrowth',
            'salesGrowth',
            'paymentDistribution',
            'stockValue',
            'stockValueByStore'
        ));
    }

    private function getStartDate(string $period): Carbon
    {
        return match($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'quarter' => Carbon::now()->startOfQuarter(),
            'year' => Carbon::now()->startOfYear(),
            'all' => Carbon::create(2020, 1, 1),
            default => Carbon::now()->startOfMonth(),
        };
    }

    private function getPreviousPeriodStart(string $period, Carbon $currentStart): Carbon
    {
        return match($period) {
            'week' => $currentStart->copy()->subWeek(),
            'month' => $currentStart->copy()->subMonth(),
            'quarter' => $currentStart->copy()->subQuarter(),
            'year' => $currentStart->copy()->subYear(),
            default => $currentStart->copy()->subMonth(),
        };
    }

    private function getTopProducts(Carbon $startDate, Carbon $endDate, ?int $storeId = null): array
    {
        $query = SaleItem::select(
                'product_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(price * quantity) as total_revenue')
            )
            ->whereHas('sale', function($q) use ($startDate, $endDate, $storeId) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
                if ($storeId) {
                    $q->where('store_id', $storeId);
                }
            })
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        $products = [];
        foreach ($query as $item) {
            $product = Product::with('brand')->find($item->product_id);
            if (!$product) continue;

            // Calculer la marge
            $purchasePrice = $this->getProductPurchasePrice($product);
            $margin = $item->total_revenue - ($purchasePrice * $item->total_quantity);
            $marginPercent = $item->total_revenue > 0 ? ($margin / $item->total_revenue) * 100 : 0;

            $products[] = [
                'product' => $product,
                'quantity' => $item->total_quantity,
                'revenue' => $item->total_revenue,
                'margin' => $margin,
                'margin_percent' => $marginPercent,
            ];
        }

        return $products;
    }

    private function getProductPurchasePrice(Product $product): float
    {
        // Essayer de récupérer le prix d'achat depuis les fournisseurs
        $supplierPrice = $product->suppliers()->first()?->pivot?->purchase_price;
        if ($supplierPrice) {
            return $supplierPrice;
        }

        // Sinon estimer à 50% du prix de vente
        return $product->price * 0.5;
    }

    private function getTopResellersByQuantity(Carbon $startDate, Carbon $endDate): array
    {
        // Récupérer les rapports de ventes avec leurs items
        $reports = ResellerSalesReport::with(['reseller', 'items'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Calculer les totaux par revendeur
        $resellerTotals = [];
        foreach ($reports as $report) {
            if (!$report->reseller) continue;

            $resellerId = $report->reseller_id;
            if (!isset($resellerTotals[$resellerId])) {
                $resellerTotals[$resellerId] = [
                    'reseller' => $report->reseller,
                    'quantity' => 0,
                ];
            }
            $resellerTotals[$resellerId]['quantity'] += $report->items->sum('quantity_sold');
        }

        // Trier et limiter à 10
        usort($resellerTotals, fn($a, $b) => $b['quantity'] <=> $a['quantity']);
        return array_slice($resellerTotals, 0, 10);
    }

    private function getTopResellersByRevenue(Carbon $startDate, Carbon $endDate): array
    {
        // Récupérer les rapports de ventes avec leurs items
        $reports = ResellerSalesReport::with(['reseller', 'items'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Calculer les totaux par revendeur
        $resellerTotals = [];
        foreach ($reports as $report) {
            if (!$report->reseller) continue;

            $resellerId = $report->reseller_id;
            if (!isset($resellerTotals[$resellerId])) {
                $resellerTotals[$resellerId] = [
                    'reseller' => $report->reseller,
                    'revenue' => 0,
                ];
            }
            $resellerTotals[$resellerId]['revenue'] += $report->items->sum(fn($item) => $item->quantity_sold * $item->unit_price);
        }

        // Trier et limiter à 10
        usort($resellerTotals, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        return array_slice($resellerTotals, 0, 10);
    }

    private function calculateTotalMargin(Carbon $startDate, Carbon $endDate, ?int $storeId = null): float
    {
        $query = SaleItem::whereHas('sale', function($q) use ($startDate, $endDate, $storeId) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
            if ($storeId) {
                $q->where('store_id', $storeId);
            }
        })->whereNotNull('product_id')->with('product.suppliers')->get();

        $totalMargin = 0;
        foreach ($query as $item) {
            if (!$item->product) continue;

            $revenue = $item->price * $item->quantity;
            $purchasePrice = $this->getProductPurchasePrice($item->product);
            $cost = $purchasePrice * $item->quantity;
            $totalMargin += $revenue - $cost;
        }

        return $totalMargin;
    }

    private function getTopBrands(Carbon $startDate, Carbon $endDate): array
    {
        return SaleItem::select(
                'products.brand_id',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.price * sale_items.quantity) as total_revenue')
            )
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereHas('sale', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->whereNotNull('products.brand_id')
            ->groupBy('products.brand_id')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $brand = \App\Models\Brand::find($item->brand_id);
                return [
                    'brand' => $brand,
                    'quantity' => $item->total_quantity,
                    'revenue' => $item->total_revenue,
                ];
            })
            ->toArray();
    }

    private function getTopCategories(Carbon $startDate, Carbon $endDate): array
    {
        $categoryData = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('category_product', 'sale_items.product_id', '=', 'category_product.product_id')
            ->join('categories', 'category_product.category_id', '=', 'categories.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->select(
                'categories.id',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.price * sale_items.quantity) as total_revenue')
            )
            ->groupBy('categories.id')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        return $categoryData->map(function($item) {
            $category = \App\Models\Category::with('translations')->find($item->id);
            $name = $category?->translation()?->name ?? $category?->translation('fr')?->name ?? 'N/A';
            return [
                'name' => $name,
                'quantity' => $item->total_quantity,
                'revenue' => $item->total_revenue,
            ];
        })->toArray();
    }

    private function getMonthlyEvolution(): array
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $revenue = Sale::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total');
            $sales = Sale::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

            $data[] = [
                'month' => $date->translatedFormat('M Y'),
                'revenue' => $revenue,
                'sales' => $sales,
            ];
        }
        return $data;
    }

    private function calculateStockValue(?int $storeId = null): float
    {
        $query = StockBatch::where('quantity', '>', 0)
            ->whereNotNull('store_id');

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        // Utiliser unit_price (prix d'achat) au lieu du prix de vente
        return $query->sum(DB::raw('quantity * unit_price'));
    }
}
