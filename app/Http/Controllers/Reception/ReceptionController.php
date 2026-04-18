<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SupplierOrder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockTransaction;
use App\Models\Refill;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\Store;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\Brand;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\RawMaterial;
use App\Models\RawMaterialStockBatch;
use App\Models\RawMaterialStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceptionController extends Controller
{

    /**
     * Show login form with PIN pad
     */
    public function loginForm()
    {
        if (session('reception_user_id')) {
            return redirect()->route('reception.home');
        }

        return view('reception.login');
    }

    /**
     * Authenticate user via PIN
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:6',
        ]);

        $user = User::where('pin_code', $request->pin)
            ->whereNotNull('store_id')
            ->first();

        if (!$user) {
            return back()->with('error', 'Invalid PIN code');
        }

        session([
            'reception_user_id' => $user->id,
            'reception_user_name' => $user->name,
            'reception_store_id' => $user->store_id,
        ]);

        return redirect()->route('reception.home');
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->forget(['reception_user_id', 'reception_user_name', 'reception_store_id']);
        return redirect()->route('reception.login');
    }

    /**
     * Home page with menu buttons
     */
    public function home()
    {
        $storeIds = $this->getAccessibleStoreIds();

        $pendingOrdersCount = SupplierOrder::whereIn('destination_store_id', $storeIds)
            ->where('status', 'waiting_reception')
            ->where('order_type', SupplierOrder::ORDER_TYPE_PRODUCT)
            ->count();

        // Count pending factory orders (raw materials)
        $pendingFactoryOrdersCount = SupplierOrder::whereIn('destination_store_id', $storeIds)
            ->where('status', 'waiting_reception')
            ->where('order_type', SupplierOrder::ORDER_TYPE_RAW_MATERIAL)
            ->count();

        // Count all pending transfers (not restricted by store)
        $pendingTransfersCount = StockMovement::whereIn('status', [StockMovement::STATUS_VALIDATED, StockMovement::STATUS_IN_TRANSIT])
            ->count();

        return view('reception.home', [
            'userName' => session('reception_user_name'),
            'pendingOrdersCount' => $pendingOrdersCount,
            'pendingFactoryOrdersCount' => $pendingFactoryOrdersCount,
            'pendingTransfersCount' => $pendingTransfersCount,
        ]);
    }

    /**
     * List supplier orders waiting for reception
     */
    public function ordersList()
    {
        $storeIds = $this->getAccessibleStoreIds();

        $orders = SupplierOrder::with(['supplier', 'destinationStore'])
            ->whereIn('destination_store_id', $storeIds)
            ->where('status', 'waiting_reception')
            ->where('order_type', SupplierOrder::ORDER_TYPE_PRODUCT)
            ->whereHas('supplier', fn ($q) => $q->where('type', 'buyer'))
            ->latest()
            ->get();

        // Check which orders have partial reception (at least one StockBatch created)
        $orderIds = $orders->pluck('id');
        $ordersWithPartialReception = StockBatch::whereIn('source_supplier_order_id', $orderIds)
            ->where('quantity', '>', 0)
            ->distinct()
            ->pluck('source_supplier_order_id')
            ->toArray();

        return view('reception.orders.index', compact('orders', 'ordersWithPartialReception'));
    }

    /**
     * Show single order for reception
     */
    public function orderShow(SupplierOrder $order)
    {
        $storeIds = $this->getAccessibleStoreIds();

        if (!in_array($order->destination_store_id, $storeIds)) {
            abort(403, 'Access denied');
        }

        if ($order->status !== 'waiting_reception') {
            return redirect()->route('reception.orders')
                ->with('error', 'This order is not available for reception');
        }

        $order->load(['supplier', 'products.images', 'destinationStore']);

        // Get already received quantities from StockBatch
        $receivedQuantities = StockBatch::where('source_supplier_order_id', $order->id)
            ->pluck('quantity', 'product_id')
            ->toArray();

        return view('reception.orders.show', compact('order', 'receivedQuantities'));
    }

    /**
     * Receive a single item (AJAX) - creates StockBatch immediately
     */
    public function receiveItem(Request $request, SupplierOrder $order)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity_received' => 'required|integer|min:0',
        ]);

        $storeIds = $this->getAccessibleStoreIds();

        if (!in_array($order->destination_store_id, $storeIds)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if ($order->status !== 'waiting_reception') {
            return response()->json(['error' => 'Order not available for reception'], 400);
        }

        $productId = $request->product_id;
        $quantityReceived = $request->quantity_received;

        // Get purchase price from order pivot
        $orderProduct = $order->products()->where('product_id', $productId)->first();
        if (!$orderProduct) {
            return response()->json(['error' => 'Product not found in order'], 404);
        }
        $purchasePrice = $orderProduct->pivot->purchase_price ?? 0;

        DB::transaction(function () use ($order, $productId, $quantityReceived, $purchasePrice) {
            // Find or create StockBatch for this product/order
            $batch = StockBatch::where('source_supplier_order_id', $order->id)
                ->where('product_id', $productId)
                ->first();

            $oldQuantity = $batch ? $batch->quantity : 0;
            $difference = $quantityReceived - $oldQuantity;

            if ($batch) {
                // Update existing batch
                $batch->update(['quantity' => $quantityReceived]);
            } else {
                // Create new batch
                $batch = StockBatch::create([
                    'product_id' => $productId,
                    'store_id' => $order->destination_store_id,
                    'quantity' => $quantityReceived,
                    'unit_price' => $purchasePrice,
                    'source_supplier_order_id' => $order->id,
                ]);
            }

            // Update pivot table
            $order->products()->updateExistingPivot($productId, [
                'quantity_received' => $quantityReceived,
            ]);

            // Create StockTransaction if there's a difference
            if ($difference != 0) {
                StockTransaction::create([
                    'stock_batch_id' => $batch->id,
                    'store_id' => $order->destination_store_id,
                    'product_id' => $productId,
                    'type' => $difference > 0 ? 'in' : 'out',
                    'quantity' => abs($difference),
                    'reason' => 'supplier_reception',
                    'supplier_id' => $order->supplier_id,
                    'supplier_order_id' => $order->id,
                    'user_id' => session('reception_user_id'),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'new_quantity' => $quantityReceived,
        ]);
    }

    /**
     * Finalize order reception - change status
     */
    public function finalizeOrder(SupplierOrder $order)
    {
        $storeIds = $this->getAccessibleStoreIds();

        if (!in_array($order->destination_store_id, $storeIds)) {
            return redirect()->route('reception.orders')
                ->with('error', 'Access denied');
        }

        if ($order->status !== 'waiting_reception') {
            return redirect()->route('reception.orders')
                ->with('error', 'Order cannot be finalized');
        }

        // Set any untouched products (NULL quantity_received) to 0
        foreach ($order->products as $product) {
            if (is_null($product->pivot->quantity_received)) {
                $order->products()->updateExistingPivot($product->id, [
                    'quantity_received' => 0,
                ]);
            }
        }

        // Change status based on supplier type
        if ($order->supplier->type === 'consignment') {
            $order->status = 'received';
        } else {
            $order->status = 'waiting_invoice';
        }
        $order->save();

        return redirect()->route('reception.orders')
            ->with('success', 'Order finalized successfully');
    }

    // =====================================================
    // FACTORY ORDERS (Raw Materials)
    // =====================================================

    /**
     * List factory orders (raw materials) waiting for reception
     */
    public function factoryOrdersList()
    {
        $storeIds = $this->getAccessibleStoreIds();

        $orders = SupplierOrder::with(['supplier', 'destinationStore'])
            ->whereIn('destination_store_id', $storeIds)
            ->where('status', 'waiting_reception')
            ->where('order_type', SupplierOrder::ORDER_TYPE_RAW_MATERIAL)
            ->latest()
            ->get();

        // Check which orders have partial reception
        $orderIds = $orders->pluck('id');
        $ordersWithPartialReception = \App\Models\RawMaterialStockBatch::whereIn('source_supplier_order_id', $orderIds)
            ->where('quantity', '>', 0)
            ->distinct()
            ->pluck('source_supplier_order_id')
            ->toArray();

        return view('reception.factory-orders.index', compact('orders', 'ordersWithPartialReception'));
    }

    /**
     * Show single factory order for reception
     */
    public function factoryOrderShow(SupplierOrder $order)
    {
        $storeIds = $this->getAccessibleStoreIds();

        if (!in_array($order->destination_store_id, $storeIds)) {
            abort(403, 'Access denied');
        }

        if ($order->status !== 'waiting_reception') {
            return redirect()->route('reception.factory-orders')
                ->with('error', 'This order is not available for reception');
        }

        $order->load(['supplier', 'rawMaterials', 'destinationStore']);

        // Get already received quantities from RawMaterialStockBatch
        $receivedQuantities = \App\Models\RawMaterialStockBatch::where('source_supplier_order_id', $order->id)
            ->pluck('quantity', 'raw_material_id')
            ->toArray();

        return view('reception.factory-orders.show', compact('order', 'receivedQuantities'));
    }

    /**
     * Receive a single raw material item (AJAX)
     */
    public function receiveFactoryItem(Request $request, SupplierOrder $order)
    {
        $request->validate([
            'raw_material_id' => 'required|exists:raw_materials,id',
            'quantity_received' => 'required|numeric|min:0',
        ]);

        $storeIds = $this->getAccessibleStoreIds();

        if (!in_array($order->destination_store_id, $storeIds)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if ($order->status !== 'waiting_reception') {
            return response()->json(['error' => 'Order not available for reception'], 400);
        }

        $rawMaterialId = $request->raw_material_id;
        $quantityReceived = $request->quantity_received;

        // Get purchase price from order pivot
        $orderMaterial = $order->rawMaterials()->where('raw_material_id', $rawMaterialId)->first();
        if (!$orderMaterial) {
            return response()->json(['error' => 'Raw material not found in order'], 404);
        }
        $purchasePrice = $orderMaterial->pivot->purchase_price ?? 0;

        DB::transaction(function () use ($order, $rawMaterialId, $quantityReceived, $purchasePrice) {
            // Find or create RawMaterialStockBatch for this material/order
            $batch = \App\Models\RawMaterialStockBatch::where('source_supplier_order_id', $order->id)
                ->where('raw_material_id', $rawMaterialId)
                ->first();

            $oldQuantity = $batch ? $batch->quantity : 0;
            $difference = $quantityReceived - $oldQuantity;

            if ($batch) {
                // Update existing batch
                $batch->update(['quantity' => $quantityReceived]);
            } else {
                // Create new batch
                $batch = \App\Models\RawMaterialStockBatch::create([
                    'raw_material_id' => $rawMaterialId,
                    'quantity' => $quantityReceived,
                    'unit_price' => $purchasePrice,
                    'source_supplier_order_id' => $order->id,
                ]);
            }

            // Update pivot table
            $order->rawMaterials()->updateExistingPivot($rawMaterialId, [
                'quantity_received' => $quantityReceived,
            ]);

            // Create stock movement if there's a difference
            if ($difference != 0) {
                \App\Models\RawMaterialStockMovement::create([
                    'raw_material_stock_batch_id' => $batch->id,
                    'raw_material_id' => $rawMaterialId,
                    'type' => \App\Models\RawMaterialStockMovement::TYPE_PURCHASE,
                    'quantity' => $difference, // Positive for in, negative for out
                    'reference' => 'Order #' . $order->id,
                    'source_type' => 'App\Models\SupplierOrder',
                    'source_id' => $order->id,
                    'user_id' => session('reception_user_id'),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'new_quantity' => $quantityReceived,
        ]);
    }

    /**
     * Finalize factory order reception
     */
    public function finalizeFactoryOrder(SupplierOrder $order)
    {
        $storeIds = $this->getAccessibleStoreIds();

        if (!in_array($order->destination_store_id, $storeIds)) {
            return redirect()->route('reception.factory-orders')
                ->with('error', 'Access denied');
        }

        if ($order->status !== 'waiting_reception') {
            return redirect()->route('reception.factory-orders')
                ->with('error', 'Order cannot be finalized');
        }

        // Set any untouched raw materials (NULL quantity_received) to 0
        foreach ($order->rawMaterials as $material) {
            if (is_null($material->pivot->quantity_received)) {
                $order->rawMaterials()->updateExistingPivot($material->id, [
                    'quantity_received' => 0,
                ]);
            }
        }

        // Change status based on supplier type
        if ($order->supplier->type === 'consignment') {
            $order->status = 'received';
        } else {
            $order->status = 'waiting_invoice';
        }
        $order->save();

        return redirect()->route('reception.factory-orders')
            ->with('success', 'Factory order finalized successfully');
    }

    /**
     * List suppliers for refill
     */
    public function refillSuppliers()
    {
        $suppliers = Supplier::where('is_active', true)
            ->where('is_raw_material_supplier', false)
            ->where('type', 'consignment')
            ->orderBy('name')
            ->get();

        return view('reception.refill.suppliers', compact('suppliers'));
    }

    /**
     * Show products for a supplier (refill)
     */
    public function refillProducts(Supplier $supplier)
    {
        $products = $supplier->products()
            ->with(['brand', 'images'])
            ->orderBy('name->en')
            ->get();

        return view('reception.refill.products', compact('supplier', 'products'));
    }

    /**
     * Store refill
     */
    public function storeRefill(Request $request, Supplier $supplier)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $storeId = session('reception_store_id');
        $userId = session('reception_user_id');

        DB::transaction(function () use ($request, $supplier, $storeId, $userId) {
            // Create refill
            $refill = Refill::create([
                'supplier_id' => $supplier->id,
                'destination_store_id' => $storeId,
            ]);

            $syncData = [];

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) continue;

                $quantity = (int) $item['quantity'];
                if ($quantity <= 0) continue;

                // Get purchase price from supplier-product pivot
                $supplierProduct = $supplier->products()->where('product_id', $item['product_id'])->first();
                $purchasePrice = $supplierProduct ? ($supplierProduct->pivot->purchase_price ?? $product->price) : $product->price;

                // Prepare pivot data
                $syncData[$item['product_id']] = [
                    'quantity_received' => $quantity,
                    'purchase_price' => $purchasePrice,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Create StockBatch
                $batch = StockBatch::create([
                    'product_id' => $item['product_id'],
                    'store_id' => $storeId,
                    'quantity' => $quantity,
                    'unit_price' => $purchasePrice,
                    'source_refill_id' => $refill->id,
                    'label' => 'Refill via Reception PWA',
                ]);

                // Create StockTransaction
                StockTransaction::create([
                    'stock_batch_id' => $batch->id,
                    'store_id' => $storeId,
                    'product_id' => $item['product_id'],
                    'type' => 'in',
                    'quantity' => $quantity,
                    'reason' => 'supplier_refill',
                    'supplier_id' => $supplier->id,
                    'user_id' => $userId,
                ]);
            }

            $refill->products()->sync($syncData);
        });

        return redirect()->route('reception.home')
            ->with('success', 'Refill saved successfully');
    }

    /**
     * List consignment suppliers for returns
     */
    public function returnSuppliers()
    {
        $suppliers = Supplier::where('is_active', true)
            ->where('type', 'consignment')
            ->orderBy('name')
            ->get();

        return view('reception.returns.suppliers', compact('suppliers'));
    }

    /**
     * Show products with stock for a supplier (return)
     */
    public function returnProducts(Supplier $supplier)
    {
        $storeId = session('reception_store_id');

        // Get supplier products with their stock in the user's store
        $products = $supplier->products()
            ->with(['brand', 'images'])
            ->orderBy('name->en')
            ->get()
            ->map(function ($product) use ($storeId) {
                $stock = StockBatch::where('product_id', $product->id)
                    ->where('store_id', $storeId)
                    ->where('quantity', '>', 0)
                    ->sum('quantity');

                $product->current_stock = $stock;
                return $product;
            })
            ->filter(fn($p) => $p->current_stock > 0)
            ->values();

        return view('reception.returns.products', compact('supplier', 'products'));
    }

    /**
     * Store return (creates SupplierReturn and immediately deducts stock)
     */
    public function storeReturn(Request $request, Supplier $supplier)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $storeId = session('reception_store_id');
        $userId = session('reception_user_id');

        DB::transaction(function () use ($request, $supplier, $storeId, $userId) {
            // Create supplier return (pending status - waiting for BO validation)
            $supplierReturn = SupplierReturn::create([
                'supplier_id' => $supplier->id,
                'store_id' => $storeId,
                'created_by_user_id' => $userId,
                'status' => 'pending',
                'notes' => 'Created via Reception PWA',
            ]);

            // Get supplier products with purchase prices
            $supplierProducts = $supplier->products()->get()->keyBy('id');

            foreach ($request->items as $item) {
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

                // Immediately deduct stock using FIFO
                $this->deductStock($productId, $storeId, $quantity, $supplierReturn->id, $userId);
            }
        });

        return redirect()->route('reception.home')
            ->with('success', 'Return created successfully');
    }

    /**
     * Deduct stock using FIFO method
     */
    private function deductStock(int $productId, int $storeId, int $quantity, int $supplierReturnId, int $userId): void
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

            // Create stock transaction for audit trail
            StockTransaction::create([
                'stock_batch_id' => $batch->id,
                'store_id' => $storeId,
                'product_id' => $productId,
                'type' => 'out',
                'quantity' => $deductQuantity,
                'reason' => 'supplier_return',
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * Get store IDs accessible to current user
     * PP staff can also see Warehouse orders
     */
    private function getAccessibleStoreIds(): array
    {
        $userStoreId = session('reception_store_id');
        $ids = [$userStoreId];

        // The first shop (by ID) has access to the warehouse — existing business rule
        $firstShop = Store::where('type', 'shop')->orderBy('id')->first();
        if ($firstShop && $userStoreId === $firstShop->id) {
            $warehouseIds = Store::where('type', 'warehouse')->pluck('id')->toArray();
            $ids = array_merge($ids, $warehouseIds);
        }

        return array_unique($ids);
    }

    /**
     * Check Price page with barcode scanner
     */
    public function checkPrice()
    {
        $stores = Store::orderBy('name')->get();

        return view('reception.check-price', [
            'stores' => $stores,
            'userStoreId' => session('reception_store_id'),
        ]);
    }

    /**
     * Lookup product by barcode (AJAX)
     */
    public function lookupBarcode(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
            'store_id' => 'required|exists:stores,id',
        ]);

        $barcode = trim($request->barcode);

        \Log::info('Barcode lookup', ['barcode' => $barcode, 'store_id' => $request->store_id]);

        // 1. Exact match
        $product = Product::where('ean', $barcode)
            ->with(['brand', 'images'])
            ->first();

        \Log::info('Exact match result', ['found' => $product ? true : false]);

        // 2. If not found, search for EAN containing the barcode (ignoring spaces/prefixes)
        if (!$product) {
            $product = Product::where(function ($query) use ($barcode) {
                $query->where('ean', 'LIKE', '%' . $barcode . '%')
                      ->orWhereRaw("REPLACE(ean, ' ', '') LIKE ?", ['%' . $barcode . '%']);
            })
            ->with(['brand', 'images'])
            ->first();
        }

        // 3. If still not found, try removing spaces from barcode and search
        if (!$product) {
            $cleanedBarcode = preg_replace('/\s+/', '', $barcode);
            if ($cleanedBarcode !== $barcode) {
                $product = Product::where('ean', $cleanedBarcode)
                    ->orWhere('ean', 'LIKE', '%' . $cleanedBarcode . '%')
                    ->with(['brand', 'images'])
                    ->first();
            }
        }

        if (!$product) {
            return response()->json([
                'found' => false,
                'message' => 'Product not found',
            ]);
        }

        $store = Store::find($request->store_id);
        $stock = $product->getTotalStock($store);

        $imageUrl = null;
        if ($product->images->isNotEmpty()) {
            $imageUrl = asset('storage/' . $product->images->first()->path);
        }

        return response()->json([
            'found' => true,
            'product' => [
                'id' => $product->id,
                'ean' => $product->ean,
                'name' => $product->translated_name,
                'brand' => $product->brand?->name,
                'price' => $product->price,
                'stock' => $stock,
                'image' => $imageUrl,
                'color' => $product->color,
                'size' => $product->size,
            ],
            'store_name' => $store->name,
        ]);
    }

    // ==================== STOCK TRANSFERS ====================

    /**
     * List all pending stock transfers
     */
    public function transfersList()
    {
        // All transfers waiting to be received (not restricted by store)
        $pendingTransfers = StockMovement::with(['fromStore', 'toStore', 'user', 'items'])
            ->whereIn('status', [StockMovement::STATUS_VALIDATED, StockMovement::STATUS_IN_TRANSIT])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('reception.transfers.index', compact('pendingTransfers'));
    }

    /**
     * Show form to create a new transfer
     */
    public function transferCreate()
    {
        $stores = Store::orderBy('name')->get();

        return view('reception.transfers.create', compact('stores'));
    }

    /**
     * Get products with stock for a given source store (AJAX)
     */
    public function transferProducts(Request $request)
    {
        $request->validate([
            'from_store_id' => 'required|exists:stores,id',
        ]);

        $fromStoreId = $request->from_store_id;

        // Get all products with stock in the source store
        $products = Product::with(['brand', 'images'])
            ->whereHas('stockBatches', function ($query) use ($fromStoreId) {
                $query->where('store_id', $fromStoreId)
                      ->where('quantity', '>', 0);
            })
            ->get()
            ->map(function ($product) use ($fromStoreId) {
                $stock = StockBatch::where('product_id', $product->id)
                    ->where('store_id', $fromStoreId)
                    ->where('quantity', '>', 0)
                    ->sum('quantity');

                $avgPrice = StockBatch::where('product_id', $product->id)
                    ->where('store_id', $fromStoreId)
                    ->where('quantity', '>', 0)
                    ->avg('unit_price') ?? 0;

                $thumbnail = $product->images->first()
                    ? asset('storage/' . $product->images->first()->path)
                    : asset('images/placeholder.png');

                return [
                    'id' => $product->id,
                    'name' => $product->name[app()->getLocale()] ?? $product->name['en'] ?? reset($product->name),
                    'ean' => $product->ean,
                    'brand' => $product->brand?->name,
                    'stock' => $stock,
                    'unit_price' => round($avgPrice, 5),
                    'thumbnail' => $thumbnail,
                ];
            })
            ->filter(fn($p) => $p['stock'] > 0)
            ->values();

        return response()->json(['products' => $products]);
    }

    /**
     * Store a new transfer
     */
    public function storeTransfer(Request $request)
    {
        $request->validate([
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:500',
        ]);

        $userId = session('reception_user_id');
        $fromStoreId = $request->from_store_id;

        DB::transaction(function () use ($request, $userId, $fromStoreId) {
            $movement = StockMovement::create([
                'type'          => 'transfer',
                'from_store_id' => $fromStoreId,
                'to_store_id'   => $request->to_store_id,
                'note'          => $request->note,
                'user_id'       => $userId,
                'status'        => StockMovement::STATUS_VALIDATED,
            ]);

            $fromStore = Store::find($fromStoreId);

            foreach ($request->items as $item) {
                $productId = $item['product_id'];
                $qty = (int) $item['quantity'];

                if ($qty <= 0) continue;

                $product = Product::find($productId);
                if (!$product) continue;

                // Get average unit price from source store
                $avgUnitPrice = StockBatch::where('store_id', $fromStoreId)
                    ->where('product_id', $productId)
                    ->where('quantity', '>', 0)
                    ->avg('unit_price') ?? 0;

                $movement->items()->create([
                    'product_id' => $productId,
                    'quantity'   => $qty,
                    'unit_price' => $avgUnitPrice,
                ]);

                // Deduct stock from source store using FIFO
                if ($fromStore) {
                    $this->deductStockForTransfer($productId, $fromStoreId, $qty, $userId);
                }
            }
        });

        return redirect()->route('reception.home')
            ->with('success', __('messages.reception.transfer_created'));
    }

    /**
     * Show a single transfer for reception
     */
    public function transferShow(StockMovement $movement)
    {
        if (!in_array($movement->status, [StockMovement::STATUS_VALIDATED, StockMovement::STATUS_IN_TRANSIT])) {
            return redirect()->route('reception.transfers')
                ->with('error', __('messages.reception.transfer_not_available'));
        }

        $movement->load(['fromStore', 'toStore', 'user', 'items.product.images']);

        return view('reception.transfers.show', compact('movement'));
    }

    /**
     * Receive a transfer (mark as received and add stock)
     */
    public function receiveTransfer(StockMovement $movement)
    {
        $userId = session('reception_user_id');

        if (!in_array($movement->status, [StockMovement::STATUS_VALIDATED, StockMovement::STATUS_IN_TRANSIT])) {
            return redirect()->route('reception.transfers')
                ->with('error', __('messages.reception.transfer_not_available'));
        }

        DB::transaction(function () use ($movement, $userId) {
            $toStore = Store::find($movement->to_store_id);
            $fromStore = Store::find($movement->from_store_id);

            if (!$toStore) {
                throw new \Exception("Destination store not found.");
            }

            $totalAmount = 0;

            foreach ($movement->items as $item) {
                $product = Product::find($item->product_id);
                if (!$product) continue;

                // Create StockBatch for destination store
                StockBatch::create([
                    'product_id' => $product->id,
                    'store_id'   => $toStore->id,
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->unit_price,
                ]);

                $totalAmount += $item->quantity * ($item->unit_price ?? 0);
            }

            // Generate invoice and financial transactions
            if ($fromStore && $totalAmount > 0) {
                $this->generateTransferInvoiceAndTransactions($movement, $fromStore, $toStore, $totalAmount, $userId);
            }

            $movement->update(['status' => StockMovement::STATUS_RECEIVED]);
        });

        return redirect()->route('reception.transfers')
            ->with('success', __('messages.reception.transfer_received'));
    }

    /**
     * Deduct stock for transfer using FIFO
     */
    private function deductStockForTransfer(int $productId, int $storeId, int $quantity, int $userId): void
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

            // Create stock transaction for audit trail
            StockTransaction::create([
                'stock_batch_id' => $batch->id,
                'store_id' => $storeId,
                'product_id' => $productId,
                'type' => 'out',
                'quantity' => $deductQuantity,
                'reason' => 'stock_transfer',
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * Generate invoice and financial transactions for a transfer
     */
    private function generateTransferInvoiceAndTransactions(
        StockMovement $movement,
        Store $fromStore,
        Store $toStore,
        float $totalAmount,
        int $userId
    ): void {
        $revenueAccount = FinancialAccount::where('code', '701')->first();
        if (!$revenueAccount) {
            throw new \Exception("Financial account 701 (Shop Sales) not found.");
        }

        $invoiceNumber = StockMovement::generateInvoiceNumber();

        // Determine labels based on transfer type
        $fromIsWarehouse = $fromStore->type === 'warehouse';
        $toIsWarehouse = $toStore->type === 'warehouse';

        if ($fromIsWarehouse && !$toIsWarehouse) {
            $fromLabel = "Approvisionnement #{$movement->id} - Envoi vers {$toStore->name}";
            $toLabel = "Approvisionnement #{$movement->id} - Réception depuis {$fromStore->name}";
            $invoiceType = 'Approvisionnement';
        } elseif (!$fromIsWarehouse && $toIsWarehouse) {
            $fromLabel = "Retour stock #{$movement->id} - Envoi vers {$toStore->name}";
            $toLabel = "Retour stock #{$movement->id} - Réception depuis {$fromStore->name}";
            $invoiceType = 'Retour de stock';
        } else {
            $fromLabel = "Transfert #{$movement->id} - Envoi vers {$toStore->name}";
            $toLabel = "Transfert #{$movement->id} - Réception depuis {$fromStore->name}";
            $invoiceType = 'Transfert inter-magasins';
        }

        // Credit transaction for source store
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
            'user_id'          => $userId,
            'payment_method_id' => 2,
            'external_reference' => $invoiceNumber,
        ]);

        // Debit transaction for destination store
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
            'user_id'          => $userId,
            'payment_method_id' => 2,
            'external_reference' => $invoiceNumber,
        ]);

        // Generate PDF invoice
        $movement->load(['items.product', 'fromStore', 'toStore', 'user']);
        $pdf = Pdf::loadView('stock_movements.invoice', [
            'movement' => $movement,
            'invoiceNumber' => $invoiceNumber,
            'totalAmount' => $totalAmount,
            'invoiceType' => strtoupper($invoiceType),
        ])->setPaper('a4', 'portrait');

        $pdfPath = "stock_movements/invoices/{$invoiceNumber}.pdf";
        Storage::disk('public')->put($pdfPath, $pdf->output());

        // Update movement with invoice info
        $movement->update([
            'total_amount'        => $totalAmount,
            'invoice_number'      => $invoiceNumber,
            'invoice_path'        => $pdfPath,
            'from_transaction_id' => $fromTransaction->id,
            'to_transaction_id'   => $toTransaction->id,
        ]);
    }

    // ==================== QUICK INVENTORY ====================

    /**
     * Show quick inventory page
     */
    public function quickInventoryIndex()
    {
        $stores = Store::orderBy('name')->get();

        return view('reception.quick-inventory', compact('stores'));
    }

    /**
     * Get brands that have stock in a given store (AJAX)
     */
    public function quickInventoryBrands(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
        ]);

        $storeId = $request->store_id;

        // For quick inventory we list every brand that has at least one active product,
        // regardless of whether stock batches exist for the selected store — operators
        // need to be able to count products that have never been received as well.
        $brands = Brand::whereHas('products')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['brands' => $brands]);
    }

    /**
     * Get products with theoretical stock for counting (AJAX)
     * Accepts either brand_id or product_ids[]
     */
    public function quickInventoryProducts(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'brand_id' => 'nullable|exists:brands,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $storeId = $request->store_id;

        $query = Product::with(['brand']);

        if ($request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        } elseif ($request->product_ids) {
            $query->whereIn('id', $request->product_ids);
        } else {
            return response()->json(['products' => []]);
        }

        $products = $query->get()->map(function ($product) use ($storeId) {
            $theoretical = StockBatch::where('product_id', $product->id)
                ->where('store_id', $storeId)
                ->where('quantity', '>', 0)
                ->sum('quantity');

            // Get last count info
            $lastCount = DB::table('product_stock_counts')
                ->where('product_id', $product->id)
                ->where('store_id', $storeId)
                ->first();

            $countedByName = null;
            if ($lastCount && $lastCount->counted_by) {
                $countedByName = User::find($lastCount->counted_by)?->name;
            }

            return [
                'id' => $product->id,
                'name' => $product->name[app()->getLocale()] ?? $product->name['en'] ?? reset($product->name),
                'ean' => $product->ean,
                'brand' => $product->brand?->name,
                'theoretical' => (int) $theoretical,
                'last_counted_at' => $lastCount?->last_counted_at,
                'counted_by_name' => $countedByName,
            ];
        });

        return response()->json(['products' => $products->values()]);
    }

    /**
     * Search products by name/EAN for quick inventory (AJAX)
     */
    public function quickInventorySearchProducts(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'query' => 'required|string|min:2',
        ]);

        $storeId = $request->store_id;
        $search = $request->query('query', $request->input('query'));

        $products = Product::search($search)
            ->take(20)
            ->get()
            ->load('brand')
            ->map(function ($product) use ($storeId) {
                $theoretical = StockBatch::where('product_id', $product->id)
                    ->where('store_id', $storeId)
                    ->where('quantity', '>', 0)
                    ->sum('quantity');

                return [
                    'id' => $product->id,
                    'name' => $product->name[app()->getLocale()] ?? $product->name['en'] ?? reset($product->name),
                    'ean' => $product->ean,
                    'brand' => $product->brand?->name,
                    'theoretical' => (int) $theoretical,
                ];
            });

        return response()->json(['products' => $products->values()]);
    }

    /**
     * Apply inventory adjustments (AJAX)
     */
    public function quickInventoryApply(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'adjustments' => 'present|array',
            'adjustments.*.product_id' => 'required|exists:products,id',
            'adjustments.*.difference' => 'required|integer',
            'counted_product_ids' => 'required|array',
            'counted_product_ids.*' => 'exists:products,id',
        ]);

        $storeId = $request->store_id;
        $userId = session('reception_user_id');

        DB::transaction(function () use ($request, $storeId, $userId) {
            // Apply stock adjustments for products with differences
            foreach ($request->adjustments as $adj) {
                $productId = $adj['product_id'];
                $difference = (int) $adj['difference'];

                if ($difference === 0) continue;

                if ($difference > 0) {
                    // Stock increase: create a new batch
                    $batch = StockBatch::create([
                        'product_id' => $productId,
                        'store_id' => $storeId,
                        'quantity' => $difference,
                        'unit_price' => 0,
                        'label' => 'Inventory adjustment (Quick Inventory)',
                    ]);

                    StockTransaction::create([
                        'stock_batch_id' => $batch->id,
                        'store_id' => $storeId,
                        'product_id' => $productId,
                        'type' => 'in',
                        'quantity' => $difference,
                        'reason' => 'inventory_adjustment',
                        'user_id' => $userId,
                    ]);
                } else {
                    // Stock decrease: deduct using FIFO
                    $remaining = abs($difference);
                    $batches = StockBatch::where('product_id', $productId)
                        ->where('store_id', $storeId)
                        ->where('quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->get();

                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;

                        $deduct = min($batch->quantity, $remaining);
                        $batch->decrement('quantity', $deduct);
                        $remaining -= $deduct;

                        StockTransaction::create([
                            'stock_batch_id' => $batch->id,
                            'store_id' => $storeId,
                            'product_id' => $productId,
                            'type' => 'out',
                            'quantity' => $deduct,
                            'reason' => 'inventory_adjustment',
                            'user_id' => $userId,
                        ]);
                    }
                }
            }

            // Update last counted timestamp for all counted products
            foreach ($request->counted_product_ids as $productId) {
                DB::table('product_stock_counts')->updateOrInsert(
                    ['product_id' => $productId, 'store_id' => $storeId],
                    [
                        'last_counted_at' => now(),
                        'counted_by' => $userId,
                        'updated_at' => now(),
                    ]
                );
            }
        });

        return response()->json(['success' => true]);
    }

    // ─── Raw Materials Quick Inventory ───

    public function quickInventorySearchRawMaterials(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $query = $request->query('query') ?: $request->input('query');

        $rawMaterials = RawMaterial::where('is_active', true)
            ->where('track_stock', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%')
                  ->orWhere('sku', 'LIKE', '%' . $query . '%');
            })
            ->orderBy('name')
            ->take(20)
            ->get()
            ->map(function ($rm) {
                return [
                    'id' => $rm->id,
                    'name' => $rm->name,
                    'sku' => $rm->sku,
                    'unit' => $rm->unit,
                    'theoretical' => round((float) $rm->total_stock, 2),
                ];
            });

        return response()->json(['raw_materials' => $rawMaterials]);
    }

    public function quickInventoryRawMaterials(Request $request)
    {
        $request->validate([
            'raw_material_ids' => 'nullable|array',
            'raw_material_ids.*' => 'integer|exists:raw_materials,id',
            'load_all' => 'nullable|boolean',
        ]);

        $query = RawMaterial::where('is_active', true)
            ->where('track_stock', true);

        if ($request->boolean('load_all')) {
            // Load all tracked raw materials
        } elseif ($request->filled('raw_material_ids')) {
            $query->whereIn('id', $request->raw_material_ids);
        } else {
            return response()->json(['raw_materials' => []]);
        }

        $rawMaterials = $query->orderBy('name')->get()->map(function ($rm) {
            return [
                'id' => $rm->id,
                'name' => $rm->name,
                'sku' => $rm->sku,
                'unit' => $rm->unit,
                'theoretical' => round((float) $rm->total_stock, 2),
            ];
        });

        return response()->json(['raw_materials' => $rawMaterials]);
    }

    public function quickInventoryApplyRawMaterials(Request $request)
    {
        $request->validate([
            'adjustments' => 'required|array',
            'adjustments.*.raw_material_id' => 'required|integer|exists:raw_materials,id',
            'adjustments.*.difference' => 'required|numeric',
        ]);

        $userId = session('reception_user_id');

        DB::transaction(function () use ($request, $userId) {
            $batchNumber = 'INV-' . now()->format('Ymd-His');

            foreach ($request->adjustments as $adj) {
                $difference = round((float) $adj['difference'], 2);
                if ($difference == 0) continue;

                $rmId = $adj['raw_material_id'];

                if ($difference > 0) {
                    // Stock increase: create a new batch
                    $batch = RawMaterialStockBatch::create([
                        'raw_material_id' => $rmId,
                        'quantity' => $difference,
                        'unit_price' => 0,
                        'received_at' => now(),
                        'batch_number' => $batchNumber,
                        'notes' => 'Inventory adjustment (Quick Inventory)',
                    ]);

                    RawMaterialStockMovement::create([
                        'raw_material_id' => $rmId,
                        'raw_material_stock_batch_id' => $batch->id,
                        'quantity' => $difference,
                        'type' => RawMaterialStockMovement::TYPE_ADJUSTMENT,
                        'reference' => $batchNumber,
                        'notes' => 'Quick Inventory adjustment (+)',
                        'user_id' => $userId,
                    ]);
                } else {
                    // Stock decrease: FIFO deduction
                    $remaining = abs($difference);
                    $batches = RawMaterialStockBatch::where('raw_material_id', $rmId)
                        ->where('quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->get();

                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;

                        $deduct = min($remaining, $batch->quantity);
                        $batch->decrement('quantity', $deduct);

                        RawMaterialStockMovement::create([
                            'raw_material_id' => $rmId,
                            'raw_material_stock_batch_id' => $batch->id,
                            'quantity' => -$deduct,
                            'type' => RawMaterialStockMovement::TYPE_ADJUSTMENT,
                            'reference' => $batchNumber,
                            'notes' => 'Quick Inventory adjustment (-)',
                            'user_id' => $userId,
                        ]);

                        $remaining = round($remaining - $deduct, 2);
                    }

                    // If there's still remaining (theoretical was wrong), create negative adjustment batch
                    if ($remaining > 0) {
                        $batch = RawMaterialStockBatch::create([
                            'raw_material_id' => $rmId,
                            'quantity' => 0,
                            'unit_price' => 0,
                            'received_at' => now(),
                            'batch_number' => $batchNumber,
                            'notes' => 'Inventory adjustment overflow (Quick Inventory)',
                        ]);

                        RawMaterialStockMovement::create([
                            'raw_material_id' => $rmId,
                            'raw_material_stock_batch_id' => $batch->id,
                            'quantity' => -$remaining,
                            'type' => RawMaterialStockMovement::TYPE_ADJUSTMENT,
                            'reference' => $batchNumber,
                            'notes' => 'Quick Inventory adjustment (overflow)',
                            'user_id' => $userId,
                        ]);
                    }
                }
            }
        });

        return response()->json(['success' => true]);
    }
}
