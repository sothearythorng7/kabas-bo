<?php

namespace App\Http\Controllers;

use App\Models\WarehouseInvoice;
use App\Models\Product;
use App\Enums\InvoiceStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Factures à payer
        $invoicesToPay = WarehouseInvoice::where('status', InvoiceStatus::TO_PAY->value);

        $invoicesToPayCount = $invoicesToPay->count();
        $invoicesToPayTotal = $invoicesToPay->sum('amount_usd');

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

        return view('dashboard', compact(
            'invoicesToPayCount',
            'invoicesToPayTotal',
            'productsWithoutImages',
            'productsWithoutDescriptionFr',
            'productsWithoutDescriptionEn',
            'productsOutOfStock'
        ));
    }

    public function productsWithIssues(Request $request)
    {
        $issueType = $request->get('type', 'all');

        $query = Product::with('brand', 'images');

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
                        });
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

            $product->issues = $issues;
            return $product;
        });

        return view('dashboard.products-issues', compact('products', 'issueType'));
    }
}