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
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BIDashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'month');

        if ($period === 'custom') {
            $startDate = $request->get('start_date')
                ? Carbon::parse($request->get('start_date'))->startOfDay()
                : Carbon::now()->startOfMonth();
            $endDate = $request->get('end_date')
                ? Carbon::parse($request->get('end_date'))->endOfDay()
                : Carbon::now()->endOfDay();
        } else {
            $startDate = $this->getStartDate($period);
            $endDate = Carbon::now();
        }

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
        // Chiffre d'affaires total (excluding voucher payments - already counted at creation)
        $allSales = Sale::whereBetween('created_at', [$startDate, $endDate])->get();
        $totalRevenue = Sale::sumRealRevenue($allSales);
        $totalRevenueByStore = [];
        foreach ($stores as $store) {
            $storeSalesForRevenue = Sale::where('store_id', $store->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            $totalRevenueByStore[$store->id] = Sale::sumRealRevenue($storeSalesForRevenue);
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

        $previousRevenue = Sale::sumRealRevenue(
            Sale::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->get()
        );
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

        // === VALEUR DU STOCK DES REVENDEURS CONSIGNMENT ===
        $consignmentResellersStock = $this->getConsignmentResellersStockValue();

        // === WALK-IN ET TAUX DE TRANSFORMATION ===
        // On ne compte que les shifts avec visitors_count renseigné et > 0
        $shiftsWithVisitors = Shift::whereBetween('started_at', [$startDate, $endDate])
            ->whereNotNull('visitors_count')
            ->where('visitors_count', '>', 0)
            ->pluck('id');

        $totalWalkIns = Shift::whereIn('id', $shiftsWithVisitors)->sum('visitors_count');

        // Nombre de jours avec des shifts ayant visitors_count
        $daysWithShifts = Shift::whereIn('id', $shiftsWithVisitors)
            ->selectRaw('DATE(started_at) as date')
            ->distinct()
            ->count();
        $averageWalkInPerDay = $daysWithShifts > 0 ? $totalWalkIns / $daysWithShifts : 0;

        // Taux de transformation: ventes des shifts avec visitors / visiteurs
        $salesFromShiftsWithVisitors = Sale::whereIn('shift_id', $shiftsWithVisitors)->count();
        $conversionRate = $totalWalkIns > 0 ? ($salesFromShiftsWithVisitors / $totalWalkIns) * 100 : 0;

        // Par magasin
        $walkInsByStore = [];
        $conversionRateByStore = [];
        $averageWalkInPerDayByStore = [];
        foreach ($stores as $store) {
            $storeShiftsWithVisitors = Shift::where('store_id', $store->id)
                ->whereBetween('started_at', [$startDate, $endDate])
                ->whereNotNull('visitors_count')
                ->where('visitors_count', '>', 0)
                ->pluck('id');

            $storeWalkIns = Shift::whereIn('id', $storeShiftsWithVisitors)->sum('visitors_count');
            $walkInsByStore[$store->id] = $storeWalkIns;

            // Jours avec shifts pour ce magasin
            $storeDaysWithShifts = Shift::whereIn('id', $storeShiftsWithVisitors)
                ->selectRaw('DATE(started_at) as date')
                ->distinct()
                ->count();
            $averageWalkInPerDayByStore[$store->id] = $storeDaysWithShifts > 0 ? $storeWalkIns / $storeDaysWithShifts : 0;

            // Ventes UNIQUEMENT des shifts avec visitors_count renseigné
            $storeSalesFromShiftsWithVisitors = Sale::whereIn('shift_id', $storeShiftsWithVisitors)->count();
            $conversionRateByStore[$store->id] = $storeWalkIns > 0 ? ($storeSalesFromShiftsWithVisitors / $storeWalkIns) * 100 : 0;
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
            'stockValueByStore',
            'consignmentResellersStock',
            'totalWalkIns',
            'averageWalkInPerDay',
            'conversionRate',
            'walkInsByStore',
            'conversionRateByStore',
            'averageWalkInPerDayByStore'
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
        // Step 1: Get top 10 product IDs by quantity (SQL)
        $topProductIds = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_quantity'))
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
            ->pluck('total_quantity', 'product_id');

        if ($topProductIds->isEmpty()) {
            return [];
        }

        // Step 2: Load items for those products with sale (for net revenue calculation)
        $items = SaleItem::with('sale.items')
            ->whereIn('product_id', $topProductIds->keys())
            ->whereHas('sale', function($q) use ($startDate, $endDate, $storeId) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
                if ($storeId) {
                    $q->where('store_id', $storeId);
                }
            })
            ->get();

        // Step 3: Calculate net revenue per product
        $revenueByProduct = $items->groupBy('product_id')->map(fn($group) => $group->sum(fn($item) => $item->net_revenue));

        // Step 4: Build result
        $products = [];
        foreach ($topProductIds as $productId => $totalQuantity) {
            $product = Product::with('brand')->find($productId);
            if (!$product) continue;

            $totalRevenue = $revenueByProduct[$productId] ?? 0;
            $purchasePrice = $this->getProductPurchasePrice($product);
            $margin = $totalRevenue - ($purchasePrice * $totalQuantity);
            $marginPercent = $totalRevenue > 0 ? ($margin / $totalRevenue) * 100 : 0;

            $products[] = [
                'product' => $product,
                'quantity' => $totalQuantity,
                'revenue' => $totalRevenue,
                'margin' => $margin,
                'margin_percent' => $marginPercent,
            ];
        }

        usort($products, fn($a, $b) => $b['quantity'] <=> $a['quantity']);

        return $products;
    }

    private function getProductPurchasePrice(Product $product): float
    {
        // 1. Try supplier pivot purchase_price
        $supplierPrice = $product->suppliers()->first()?->pivot?->purchase_price;
        if ($supplierPrice && $supplierPrice > 0) {
            return $supplierPrice;
        }

        // 2. Try average from stock_batches unit_price
        $batchPrice = StockBatch::where('product_id', $product->id)
            ->where('unit_price', '>', 0)
            ->avg('unit_price');
        if ($batchPrice && $batchPrice > 0) {
            return (float) $batchPrice;
        }

        // 3. Fallback: estimate at 50% of sale price
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
        $items = SaleItem::whereHas('sale', function($q) use ($startDate, $endDate, $storeId) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
            if ($storeId) {
                $q->where('store_id', $storeId);
            }
        })->whereNotNull('product_id')->with(['product.suppliers', 'sale.items'])->get();

        $totalMargin = 0;
        foreach ($items as $item) {
            if (!$item->product) continue;

            $revenue = $item->net_revenue;
            $purchasePrice = $this->getProductPurchasePrice($item->product);
            $cost = $purchasePrice * $item->quantity;
            $totalMargin += $revenue - $cost;
        }

        return $totalMargin;
    }

    private function getTopBrands(Carbon $startDate, Carbon $endDate): array
    {
        $items = SaleItem::with(['sale.items', 'product.brand'])
            ->whereHas('sale', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->whereNotNull('product_id')
            ->whereHas('product', fn($q) => $q->whereNotNull('brand_id'))
            ->get();

        $brandData = [];
        foreach ($items as $item) {
            if (!$item->product) continue;
            $brandId = $item->product->brand_id;
            if (!$brandId) continue;

            if (!isset($brandData[$brandId])) {
                $brandData[$brandId] = [
                    'brand' => $item->product->brand,
                    'quantity' => 0,
                    'revenue' => 0,
                ];
            }

            $brandData[$brandId]['quantity'] += $item->quantity;
            $brandData[$brandId]['revenue'] += $item->net_revenue;
        }

        usort($brandData, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        return array_slice($brandData, 0, 10);
    }

    private function getTopCategories(Carbon $startDate, Carbon $endDate): array
    {
        $items = SaleItem::with(['sale.items', 'product.categories.translations'])
            ->whereHas('sale', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->whereNotNull('product_id')
            ->get();

        $categoryData = [];
        foreach ($items as $item) {
            if (!$item->product) continue;

            foreach ($item->product->categories as $category) {
                if (!isset($categoryData[$category->id])) {
                    $name = $category->translation()?->name ?? $category->translation('fr')?->name ?? 'N/A';
                    $categoryData[$category->id] = [
                        'name' => $name,
                        'quantity' => 0,
                        'revenue' => 0,
                    ];
                }

                $categoryData[$category->id]['quantity'] += $item->quantity;
                $categoryData[$category->id]['revenue'] += $item->net_revenue;
            }
        }

        usort($categoryData, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        return array_slice($categoryData, 0, 10);
    }

    private function getMonthlyEvolution(): array
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $monthlySales = Sale::whereBetween('created_at', [$startOfMonth, $endOfMonth])->get();
            $revenue = Sale::sumRealRevenue($monthlySales);
            $sales = $monthlySales->count();

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

    private function getConsignmentResellersStockValue(): array
    {
        // Récupérer tous les revendeurs de type consignment
        $consignmentResellers = Reseller::where('type', 'consignment')->get();

        $result = [];
        $totalValue = 0;

        foreach ($consignmentResellers as $reseller) {
            // Récupérer les batches avec les produits pour calculer la valeur
            $batches = StockBatch::where('reseller_id', $reseller->id)
                ->where('quantity', '>', 0)
                ->with('product')
                ->get();

            // Calculer la valeur du stock en utilisant price_btob si unit_price est 0
            $stockValue = $batches->sum(function($batch) {
                $price = $batch->unit_price > 0
                    ? $batch->unit_price
                    : ($batch->product->price_btob ?? $batch->product->price ?? 0);
                return $batch->quantity * $price;
            });

            // Nombre de produits différents
            $productCount = $batches->pluck('product_id')->unique()->count();

            // Quantité totale
            $totalQuantity = $batches->sum('quantity');

            $result[] = [
                'reseller' => $reseller,
                'stock_value' => $stockValue,
                'product_count' => $productCount,
                'total_quantity' => $totalQuantity,
            ];

            $totalValue += $stockValue;
        }

        // Trier par valeur de stock décroissante
        usort($result, fn($a, $b) => $b['stock_value'] <=> $a['stock_value']);

        return [
            'resellers' => $result,
            'total_value' => $totalValue,
        ];
    }
}
