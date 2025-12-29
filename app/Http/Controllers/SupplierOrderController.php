<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\Product;
use App\Models\Store;
use App\Models\StockBatch;
use App\Models\PurchasePriceHistory;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use App\Models\FinancialJournal;
use App\Models\SaleReport;
use App\Models\RawMaterial;
use App\Models\RawMaterialStockBatch;
use App\Models\RawMaterialStockMovement;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\SupplierOrderInvoiceLine;
use App\Models\PriceDifference;
use Illuminate\Support\Facades\Session;
use App\Models\StockTransaction;


class SupplierOrderController extends Controller
{
    public function index(Supplier $supplier)
    {
        $orders = $supplier->supplierOrders()->latest()->paginate(10);
        return view('supplier.orders.index', compact('supplier', 'orders'));
    }

    public function create(Supplier $supplier)
    {
        $stores = Store::all();

        // Si fournisseur de matières premières
        if ($supplier->is_raw_material_supplier) {
            $rawMaterials = $supplier->rawMaterials()->active()->get();
            return view('supplier_orders.create_raw_materials', compact('supplier', 'rawMaterials', 'stores'));
        }

        // Sinon, commande de produits classique
        $products = $supplier->products()->with('brand')->get();
        return view('supplier_orders.create', compact('supplier', 'products', 'stores'));
    }

    /**
     * Get stock levels for supplier products in a specific store (AJAX)
     */
    public function getProductsStock(Supplier $supplier, Store $store)
    {
        $productIds = $supplier->products()->pluck('products.id');

        $stocks = StockBatch::whereIn('product_id', $productIds)
            ->where('store_id', $store->id)
            ->where('quantity', '>', 0)
            ->selectRaw('product_id, SUM(quantity) as total_stock')
            ->groupBy('product_id')
            ->pluck('total_stock', 'product_id');

        return response()->json($stocks);
    }

