<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\GeneralInvoice;
use App\Models\ResellerInvoice;
use App\Models\SaleReport;
use App\Models\SupplierOrder;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\WebsiteOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // Date sélectionnée (par défaut aujourd'hui)
        $selectedDate = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::now();

        // Factures à payer - GeneralInvoice avec status 'pending'
        $generalInvoicesCount = GeneralInvoice::where('status', 'pending')->count();
        $generalInvoicesTotal = GeneralInvoice::where('status', 'pending')->sum('amount');

        // Factures à payer - SupplierOrder reçues non payées (uniquement type 'buyer')
        $supplierOrdersQuery = SupplierOrder::where('status', 'received')
            ->whereHas('supplier', fn($q) => $q->where('type', 'buyer'))
            ->where('is_paid', false)
            ->with('products');

        $supplierOrdersCount = $supplierOrdersQuery->count();
        $supplierOrdersTotal = $supplierOrdersQuery->get()->sum(fn($order) =>
            $order->products->sum(fn($p) => ($p->pivot->invoice_price ?? $p->pivot->purchase_price ?? 0) * ($p->pivot->quantity_received ?? 0))
        );

        // Total des factures à payer
        $invoicesToPayCount = $generalInvoicesCount + $supplierOrdersCount;
        $invoicesToPayTotal = $generalInvoicesTotal + $supplierOrdersTotal;

        // Alertes produits - Produits sans photos
        $productsWithoutImages = Product::whereDoesntHave('images')->count();

        // Produits sans description FR
        $productsWithoutDescriptionFr = Product::where(function($q) {
            $q->whereNull('description')
              ->orWhereRaw("JSON_EXTRACT(description, '$.fr') IS NULL")
              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.fr')) = ''")
              ->orWhereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(description, '$.fr'))) = ''");
        })->count();

        // Produits sans description EN
        $productsWithoutDescriptionEn = Product::where(function($q) {
            $q->whereNull('description')
              ->orWhereRaw("JSON_EXTRACT(description, '$.en') IS NULL")
              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.en')) = ''")
              ->orWhereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(description, '$.en'))) = ''");
        })->count();

        // Produits hors-stock - Calculer les produits avec stock total = 0
        // Méthode 1: Produits sans aucun batch
        $productsWithoutBatches = Product::whereDoesntHave('stockBatches')->pluck('id');

        // Méthode 2: Produits avec des batches mais stock total = 0
        $productsWithZeroStock = Product::whereHas('stockBatches')
            ->get()
            ->filter(function($product) {
                return $product->stockBatches()->sum('quantity') == 0;
            })
            ->pluck('id');

        // Combiner les deux listes
        $productsOutOfStock = $productsWithoutBatches->merge($productsWithZeroStock)->unique()->count();

        // Produits avec EAN fake ou vide
        $productsWithFakeOrEmptyEan = Product::where(function($q) {
            $q->where('ean', 'LIKE', 'FAKE-%')
              ->orWhereNull('ean')
              ->orWhere('ean', '');
        })->count();

        // Produits sans catégorie
        $productsWithoutCategories = Product::whereDoesntHave('categories')->count();

        // Produits inactifs
        $inactiveProducts = Product::where('is_active', false)->count();

        // Produits sans poids
        $productsWithoutWeight = Product::where(function($q) {
            $q->whereNull('shipping_weight')
              ->orWhere('shipping_weight', 0);
        })->where('is_active', true)->count();

        // Reseller invoices en attente de paiement (sales reports consignment)
        $resellerInvoicesUnpaid = ResellerInvoice::whereIn('status', ['unpaid', 'partially_paid'])
            ->whereNotNull('sales_report_id')
            ->count();
        $resellerInvoicesUnpaidTotal = ResellerInvoice::whereIn('status', ['unpaid', 'partially_paid'])
            ->whereNotNull('sales_report_id')
            ->get()
            ->sum(function ($invoice) {
                $paid = $invoice->payments()->sum('amount');
                return $invoice->total_amount - $paid;
            });

        // Consignment supplier invoices (sale reports) à payer
        $consignmentInvoicesUnpaid = SaleReport::where('status', 'invoiced')
            ->where('is_paid', false)->count();
        $consignmentInvoicesUnpaidTotal = SaleReport::where('status', 'invoiced')
            ->where('is_paid', false)->sum('total_amount_invoiced');

        // Messages du site web non lus
        $unreadContactMessages = ContactMessage::unread()->count();

        // C.A. du jour sélectionné par magasin
        $startOfDay = $selectedDate->copy()->startOfDay();
        $endOfDay = $selectedDate->copy()->endOfDay();

        // C.A. du mois de la date sélectionnée par magasin
        $startOfMonth = $selectedDate->copy()->startOfMonth();
        $endOfMonth = $selectedDate->copy()->endOfMonth();

        // Siem Reap (store_id = 2) - Daily
        $salesSiemReapDaily = Sale::where('store_id', 2)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get();
        $revenueSiemReapDaily = Sale::sumRealRevenue($salesSiemReapDaily);
        $salesCountSiemReapDaily = $salesSiemReapDaily->count();

        // Siem Reap (store_id = 2) - Monthly
        $salesSiemReapMonthly = Sale::where('store_id', 2)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();
        $revenueSiemReapMonthly = Sale::sumRealRevenue($salesSiemReapMonthly);
        $salesCountSiemReapMonthly = $salesSiemReapMonthly->count();

        // Phnom Penh (store_id = 1) - Daily
        $salesPhnomPenhDaily = Sale::where('store_id', 1)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get();
        $revenuePhnomPenhDaily = Sale::sumRealRevenue($salesPhnomPenhDaily);
        $salesCountPhnomPenhDaily = $salesPhnomPenhDaily->count();

        // Phnom Penh (store_id = 1) - Monthly
        $salesPhnomPenhMonthly = Sale::where('store_id', 1)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();
        $revenuePhnomPenhMonthly = Sale::sumRealRevenue($salesPhnomPenhMonthly);
        $salesCountPhnomPenhMonthly = $salesPhnomPenhMonthly->count();

        // Données pour le graphique des factures par statut
        $invoicesByStatus = [
            'to_pay' => $invoicesToPayCount,
            'paid' => GeneralInvoice::where('status', 'paid')->count() +
                      SupplierOrder::where('status', 'received')
                          ->whereHas('supplier', fn($q) => $q->where('type', 'buyer'))
                          ->where('is_paid', true)
                          ->count(),
        ];

        // Données pour le graphique du C.A. mensuel (6 derniers mois)
        $monthlyRevenue = [];
        $monthlyRevenueSiemReap = [];
        $monthlyRevenuePhnomPenh = [];
        $monthLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthLabels[] = $date->translatedFormat('M');

            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            // Total tous magasins
            $monthlyRevenue[] = Sale::sumRealRevenue(
                Sale::whereBetween('created_at', [$startOfMonth, $endOfMonth])->get()
            );

            // Siem Reap (store_id = 2)
            $monthlyRevenueSiemReap[] = Sale::sumRealRevenue(
                Sale::where('store_id', 2)
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->get()
            );

            // Phnom Penh (store_id = 1)
            $monthlyRevenuePhnomPenh[] = Sale::sumRealRevenue(
                Sale::where('store_id', 1)
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->get()
            );
        }

        // Commandes du site web (paiement accepté) ventilées par statut — uniquement source=website
        $websiteOrdersByStatus = WebsiteOrder::where('payment_status', 'paid')
            ->where('source', 'website')
            ->selectRaw('status, count(*) as count, sum(total) as total')
            ->groupBy('status')
            ->orderByRaw("FIELD(status, 'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled')")
            ->get();

        $websiteOrdersPaidTotal = $websiteOrdersByStatus->sum('count');
        $websiteOrdersPaidAmount = $websiteOrdersByStatus->sum('total');

        // Commandes spéciales ventilées par statut
        $specialOrdersByStatus = WebsiteOrder::where('source', 'backoffice')
            ->selectRaw('status, payment_status, count(*) as count, sum(total) as total')
            ->groupBy('status', 'payment_status')
            ->orderByRaw("FIELD(status, 'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled')")
            ->get();

        $specialOrdersPending = WebsiteOrder::where('source', 'backoffice')
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->count();

        $specialOrdersToProcess = WebsiteOrder::where('source', 'backoffice')
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->count();

        $specialOrdersTotal = WebsiteOrder::where('source', 'backoffice')->count();

        return view('dashboard', compact(
            'selectedDate',
            'invoicesToPayCount',
            'invoicesToPayTotal',
            'websiteOrdersByStatus',
            'websiteOrdersPaidTotal',
            'websiteOrdersPaidAmount',
            'specialOrdersPending',
            'specialOrdersToProcess',
            'specialOrdersTotal',
            'productsWithoutImages',
            'productsWithoutDescriptionFr',
            'productsWithoutDescriptionEn',
            'productsOutOfStock',
            'productsWithFakeOrEmptyEan',
            'productsWithoutCategories',
            'inactiveProducts',
            'productsWithoutWeight',
            'resellerInvoicesUnpaid',
            'resellerInvoicesUnpaidTotal',
            'consignmentInvoicesUnpaid',
            'consignmentInvoicesUnpaidTotal',
            'unreadContactMessages',
            'revenueSiemReapDaily',
            'salesCountSiemReapDaily',
            'revenueSiemReapMonthly',
            'salesCountSiemReapMonthly',
            'revenuePhnomPenhDaily',
            'salesCountPhnomPenhDaily',
            'revenuePhnomPenhMonthly',
            'salesCountPhnomPenhMonthly',
            'invoicesByStatus',
            'monthlyRevenue',
            'monthlyRevenueSiemReap',
            'monthlyRevenuePhnomPenh',
            'monthLabels'
        ));
    }

    public function productsWithIssues(Request $request)
    {
        $issueType = $request->get('type', 'all');

        $query = Product::with('brand', 'images', 'categories');

        // Filtrer selon le type de problème
        switch ($issueType) {
            case 'no_image':
                $query->whereDoesntHave('images');
                break;

            case 'no_description_fr':
                $query->where(function($q) {
                    $q->whereNull('description')
                      ->orWhereRaw("JSON_EXTRACT(description, '$.fr') IS NULL")
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.fr')) = ''")
                      ->orWhereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(description, '$.fr'))) = ''");
                });
                break;

            case 'no_description_en':
                $query->where(function($q) {
                    $q->whereNull('description')
                      ->orWhereRaw("JSON_EXTRACT(description, '$.en') IS NULL")
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.en')) = ''")
                      ->orWhereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(description, '$.en'))) = ''");
                });
                break;

            case 'fake_or_empty_ean':
                $query->where(function($q) {
                    $q->where('ean', 'LIKE', 'FAKE-%')
                      ->orWhereNull('ean')
                      ->orWhere('ean', '');
                });
                break;

            case 'no_category':
                $query->whereDoesntHave('categories');
                break;

            case 'inactive':
                $query->where('is_active', false);
                break;

            case 'no_weight':
                $query->where('is_active', true)->where(function($q) {
                    $q->whereNull('shipping_weight')->orWhere('shipping_weight', 0);
                });
                break;

            case 'all':
            default:
                // Tous les produits avec au moins un problème
                $query->where(function($mainQuery) {
                    $mainQuery->whereDoesntHave('images')
                        ->orWhere(function($q) {
                            $q->whereNull('description')
                              ->orWhereRaw("JSON_EXTRACT(description, '$.fr') IS NULL")
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.fr')) = ''")
                              ->orWhereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(description, '$.fr'))) = ''");
                        })
                        ->orWhere(function($q) {
                            $q->whereNull('description')
                              ->orWhereRaw("JSON_EXTRACT(description, '$.en') IS NULL")
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.en')) = ''")
                              ->orWhereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(description, '$.en'))) = ''");
                        })
                        ->orWhere(function($q) {
                            $q->where('ean', 'LIKE', 'FAKE-%')
                              ->orWhereNull('ean')
                              ->orWhere('ean', '');
                        })
                        ->orWhereDoesntHave('categories');
                });
                break;
        }

        $products = $query->paginate(50)->withQueryString();

        // Ajouter les problèmes pour chaque produit
        $products->getCollection()->transform(function($product) {
            $issues = [];

            if ($product->images->isEmpty()) {
                $issues[] = 'no_image';
            }

            $descFr = $product->description['fr'] ?? '';
            if (empty(trim($descFr))) {
                $issues[] = 'no_description_fr';
            }

            $descEn = $product->description['en'] ?? '';
            if (empty(trim($descEn))) {
                $issues[] = 'no_description_en';
            }

            // Vérifier si l'EAN est fake ou vide
            if (empty($product->ean) || str_starts_with($product->ean, 'FAKE-')) {
                $issues[] = 'fake_or_empty_ean';
            }

            // Vérifier si le produit n'a pas de catégorie
            if ($product->categories->isEmpty()) {
                $issues[] = 'no_category';
            }

            // Vérifier si le produit est inactif
            if (!$product->is_active) {
                $issues[] = 'inactive';
            }

            // Vérifier si le produit n'a pas de poids
            if (empty($product->shipping_weight)) {
                $issues[] = 'no_weight';
            }

            $product->issues = $issues;
            return $product;
        });

        return view('dashboard.products-issues', compact('products', 'issueType'));
    }

    public function dailySales(Request $request, Store $store)
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::now();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $sales = Sale::where('store_id', $store->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->with(['items.product', 'shift.user', 'exchanges.items.product', 'exchanges.generatedVoucher'])
            ->orderByDesc('created_at')
            ->get();

        // Calculer les totaux (excluding voucher payments)
        $totalRevenue = Sale::sumRealRevenue($sales);
        $totalBeforeDiscount = 0;
        $totalDiscounts = 0;

        foreach ($sales as $sale) {
            $saleBeforeDiscount = 0;
            $saleItemDiscounts = 0;

            foreach ($sale->items as $item) {
                $itemTotal = $item->price * $item->quantity;
                $saleBeforeDiscount += $itemTotal;

                // Calculer les réductions au niveau article
                if (!empty($item->discounts)) {
                    foreach ($item->discounts as $discount) {
                        if (($discount['type'] ?? '') === 'percent') {
                            $saleItemDiscounts += $itemTotal * (($discount['value'] ?? 0) / 100);
                        } else {
                            $saleItemDiscounts += ($discount['value'] ?? 0);
                        }
                    }
                }
            }

            // Calculer les réductions au niveau vente
            $saleDiscounts = 0;
            if (!empty($sale->discounts)) {
                $subtotal = $saleBeforeDiscount - $saleItemDiscounts;
                foreach ($sale->discounts as $discount) {
                    if (($discount['type'] ?? '') === 'percent') {
                        $saleDiscounts += $subtotal * (($discount['value'] ?? 0) / 100);
                    } else {
                        $saleDiscounts += ($discount['value'] ?? 0);
                    }
                }
            }

            $totalBeforeDiscount += $saleBeforeDiscount;
            $totalDiscounts += $saleItemDiscounts + $saleDiscounts;
        }

        return view('dashboard.daily-sales', compact(
            'store',
            'sales',
            'totalRevenue',
            'totalBeforeDiscount',
            'totalDiscounts',
            'date'
        ));
    }
}