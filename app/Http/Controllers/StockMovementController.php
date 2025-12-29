<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\StockBatch;
use App\Models\Store;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class StockMovementController extends Controller
{
    public function index()
    {
        $movements = StockMovement::with(['fromStore', 'toStore', 'user', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('stock_movements.index', compact('movements'));
    }

    public function create()
    {
        $products = Product::with('stores', 'stockBatches')->get();
        $stores = Store::all();

        // Calculer le stock réel par magasin pour chaque produit
        $products->map(function ($product) {
            $product->realStock = $product->stores->mapWithKeys(function ($store) use ($product) {
                $stock = $product->stockBatches()
                    ->where('store_id', $store->id)
                    ->sum('quantity');
                return [$store->id => $stock];
            });
            return $product;
        });

        return view('stock_movements.create', compact('products', 'stores'));
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $movement = StockMovement::create([
                'type'          => StockMovement::TYPE_TRANSFER,
                'from_store_id' => $request->from_store_id,
                'to_store_id'   => $request->to_store_id,
                'note'          => $request->note,
                'user_id'       => auth()->id(),
                'status'        => StockMovement::STATUS_VALIDATED,
            ]);

            $fromStore = Store::find($request->from_store_id);

            foreach ($request->products ?? [] as $productId => $qty) {
                if ($qty <= 0) continue;

                $product = Product::find($productId);
                if (!$product) continue;

                // Récupérer le prix unitaire moyen depuis les StockBatch du magasin source
                $avgUnitPrice = StockBatch::where('store_id', $request->from_store_id)
                    ->where('product_id', $productId)
                    ->where('quantity', '>', 0)
                    ->avg('unit_price') ?? 0;

                $movement->items()->create([
                    'product_id' => $productId,
                    'quantity'   => $qty,
                    'unit_price' => $avgUnitPrice,
                ]);

                if ($fromStore) {
                    if (!$product->removeStock($fromStore, $qty)) {
                        throw new \Exception("Stock insuffisant pour le produit {$product->name}");
                    }
                }
            }
        });

        return redirect()->route('stock-movements.index')
            ->with('success', __('messages.stock_movement.saved_and_validated'));
    }

    public function receive(StockMovement $movement)
    {
        if (!in_array($movement->status, [StockMovement::STATUS_VALIDATED, StockMovement::STATUS_IN_TRANSIT])) {
            return redirect()->back()->withErrors('Ce mouvement ne peut pas être réceptionné.');
        }

        DB::transaction(function () use ($movement) {
            $toStore = Store::find($movement->to_store_id);
            $fromStore = Store::find($movement->from_store_id);

            if (!$toStore) throw new \Exception("Magasin de destination introuvable.");

            $totalAmount = 0;

            foreach ($movement->items as $item) {
                $product = Product::find($item->product_id);
                if (!$product) continue;

                // Créer un StockBatch pour le magasin de destination avec le prix unitaire
                StockBatch::create([
                    'product_id' => $product->id,
                    'store_id'   => $toStore->id,
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->unit_price,
                ]);

                $totalAmount += $item->quantity * ($item->unit_price ?? 0);
            }

            // Générer la facturation si c'est un transfert inter-magasins
            if ($fromStore && $totalAmount > 0) {
                $this->generateInvoiceAndTransactions($movement, $fromStore, $toStore, $totalAmount);
            }

            $movement->update(['status' => StockMovement::STATUS_RECEIVED]);
        });

        return redirect()->route('stock-movements.index')
            ->with('success', __('messages.stock_movement.received_and_updated'));
    }

    /**
     * Génère la facture et les transactions financières pour un transfert de stock
     *
     * Fonctionne dans tous les sens :
     * - Warehouse → Magasin : le warehouse a une créance, le magasin a une dette
     * - Magasin → Warehouse : le magasin a une créance, le warehouse a une dette
     * - Magasin A → Magasin B : le magasin A a une créance, le magasin B a une dette
     */
    protected function generateInvoiceAndTransactions(
        StockMovement $movement,
        Store $fromStore,
        Store $toStore,
        float $totalAmount
    ): void {
        // Récupérer le compte de revenus (701 - Shop Sales)
        $revenueAccount = FinancialAccount::where('code', '701')->first();
        if (!$revenueAccount) {
            throw new \Exception("Compte financier 701 (Shop Sales) introuvable.");
        }

        // Générer le numéro de facture
        $invoiceNumber = StockMovement::generateInvoiceNumber();

        // Déterminer les libellés selon le type de transfert
        $fromIsWarehouse = $fromStore->type === 'warehouse';
        $toIsWarehouse = $toStore->type === 'warehouse';

        if ($fromIsWarehouse && !$toIsWarehouse) {
            // Warehouse → Magasin : approvisionnement
            $fromLabel = "Approvisionnement #{$movement->id} - Envoi vers {$toStore->name}";
            $toLabel = "Approvisionnement #{$movement->id} - Réception depuis {$fromStore->name}";
            $invoiceType = 'Approvisionnement';
        } elseif (!$fromIsWarehouse && $toIsWarehouse) {
            // Magasin → Warehouse : retour de stock
            $fromLabel = "Retour stock #{$movement->id} - Envoi vers {$toStore->name}";
            $toLabel = "Retour stock #{$movement->id} - Réception depuis {$fromStore->name}";
            $invoiceType = 'Retour de stock';
        } else {
            // Magasin → Magasin ou Warehouse → Warehouse : transfert inter-magasins
            $fromLabel = "Transfert #{$movement->id} - Envoi vers {$toStore->name}";
            $toLabel = "Transfert #{$movement->id} - Réception depuis {$fromStore->name}";
            $invoiceType = 'Transfert inter-magasins';
        }

        // 1) Transaction CREDIT pour le magasin source - il a une créance sur le destinataire
        $lastFromTx = FinancialTransaction::where('store_id', $fromStore->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        $fromBalanceBefore = $lastFromTx?->balance_after ?? 0;

        $fromTransaction = FinancialTransaction::create([
            'store_id'         => $fromStore->id,
            'account_id'       => $revenueAccount->id,
            'amount'           => $totalAmount,
            'currency'         => 'USD',
            'direction'        => 'credit',
            'balance_before'   => $fromBalanceBefore,
            'balance_after'    => $fromBalanceBefore + $totalAmount,
            'label'            => $fromLabel,
            'description'      => "Facture {$invoiceNumber} - {$invoiceType} de {$fromStore->name} vers {$toStore->name}",
            'status'           => 'validated',
            'transaction_date' => now(),
            'user_id'          => auth()->id(),
            'payment_method_id' => 2, // BANK TRANSFER - pour les transferts internes
            'external_reference' => $invoiceNumber,
        ]);

        // 2) Transaction DEBIT pour le magasin destination - il a une dette envers l'expéditeur
        $lastToTx = FinancialTransaction::where('store_id', $toStore->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        $toBalanceBefore = $lastToTx?->balance_after ?? 0;

        $toTransaction = FinancialTransaction::create([
            'store_id'         => $toStore->id,
            'account_id'       => $revenueAccount->id,
            'amount'           => $totalAmount,
            'currency'         => 'USD',
            'direction'        => 'debit',
            'balance_before'   => $toBalanceBefore,
            'balance_after'    => $toBalanceBefore - $totalAmount,
            'label'            => $toLabel,
            'description'      => "Facture {$invoiceNumber} - {$invoiceType} de {$fromStore->name} vers {$toStore->name}",
            'status'           => 'validated',
            'transaction_date' => now(),
            'user_id'          => auth()->id(),
            'payment_method_id' => 2, // BANK TRANSFER - pour les transferts internes
            'external_reference' => $invoiceNumber,
        ]);

        // 3) Générer le PDF de la facture
        $movement->load(['items.product', 'fromStore', 'toStore', 'user']);
        $pdf = Pdf::loadView('stock_movements.invoice', [
            'movement' => $movement,
            'invoiceNumber' => $invoiceNumber,
            'totalAmount' => $totalAmount,
            'invoiceType' => $invoiceType,
        ])->setPaper('a4', 'portrait');

        $pdfPath = "stock_movements/invoices/{$invoiceNumber}.pdf";
        Storage::disk('public')->put($pdfPath, $pdf->output());

        // 4) Mettre à jour le mouvement avec les infos de facturation
        $movement->update([
            'total_amount'        => $totalAmount,
            'invoice_number'      => $invoiceNumber,
            'invoice_path'        => $pdfPath,
            'from_transaction_id' => $fromTransaction->id,
            'to_transaction_id'   => $toTransaction->id,
        ]);
    }

    public function cancel(StockMovement $movement)
    {
        if ($movement->status === StockMovement::STATUS_RECEIVED) {
            return redirect()->back()->withErrors("Impossible d'annuler un mouvement déjà réceptionné.");
        }

        DB::transaction(function () use ($movement) {
            $fromStore = $movement->fromStore;

            foreach ($movement->items as $item) {
                $product = Product::find($item->product_id);
                if (!$product || !$fromStore) continue;

                // Restaurer le stock dans le magasin source
                StockBatch::create([
                    'product_id' => $product->id,
                    'store_id'   => $fromStore->id,
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->unit_price,
                ]);
            }

            $movement->update(['status' => StockMovement::STATUS_CANCELLED]);
        });

        return redirect()->route('stock-movements.index')
            ->with('success', __('messages.stock_movement.cancelled_and_restored'));
    }

    public function show(StockMovement $movement)
    {
        $movement->load(['user', 'fromStore', 'toStore', 'items.product', 'fromTransaction', 'toTransaction']);
        return view('stock_movements.show', compact('movement'));
    }

    public function pdf(StockMovement $movement)
    {
        $movement->load(['user', 'fromStore', 'toStore', 'items.product']);
        $pdf = Pdf::loadView('stock_movements.pdf', compact('movement'))
            ->setPaper('a4', 'portrait');
        $filename = 'stock_movement_'.$movement->id.'.pdf';
        return $pdf->download($filename);
    }

    /**
     * Télécharge la facture PDF du transfert
     */
    public function downloadInvoice(StockMovement $movement)
    {
        if (!$movement->invoice_path || !Storage::disk('public')->exists($movement->invoice_path)) {
            return redirect()->back()->withErrors('Facture non disponible.');
        }

        return Storage::disk('public')->download(
            $movement->invoice_path,
            "facture_{$movement->invoice_number}.pdf"
        );
    }
}