    public function store(Request $request, Supplier $supplier)
    {
        // Commande de matières premières
        if ($supplier->is_raw_material_supplier) {
            return $this->storeRawMaterialOrder($request, $supplier);
        }

        // Commande de produits classique
        $request->validate([
            'products' => 'required|array',
            'products.*.quantity' => 'nullable|integer|min:0',
            'destination_store_id' => 'required|exists:stores,id',
        ]);

        $order = $supplier->supplierOrders()->create([
            'status' => 'pending',
            'destination_store_id' => $request->destination_store_id,
            'order_type' => SupplierOrder::ORDER_TYPE_PRODUCT,
        ]);

        $syncData = [];
        $supplierProducts = $supplier->products()->get()->keyBy('id');

        foreach ($request->input('products') as $productId => $productData) {
            $quantity = (int) ($productData['quantity'] ?? 0);
            if ($quantity <= 0) continue;
            if (!isset($supplierProducts[$productId])) continue;

            $product = $supplierProducts[$productId];

            $syncData[$productId] = [
                'quantity_ordered' => $quantity,
                'purchase_price' => $product->pivot->purchase_price ?? 0,
                'sale_price' => $product->price,
                'invoice_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $order->products()->sync($syncData);

        return redirect()->route('suppliers.edit', $supplier)->with('success', __('messages.supplier_order.created'));
    }

    /**
     * Créer une commande de matières premières
     */
    protected function storeRawMaterialOrder(Request $request, Supplier $supplier)
    {
        $request->validate([
            'raw_materials' => 'required|array',
            'raw_materials.*.quantity' => 'nullable|numeric|min:0',
            'raw_materials.*.purchase_price' => 'nullable|numeric|min:0',
        ]);

        $order = $supplier->supplierOrders()->create([
            'status' => 'pending',
            'destination_store_id' => null, // Pas de store pour les matières premières
            'order_type' => SupplierOrder::ORDER_TYPE_RAW_MATERIAL,
        ]);

        $syncData = [];

        foreach ($request->input('raw_materials') as $materialId => $data) {
            $quantity = (float) ($data['quantity'] ?? 0);
            if ($quantity <= 0) continue;

            $material = RawMaterial::find($materialId);
            if (!$material || $material->supplier_id !== $supplier->id) continue;

            $syncData[$materialId] = [
                'quantity_ordered' => $quantity,
                'purchase_price' => $data['purchase_price'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $order->rawMaterials()->sync($syncData);

        return redirect()->route('factory.suppliers.edit', $supplier)->with('success', __('messages.supplier_order.created'));
    }

    public function show(Supplier $supplier, SupplierOrder $order)
    {
        $paymentMethods = \App\Models\FinancialPaymentMethod::all();

        if ($order->isRawMaterialOrder()) {
            $order->load('rawMaterials');
            return view('supplier_orders.show_raw_materials', compact('supplier', 'order', 'paymentMethods'));
        }

        $order->load(['products.brand', 'priceDifferences.product']);
        return view('supplier_orders.show', compact('supplier', 'order', 'paymentMethods'));
    }

    public function edit(Supplier $supplier, SupplierOrder $order)
    {
        $products = $supplier->products()->with('brand')->get();
        $stores = Store::all();
        return view('supplier_orders.edit', compact('supplier', 'order', 'products', 'stores'));
    }

    public function update(Request $request, Supplier $supplier, SupplierOrder $order)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.quantity' => 'nullable|integer|min:0',
            'destination_store_id' => 'required|exists:stores,id',
        ]);

        $order->update(['destination_store_id' => $request->destination_store_id]);
        $order->products()->detach();

        foreach ($request->products as $productId => $productData) {
            $quantity = (int) ($productData['quantity'] ?? 0);
            if ($quantity <= 0) continue;

            $product = Product::findOrFail($productId);
            $purchasePrice = $supplier->products()->where('product_id', $productId)->first()?->pivot->purchase_price ?? 0;

            $order->products()->attach($productId, [
                'purchase_price'   => $purchasePrice,
                'sale_price'       => $product->price,
                'quantity_ordered' => $quantity,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        return redirect()->route('suppliers.edit', $supplier)->with('success', __('messages.supplier_order.updated'));
    }

    public function destroy(Supplier $supplier, SupplierOrder $order)
    {
        // Only allow deletion for pending or waiting_reception orders
        if (!in_array($order->status, ['pending', 'waiting_reception'])) {
            return redirect()->back()->with('error', __('messages.supplier_order.cannot_delete'));
        }

        // If waiting_reception, check for partial reception and delete stock batches
        if ($order->status === 'waiting_reception') {
            // Delete stock batches created for this order
            $stockBatches = StockBatch::where('source_supplier_order_id', $order->id)->get();
            foreach ($stockBatches as $batch) {
                // Delete associated stock transactions
                StockTransaction::where('stock_batch_id', $batch->id)->delete();
                $batch->delete();
            }
        }

        // Detach products from the pivot table
        $order->products()->detach();

        // Delete price differences if any
        $order->priceDifferences()->delete();

        // Delete the order
        $order->delete();

        return redirect()->route('suppliers.edit', $supplier)
            ->with('success', __('messages.supplier_order.deleted'));
    }

    public function validateOrder(Supplier $supplier, SupplierOrder $order)
    {
        $order->update(['status' => 'waiting_reception']);
        return back()->with('success', __('messages.supplier_order.validated'));
    }

    public function generatePdf(Supplier $supplier, SupplierOrder $order)
    {
        $pdf = Pdf::loadView('supplier_orders.pdf', compact('supplier', 'order'));
        return $pdf->download("commande_{$order->id}.pdf");
    }

    // Réception physique
    public function receptionForm(Supplier $supplier, SupplierOrder $order)
    {
        if ($order->isRawMaterialOrder()) {
            $order->load('rawMaterials');
            return view('supplier_orders.reception_raw_materials', compact('supplier', 'order'));
        }

        $order->load('products');
        return view('supplier_orders.reception', compact('supplier', 'order'));
    }

    public function storeReception(Request $request, Supplier $supplier, SupplierOrder $order)
    {
        // Réception de matières premières
        if ($order->isRawMaterialOrder()) {
            return $this->storeRawMaterialReception($request, $supplier, $order);
        }

        // Réception de produits classique
        $store = Store::find($order->destination_store_id);
        if (!$store) {
            return back()->withErrors('Magasin de destination introuvable.');
        }

        foreach ($request->input('products', []) as $productId => $qtyReceived) {
            $qtyReceived = (int) $qtyReceived;
            if ($qtyReceived <= 0) continue;

            $product = Product::find($productId);
            if (!$product) continue;

            $order->products()->updateExistingPivot($productId, [
                'quantity_received' => $qtyReceived,
            ]);

            $batch = StockBatch::create([
                'product_id'               => $productId,
                'store_id'                 => $store->id,
                'reseller_id'              => null,
                'quantity'                 => $qtyReceived,
                'unit_price'               => $order->products()->where('product_id', $productId)->first()->pivot->purchase_price ?? 0,
                'source_supplier_order_id' => $order->id,
            ]);

            StockTransaction::create([
                'stock_batch_id' => $batch->id,
                'store_id'       => $store->id,
                'product_id'     => $productId,
                'type'           => 'in',
                'quantity'       => $qtyReceived,
                'reason'         => 'supplier_reception',
                'supplier_id'    => $supplier->id,
                'supplier_order_id' => $order->id,
            ]);
        }

        if ($supplier->type === 'consignment') {
            $order->update(['status' => 'received']);
        } else {
            $order->update(['status' => 'waiting_invoice']);
        }

        $url = route('suppliers.edit', $supplier) . '#orders';

        return redirectBackLevels(2)->with('success', __('messages.supplier_order.received'));
    }

    /**
     * Réception de matières premières
     */
    protected function storeRawMaterialReception(Request $request, Supplier $supplier, SupplierOrder $order)
    {
        $request->validate([
            'raw_materials' => 'required|array',
            'raw_materials.*.quantity_received' => 'nullable|numeric|min:0',
            'raw_materials.*.batch_number' => 'nullable|string|max:255',
            'raw_materials.*.expires_at' => 'nullable|date',
        ]);

        foreach ($request->input('raw_materials', []) as $materialId => $data) {
            $qtyReceived = (float) ($data['quantity_received'] ?? 0);
            if ($qtyReceived <= 0) continue;

            $material = RawMaterial::find($materialId);
            if (!$material) continue;

            // Mettre à jour le pivot avec la quantité reçue
            $order->rawMaterials()->updateExistingPivot($materialId, [
                'quantity_received' => $qtyReceived,
            ]);

            $purchasePrice = $order->rawMaterials()->where('raw_material_id', $materialId)->first()->pivot->purchase_price ?? 0;

            // Créer le lot de stock pour la matière première
            $batch = RawMaterialStockBatch::create([
                'raw_material_id' => $materialId,
                'quantity' => $qtyReceived,
                'unit_price' => $purchasePrice,
                'received_at' => now(),
                'expires_at' => $data['expires_at'] ?? null,
                'batch_number' => $data['batch_number'] ?? null,
                'notes' => "Commande fournisseur #{$order->id}",
            ]);

            // Enregistrer le mouvement de stock
            if ($material->track_stock) {
                RawMaterialStockMovement::create([
                    'raw_material_id' => $materialId,
                    'raw_material_stock_batch_id' => $batch->id,
                    'quantity' => $qtyReceived,
                    'type' => 'purchase',
                    'reference' => "Commande #{$order->id}",
                    'source_type' => SupplierOrder::class,
                    'source_id' => $order->id,
                    'user_id' => auth()->id(),
                ]);
            }
        }

        // Les matières premières sont toujours en achat direct (pas de consignment)
        $order->update(['status' => 'waiting_invoice']);

        return redirect()->route('factory.suppliers.edit', $supplier)
            ->with('success', __('messages.supplier_order.received'));
    }

    public function receptionInvoiceForm(Supplier $supplier, SupplierOrder $order)
    {
        if ($order->isRawMaterialOrder()) {
            $order->load('rawMaterials');
            return view('supplier_orders.invoice_reception_raw_materials', compact('supplier', 'order'));
        }

        $order->load('products');
        return view('supplier_orders.invoice_reception', compact('supplier', 'order'));
    }

    public function storeInvoiceReception(Request $request, Supplier $supplier, SupplierOrder $order)
    {
        // Réception facture matières premières
        if ($order->isRawMaterialOrder()) {
            return $this->storeRawMaterialInvoiceReception($request, $supplier, $order);
        }

        // Réception facture produits classique
        $request->validate([
            'products' => 'required|array',
            'products.*.price_invoiced' => 'required|numeric|min:0',
            'update_reference_price' => 'nullable|array',
            'invoice_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // max 5MB
        ]);

        $order->load('products');

        // Sauvegarde du fichier facture
        if ($request->hasFile('invoice_file')) {
            $path = $request->file('invoice_file')->store('invoices', 'public');
            $order->invoice_file = $path;
            $order->save();
        }

        foreach ($request->input('products') as $productId => $data) {
            $product = $order->products->find($productId);
            if (!$product) continue;

            $priceInvoiced = (float) $data['price_invoiced'];
            $expectedPrice = $product->pivot->purchase_price;
            $updateRef     = !empty($request->input('update_reference_price')[$productId]);

            // Mise à jour du lot
            StockBatch::where('source_supplier_order_id', $order->id)
                ->where('product_id', $productId)
                ->update(['unit_price' => $priceInvoiced]);

            // Mise à jour du prix fournisseur si coché
            if ($updateRef) {
                $oldPrice = $expectedPrice;

                PurchasePriceHistory::create([
                    'supplier_id' => $supplier->id,
                    'product_id'  => $productId,
                    'old_price'   => $oldPrice,
                    'new_price'   => $priceInvoiced,
                    'changed_at'  => now(),
                ]);
            }

            if($priceInvoiced != $expectedPrice)
            {
                SupplierOrderInvoiceLine::create([
                    'supplier_order_id' => $order->id,
                    'product_id'        => $productId,
                    'reference_price'   => $expectedPrice,
                    'invoiced_price'    => $priceInvoiced,
                    'update_reference'  => $updateRef,
                ]);

                $order->products()->updateExistingPivot($productId, [
                    'purchase_price' => $priceInvoiced,
                ]);
            }
        }

        $order->update(['status' => 'received']);

        return redirectBackLevels(2)->with('success', __('messages.supplier_order.invoice_received'));
    }

    /**
     * Réception facture matières premières
     */
    protected function storeRawMaterialInvoiceReception(Request $request, Supplier $supplier, SupplierOrder $order)
    {
        $request->validate([
            'raw_materials' => 'required|array',
            'raw_materials.*.price_invoiced' => 'required|numeric|min:0',
            'invoice_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $order->load('rawMaterials');

        // Sauvegarde du fichier facture
        if ($request->hasFile('invoice_file')) {
            $path = $request->file('invoice_file')->store('invoices', 'public');
            $order->invoice_file = $path;
            $order->save();
        }

        foreach ($request->input('raw_materials') as $materialId => $data) {
            $material = $order->rawMaterials->find($materialId);
            if (!$material) continue;

            $priceInvoiced = (float) $data['price_invoiced'];

            // Mettre à jour le prix facturé dans le pivot
            $order->rawMaterials()->updateExistingPivot($materialId, [
                'invoice_price' => $priceInvoiced,
            ]);

            // Mettre à jour le prix unitaire dans les lots de stock créés
            // (On identifie par la note qui contient l'ID de commande)
            RawMaterialStockBatch::where('raw_material_id', $materialId)
                ->where('notes', "Commande fournisseur #{$order->id}")
                ->update(['unit_price' => $priceInvoiced]);
        }

        $order->update(['status' => 'received']);

        return redirect()->route('factory.suppliers.edit', $supplier)
            ->with('success', __('messages.supplier_order.invoice_received'));
    }


    public function overview(Request $request)
    {
        $perPage = 10;

        // ====== Supplier Orders ======
        $orderStatuses = ['pending','waiting_reception','waiting_invoice','received_unpaid','received_paid'];
        $ordersByStatus = [];
        foreach ($orderStatuses as $status) {
            $query = SupplierOrder::with(['supplier','products','destinationStore'])->latest();
            if ($status === 'received_unpaid') {
                $query->where('status','received')->where('is_paid',false);
            } elseif ($status === 'received_paid') {
                $query->where('status','received')->where('is_paid',true);
            } else {
                $query->where('status',$status);
            }
            $ordersByStatus[$status] = $query->paginate($perPage,['*'],$status);
        }

        $totalPendingAmount = SupplierOrder::with('products')
            ->whereIn('status', ['waiting_reception','waiting_invoice'])
            ->get()
            ->sum(fn($order) => $order->products->sum(fn($p) => ($p->pivot->purchase_price ?? 0) * ($p->pivot->quantity_ordered ?? 0)));
        $totalUnpaidReceivedAmount = SupplierOrder::where('status', 'received')
            ->where('is_paid', false)
            ->get()
            ->sum(fn($order) => $order->invoicedAmount());

        // ====== Sale Reports ======
        $saleReportStatuses = ['waiting_invoice','invoiced_unpaid','invoiced_paid'];
        $saleReportsByStatus = [];
        foreach ($saleReportStatuses as $status) {
            $query = SaleReport::with(['supplier','store'])->latest();
            if ($status === 'invoiced_unpaid') {
                $query->where('status','invoiced')->where('is_paid',false);
            } elseif ($status === 'invoiced_paid') {
                $query->where('status','invoiced')->where('is_paid',true);
            } else {
                $query->where('status',$status);
            }
            $saleReportsByStatus[$status] = $query->paginate($perPage,['*'],$status);
        }

        $totalPendingSaleReportAmount = SaleReport::where('status','waiting_invoice')->sum('total_amount_theoretical');
        $totalUnpaidInvoicedSaleReportAmount = SaleReport::where('status','invoiced')->where('is_paid',false)->sum('total_amount_invoiced');

        $paymentMethods = \App\Models\FinancialPaymentMethod::all();

        return view('supplier_orders.overview', compact(
            'ordersByStatus',
            'totalPendingAmount',
            'totalUnpaidReceivedAmount',
            'saleReportsByStatus',
            'totalPendingSaleReportAmount',
            'totalUnpaidInvoicedSaleReportAmount',
            'paymentMethods'
        ));
    }


    public function markPaid(Supplier $supplier, SupplierOrder $order)
    {
        // Récupération du store de destination
        $storeId = $order->destination_store_id;

        // Montant facturé
        //$amount = $order->products->sum(fn($p) => ($p->pivot->price_invoiced ?? $p->pivot->purchase_price ?? 0) * ($p->pivot->quantity_ordered ?? 0));
        $amount = $order->invoicedAmount();
        
        // Récupérer le compte 401
        $account = \App\Models\FinancialAccount::where('code', '401')->firstOrFail();

        // Solde précédent
        $lastTransaction = \App\Models\FinancialTransaction::where('store_id', $storeId)
            ->latest('transaction_date')
            ->first();
        $balanceBefore = $lastTransaction?->balance_after ?? 0;
        $balanceAfter = $balanceBefore - $amount;

        // Création de la transaction
        $transaction = \App\Models\FinancialTransaction::create([
            'store_id' => $storeId,
            'account_id' => $account->id,
            'amount' => $amount,
            'currency' => 'USD',
            'direction' => 'debit',
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'label' => 'Paiement commande fournisseur : ' . $supplier->name,
            'description' => "Paiement de la commande #{$order->id} pour {$supplier->name}",
            'status' => 'validated',
            'transaction_date' => now(),
            'payment_method_id' => 1, // tu peux mettre la méthode par défaut si nécessaire
            'user_id' => auth()->id(),
            'external_reference' => route('supplier-orders.show', [$supplier, $order]),
        ]);

        // Ajouter la facture comme pièce jointe
        if ($order->invoice_file) {
            $transaction->attachments()->create([
                'path' => $order->invoice_file,
                'file_type' => \Illuminate\Support\Facades\Storage::mimeType($order->invoice_file),
                'uploaded_by' => auth()->id(),
            ]);
        }

        // Mettre à jour le statut de paiement de la commande
        $order->update(['is_paid' => true]);

        return redirect()->back()->with('success', __('messages.supplier_order.marked_paid'));
    }

    /**
     * Revert a waiting_reception order back to pending status
     */
    public function revertToPending(Supplier $supplier, SupplierOrder $order)
    {
        // Only allow for waiting_reception orders
        if ($order->status !== 'waiting_reception') {
            return redirect()->back()->with('error', __('messages.supplier_order.cannot_revert'));
        }

        // Delete any stock batches created during partial reception
        $stockBatches = StockBatch::where('source_supplier_order_id', $order->id)->get();
        foreach ($stockBatches as $batch) {
            // Delete associated stock transactions
            StockTransaction::where('stock_batch_id', $batch->id)->delete();
            $batch->delete();
        }

        // Reset quantity_received on all products
        $order->products()->each(function ($product) use ($order) {
            $order->products()->updateExistingPivot($product->id, [
                'quantity_received' => null,
            ]);
        });

        // Update status back to pending
        $order->update(['status' => 'pending']);

        return redirect()->route('supplier-orders.show', [$supplier, $order])
            ->with('success', __('messages.supplier_order.reverted_to_pending'));
    }
}
