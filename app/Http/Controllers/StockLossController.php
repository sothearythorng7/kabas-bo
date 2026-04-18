<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockLoss;
use App\Models\StockLossItem;
use App\Models\StockTransaction;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockLossController extends Controller
{
    public function index(Request $request)
    {
        $query = StockLoss::with(['store', 'createdBy', 'supplier'])
            ->latest();

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $losses = $query->paginate(20)->withQueryString();
        $stores = Store::orderBy('name')->get();

        return view('stock_losses.index', compact('losses', 'stores'));
    }

    public function create()
    {
        $stores = Store::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('stock_losses.create', compact('stores', 'suppliers'));
    }

    public function searchProducts(Request $request, Store $store)
    {
        $request->validate(['q' => 'required|string|min:1']);

        $locale = app()->getLocale();

        $products = Product::search($request->q)
            ->take(20)
            ->get()
            ->map(function ($product) use ($store, $locale) {
                $batches = StockBatch::where('product_id', $product->id)
                    ->where('store_id', $store->id)
                    ->where('quantity', '>', 0)
                    ->get();

                $stock = $batches->sum('quantity');
                if ($stock <= 0) return null;

                $totalValue = $batches->sum(fn($b) => $b->quantity * ($b->unit_price ?? 0));
                $avgCost = $stock > 0 ? round($totalValue / $stock, 5) : 0;

                $name = is_array($product->name)
                    ? ($product->name[$locale] ?? reset($product->name))
                    : $product->name;

                return [
                    'id' => $product->id,
                    'name' => $name,
                    'ean' => $product->ean,
                    'brand' => $product->brand?->name,
                    'stock' => $stock,
                    'avg_cost' => $avgCost,
                ];
            })
            ->filter()
            ->values();

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'type' => 'required|in:pure_loss,supplier_refund',
            'supplier_id' => 'required_if:type,supplier_refund|nullable|exists:suppliers,id',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_cost' => 'required|numeric|min:0',
            'products.*.loss_reason' => 'nullable|string|max:255',
        ]);

        $loss = DB::transaction(function () use ($request) {
            $stockLoss = StockLoss::create([
                'store_id' => $request->store_id,
                'created_by_user_id' => auth()->id(),
                'type' => $request->type,
                'supplier_id' => $request->type === 'supplier_refund' ? $request->supplier_id : null,
                'status' => 'draft',
                'reference' => StockLoss::generateReference(),
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);

            foreach ($request->products as $item) {
                if ((int) $item['quantity'] <= 0) continue;

                StockLossItem::create([
                    'stock_loss_id' => $stockLoss->id,
                    'product_id' => $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'loss_reason' => $item['loss_reason'] ?? null,
                ]);
            }

            return $stockLoss;
        });

        return redirect()->route('stock-losses.show', $loss)
            ->with('success', __('messages.stock_loss.created'));
    }

    public function show(StockLoss $stockLoss)
    {
        $stockLoss->load(['store', 'createdBy', 'supplier', 'items.product.brand', 'financialTransaction', 'refundTransaction']);

        return view('stock_losses.show', compact('stockLoss'));
    }

    public function edit(StockLoss $stockLoss)
    {
        if (!$stockLoss->isEditable()) {
            return redirect()->route('stock-losses.show', $stockLoss)
                ->with('error', __('messages.stock_loss.cannot_edit_validated'));
        }

        $stockLoss->load(['store', 'items.product.brand']);
        $stores = Store::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('stock_losses.edit', compact('stockLoss', 'stores', 'suppliers'));
    }

    public function update(Request $request, StockLoss $stockLoss)
    {
        if (!$stockLoss->isEditable()) {
            return redirect()->route('stock-losses.show', $stockLoss)
                ->with('error', __('messages.stock_loss.cannot_edit_validated'));
        }

        $request->validate([
            'type' => 'required|in:pure_loss,supplier_refund',
            'supplier_id' => 'required_if:type,supplier_refund|nullable|exists:suppliers,id',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_cost' => 'required|numeric|min:0',
            'products.*.loss_reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $stockLoss) {
            $stockLoss->update([
                'type' => $request->type,
                'supplier_id' => $request->type === 'supplier_refund' ? $request->supplier_id : null,
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);

            // Delete old items and recreate
            $stockLoss->items()->delete();

            foreach ($request->products as $item) {
                if ((int) $item['quantity'] <= 0) continue;

                StockLossItem::create([
                    'stock_loss_id' => $stockLoss->id,
                    'product_id' => $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'loss_reason' => $item['loss_reason'] ?? null,
                ]);
            }
        });

        return redirect()->route('stock-losses.show', $stockLoss)
            ->with('success', __('messages.stock_loss.updated'));
    }

    public function validateLoss(StockLoss $stockLoss)
    {
        if ($stockLoss->isValidated()) {
            return redirect()->back()->with('error', __('messages.stock_loss.already_validated'));
        }

        DB::transaction(function () use ($stockLoss) {
            $stockLoss->load('items');

            // Deduct stock FIFO for each item
            foreach ($stockLoss->items as $item) {
                $this->deductStock($item->product_id, $stockLoss->store_id, $item->quantity);
            }

            // Create financial transaction (expense)
            $account = FinancialAccount::where('code', '60001')->first();
            $totalValue = $stockLoss->total_value;

            $lastTransaction = FinancialTransaction::where('account_id', $account->id)
                ->where('store_id', $stockLoss->store_id)
                ->orderBy('id', 'desc')
                ->first();

            $balanceBefore = $lastTransaction ? $lastTransaction->balance_after : 0;

            $transaction = FinancialTransaction::create([
                'store_id' => $stockLoss->store_id,
                'account_id' => $account->id,
                'amount' => $totalValue,
                'currency' => 'USD',
                'direction' => 'debit',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $totalValue,
                'label' => "Stock Loss #{$stockLoss->reference}",
                'description' => "Stock loss at {$stockLoss->store->name} - {$stockLoss->items->count()} products, {$stockLoss->total_quantity} units",
                'status' => 'validated',
                'transaction_date' => now(),
                'user_id' => auth()->id(),
            ]);

            $stockLoss->update([
                'status' => 'validated',
                'validated_at' => now(),
                'financial_transaction_id' => $transaction->id,
            ]);
        });

        return redirect()->route('stock-losses.show', $stockLoss)
            ->with('success', __('messages.stock_loss.validated'));
    }

    public function requestRefund(StockLoss $stockLoss)
    {
        if (!$stockLoss->isSupplierRefund() || $stockLoss->status !== 'validated') {
            return redirect()->back()->with('error', __('messages.stock_loss.cannot_request_refund'));
        }

        $stockLoss->update([
            'status' => 'refund_requested',
            'refund_requested_at' => now(),
        ]);

        return redirect()->route('stock-losses.show', $stockLoss)
            ->with('success', __('messages.stock_loss.refund_requested'));
    }

    public function confirmRefund(Request $request, StockLoss $stockLoss)
    {
        if (!in_array($stockLoss->status, ['refund_requested', 'validated']) || !$stockLoss->isSupplierRefund()) {
            return redirect()->back()->with('error', __('messages.stock_loss.cannot_confirm_refund'));
        }

        $request->validate([
            'refund_amount' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($request, $stockLoss) {
            $account = FinancialAccount::where('code', '60001')->first();

            $lastTransaction = FinancialTransaction::where('account_id', $account->id)
                ->where('store_id', $stockLoss->store_id)
                ->orderBy('id', 'desc')
                ->first();

            $balanceBefore = $lastTransaction ? $lastTransaction->balance_after : 0;
            $refundAmount = $request->refund_amount;

            $transaction = FinancialTransaction::create([
                'store_id' => $stockLoss->store_id,
                'account_id' => $account->id,
                'amount' => $refundAmount,
                'currency' => 'USD',
                'direction' => 'credit',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore - $refundAmount,
                'label' => "Supplier Refund - Stock Loss #{$stockLoss->reference}",
                'description' => "Refund from {$stockLoss->supplier->name} for stock loss #{$stockLoss->reference}",
                'status' => 'validated',
                'transaction_date' => now(),
                'user_id' => auth()->id(),
            ]);

            $stockLoss->update([
                'status' => 'refund_received',
                'refund_received_at' => now(),
                'refund_amount' => $refundAmount,
                'refund_transaction_id' => $transaction->id,
            ]);
        });

        return redirect()->route('stock-losses.show', $stockLoss)
            ->with('success', __('messages.stock_loss.refund_confirmed'));
    }

    public function destroy(StockLoss $stockLoss)
    {
        if ($stockLoss->isValidated()) {
            return redirect()->back()->with('error', __('messages.stock_loss.cannot_delete_validated'));
        }

        $stockLoss->delete();

        return redirect()->route('stock-losses.index')
            ->with('success', __('messages.stock_loss.deleted'));
    }

    private function deductStock(int $productId, int $storeId, int $quantity): void
    {
        $remainingQuantity = $quantity;

        $batches = StockBatch::where('product_id', $productId)
            ->where('store_id', $storeId)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) break;

            $deductQuantity = min($batch->quantity, $remainingQuantity);
            $batch->decrement('quantity', $deductQuantity);
            $remainingQuantity -= $deductQuantity;

            StockTransaction::create([
                'stock_batch_id' => $batch->id,
                'store_id' => $storeId,
                'product_id' => $productId,
                'type' => 'out',
                'quantity' => $deductQuantity,
                'reason' => 'stock_loss',
            ]);
        }
    }
}
