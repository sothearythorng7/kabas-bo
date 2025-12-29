<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\StockBatch;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SupplierReturnController extends Controller
{
    /**
     * Display a listing of returns for a supplier.
     */
    public function index(Supplier $supplier)
    {
        $returns = $supplier->returns()
            ->with(['store', 'createdBy'])
            ->latest()
            ->paginate(10);

        return view('supplier_returns.index', compact('supplier', 'returns'));
    }

    /**
     * Show the form for creating a new return.
     */
    public function create(Supplier $supplier)
    {
        $stores = Store::all();
        return view('supplier_returns.create', compact('supplier', 'stores'));
    }

    /**
     * Get products with stock for a specific store (AJAX).
     */
    public function getProductsWithStock(Supplier $supplier, Store $store)
    {
        $locale = app()->getLocale();

        // Get supplier products with their stock in the selected store
        $products = $supplier->products()
            ->with('brand')
            ->get()
            ->map(function ($product) use ($store, $locale) {
                $stock = StockBatch::where('product_id', $product->id)
                    ->where('store_id', $store->id)
                    ->where('quantity', '>', 0)
                    ->sum('quantity');

                // Handle multilingual name
                $name = is_array($product->name)
                    ? ($product->name[$locale] ?? reset($product->name))
                    : $product->name;

                return [
                    'id' => $product->id,
                    'name' => $name,
                    'ean' => $product->ean,
                    'brand' => $product->brand?->name,
                    'purchase_price' => $product->pivot->purchase_price ?? 0,
                    'stock' => $stock,
                ];
            })
            ->filter(fn($p) => $p['stock'] > 0)
            ->values();

        return response()->json($products);
    }

    /**
     * Store a newly created return (draft).
     */
    public function store(Request $request, Supplier $supplier)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $return = DB::transaction(function () use ($request, $supplier) {
            $supplierReturn = SupplierReturn::create([
                'supplier_id' => $supplier->id,
                'store_id' => $request->store_id,
                'created_by_user_id' => auth()->id(),
                'status' => 'draft',
                'notes' => $request->notes,
            ]);

            // Get supplier products with purchase prices
            $supplierProducts = $supplier->products()->get()->keyBy('id');

            foreach ($request->products as $item) {
                $productId = $item['product_id'];
                $quantity = (int) $item['quantity'];

                if ($quantity <= 0) continue;

                $purchasePrice = $supplierProducts[$productId]->pivot->purchase_price ?? 0;

                SupplierReturnItem::create([
                    'supplier_return_id' => $supplierReturn->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $purchasePrice,
                ]);
            }

            return $supplierReturn;
        });

        return redirect()->route('supplier-returns.show', [$supplier, $return])
            ->with('success', __('messages.supplier_return.created'));
    }

    /**
     * Display the specified return.
     */
    public function show(Supplier $supplier, SupplierReturn $return)
    {
        $return->load(['store', 'createdBy', 'items.product.brand']);
        return view('supplier_returns.show', compact('supplier', 'return'));
    }

    /**
     * Show the form for editing a pending return.
     */
    public function edit(Supplier $supplier, SupplierReturn $return)
    {
        if (!$return->isEditable()) {
            return redirect()->route('supplier-returns.show', [$supplier, $return])
                ->with('error', __('messages.supplier_return.cannot_edit_validated'));
        }

        $return->load(['store', 'items.product.brand']);

        // Get current stock for each product (add back the return quantities to show available)
        $locale = app()->getLocale();
        $products = $supplier->products()
            ->with('brand')
            ->get()
            ->map(function ($product) use ($return, $locale) {
                $stock = StockBatch::where('product_id', $product->id)
                    ->where('store_id', $return->store_id)
                    ->where('quantity', '>', 0)
                    ->sum('quantity');

                // Add back the quantity already in return (since it was deducted)
                $returnItem = $return->items->where('product_id', $product->id)->first();
                $returnQty = $returnItem ? $returnItem->quantity : 0;

                $name = is_array($product->name)
                    ? ($product->name[$locale] ?? reset($product->name))
                    : $product->name;

                return [
                    'id' => $product->id,
                    'name' => $name,
                    'ean' => $product->ean,
                    'brand' => $product->brand?->name,
                    'purchase_price' => $product->pivot->purchase_price ?? 0,
                    'stock' => $stock + $returnQty, // Available stock = current + already returned
                    'return_qty' => $returnQty,
                ];
            })
            ->filter(fn($p) => $p['stock'] > 0 || $p['return_qty'] > 0)
            ->values();

        return view('supplier_returns.edit', compact('supplier', 'return', 'products'));
    }

    /**
     * Update a pending return and adjust stock accordingly.
     */
    public function update(Request $request, Supplier $supplier, SupplierReturn $return)
    {
        if (!$return->isEditable()) {
            return redirect()->route('supplier-returns.show', [$supplier, $return])
                ->with('error', __('messages.supplier_return.cannot_edit_validated'));
        }

        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($request, $supplier, $return) {
            // Get current items for comparison
            $currentItems = $return->items->keyBy('product_id');

            // Get supplier products with purchase prices
            $supplierProducts = $supplier->products()->get()->keyBy('id');

            foreach ($request->products as $item) {
                $productId = $item['product_id'];
                $newQuantity = (int) $item['quantity'];
                $currentItem = $currentItems->get($productId);
                $oldQuantity = $currentItem ? $currentItem->quantity : 0;

                $difference = $newQuantity - $oldQuantity;

                if ($difference == 0) continue;

                if ($difference > 0) {
                    // Need to deduct more stock
                    $this->deductStock($productId, $return->store_id, $difference);
                } else {
                    // Need to restore stock (difference is negative)
                    $this->restoreStock($productId, $return->store_id, abs($difference));
                }

                // Update or create item
                if ($newQuantity > 0) {
                    $purchasePrice = $supplierProducts[$productId]->pivot->purchase_price ?? 0;

                    if ($currentItem) {
                        $currentItem->update(['quantity' => $newQuantity]);
                    } else {
                        SupplierReturnItem::create([
                            'supplier_return_id' => $return->id,
                            'product_id' => $productId,
                            'quantity' => $newQuantity,
                            'unit_price' => $purchasePrice,
                        ]);
                    }
                } elseif ($currentItem) {
                    // Remove item if quantity is 0
                    $currentItem->delete();
                }
            }

            // Update notes
            $return->update(['notes' => $request->notes]);
        });

        return redirect()->route('supplier-returns.show', [$supplier, $return])
            ->with('success', __('messages.supplier_return.updated'));
    }

    /**
     * Validate the return (just change status, stock already deducted).
     */
    public function validateReturn(Request $request, Supplier $supplier, SupplierReturn $return)
    {
        if ($return->isValidated()) {
            return redirect()->back()->with('error', __('messages.supplier_return.already_validated'));
        }

        if (!$return->isPending()) {
            return redirect()->back()->with('error', __('messages.supplier_return.cannot_validate'));
        }

        DB::transaction(function () use ($return) {
            // Generate PDF
            $pdfPath = $this->generatePdf($return);

            $return->update([
                'status' => 'validated',
                'validated_at' => now(),
                'pdf_path' => $pdfPath,
            ]);
        });

        return redirect()->route('supplier-returns.show', [$supplier, $return])
            ->with('success', __('messages.supplier_return.validated'));
    }

    /**
     * Deduct stock using FIFO method.
     */
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
                'reason' => 'supplier_return',
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Restore stock (when reducing return quantity).
     */
    private function restoreStock(int $productId, int $storeId, int $quantity): void
    {
        // Find the most recent batch for this product/store or create a new one
        $batch = StockBatch::where('product_id', $productId)
            ->where('store_id', $storeId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($batch) {
            $batch->increment('quantity', $quantity);
        } else {
            // Create new batch if none exists
            $product = Product::find($productId);
            $batch = StockBatch::create([
                'product_id' => $productId,
                'store_id' => $storeId,
                'quantity' => $quantity,
                'unit_price' => $product->price ?? 0,
                'label' => 'Restored from return adjustment',
            ]);
        }

        StockTransaction::create([
            'stock_batch_id' => $batch->id,
            'store_id' => $storeId,
            'product_id' => $productId,
            'type' => 'in',
            'quantity' => $quantity,
            'reason' => 'supplier_return_adjustment',
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Generate PDF for the return.
     */
    protected function generatePdf(SupplierReturn $return)
    {
        $return->load(['supplier', 'store', 'items.product.brand', 'createdBy']);

        $pdf = Pdf::loadView('supplier_returns.pdf', [
            'return' => $return,
        ]);

        $filename = sprintf(
            'RETURN_%s_%s_%s.pdf',
            str_replace(' ', '_', strtoupper($return->supplier->name)),
            $return->store->name,
            $return->created_at->format('dmY')
        );

        $path = "supplier_returns/{$filename}";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Download the PDF for a validated return.
     */
    public function downloadPdf(Supplier $supplier, SupplierReturn $return)
    {
        if (!$return->pdf_path || !Storage::disk('public')->exists($return->pdf_path)) {
            // Generate PDF if it doesn't exist
            $return->load(['supplier', 'store', 'items.product.brand', 'createdBy']);

            $pdf = Pdf::loadView('supplier_returns.pdf', [
                'return' => $return,
            ]);

            return $pdf->download(sprintf(
                'RETURN_%s_%s_%s.pdf',
                str_replace(' ', '_', strtoupper($return->supplier->name)),
                $return->store->name,
                $return->created_at->format('dmY')
            ));
        }

        return Storage::disk('public')->download($return->pdf_path);
    }

    /**
     * Delete a draft return.
     */
    public function destroy(Supplier $supplier, SupplierReturn $return)
    {
        if ($return->isValidated()) {
            return redirect()->back()->with('error', __('messages.supplier_return.cannot_delete_validated'));
        }

        $return->delete();

        return redirect()->route('suppliers.edit', ['supplier' => $supplier, '#returns'])
            ->with('success', __('messages.supplier_return.deleted'));
    }
}
