<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\WebsiteOrder;
use App\Models\WebsitePaymentTransaction;
use App\Services\SpecialOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class SpecialOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = WebsiteOrder::where('source', 'backoffice')
            ->with('store', 'createdByUser')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('payment_status')) {
            $query->byPaymentStatus($request->payment_status);
        }

        $orders = $query->paginate(20)->withQueryString();

        $counts = [
            'total' => WebsiteOrder::where('source', 'backoffice')->count(),
            'pending' => WebsiteOrder::where('source', 'backoffice')->where('status', 'pending')->count(),
            'processing' => WebsiteOrder::where('source', 'backoffice')->where('status', 'processing')->count(),
        ];

        return view('special-orders.index', compact('orders', 'counts'));
    }

    public function create()
    {
        $stores = Store::all();
        $warehouseId = Store::warehouseId();
        return view('special-orders.create', compact('stores', 'warehouseId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'store_id' => 'required|exists:stores,id',
            'payment_type' => 'required|in:payment_link,cash,bank_transfer',
            'deposit_amount' => 'nullable|numeric|min:0',
            'deposit_paid' => 'nullable|boolean',
            'has_shipping' => 'nullable|boolean',
            'shipping_address_line1' => 'nullable|string|max:255',
            'shipping_address_line2' => 'nullable|string|max:255',
            'shipping_city' => 'nullable|string|max:255',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_state' => 'nullable|string|max:255',
            'shipping_country' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.custom_price' => 'required|numeric|min:0',
            'options' => 'nullable|array',
            'options.*.label' => 'required|string|max:255',
            'options.*.amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        // Email required for payment link
        if ($request->payment_type === 'payment_link' && empty($request->email)) {
            return back()->withErrors(['email' => __('messages.special_order.email_required_for_link')])->withInput();
        }

        $order = DB::transaction(function () use ($request) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $lineTotal = round($item['custom_price'] * $item['quantity'], 5);
                $subtotal += $lineTotal;

                $productName = $product->name;
                if (is_array($productName)) {
                    $productName = $productName['en'] ?? $productName['fr'] ?? array_values($productName)[0] ?? '';
                }

                $productImage = $product->images()->first();

                $itemsData[] = [
                    'product_id' => $product->id,
                    'item_type' => 'product',
                    'product_name' => $productName,
                    'product_sku' => $product->ean,
                    'product_image' => $productImage?->path,
                    'unit_price' => $item['custom_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $lineTotal,
                ];
            }

            // Paid options
            $optionsData = [];
            if ($request->options) {
                foreach ($request->options as $option) {
                    if (empty($option['label']) || !isset($option['amount'])) continue;
                    $optionAmount = round((float) $option['amount'], 5);
                    $subtotal += $optionAmount;

                    $optionsData[] = [
                        'product_id' => null,
                        'item_type' => 'option',
                        'product_name' => $option['label'],
                        'product_sku' => null,
                        'product_image' => null,
                        'unit_price' => $optionAmount,
                        'quantity' => 1,
                        'subtotal' => $optionAmount,
                    ];
                }
            }

            // Discount
            $discountAmount = round((float) ($request->discount_amount ?? 0), 5);

            $depositAmount = $request->deposit_amount ?? 0;
            $depositPaid = $request->boolean('deposit_paid');

            $total = max(0, $subtotal - $discountAmount);

            // Determine payment method label
            $paymentMethod = match ($request->payment_type) {
                'cash' => 'cash',
                'bank_transfer' => 'bank_transfer',
                default => 'cards',
            };

            $order = WebsiteOrder::create([
                'order_number' => WebsiteOrder::generateOrderNumber(),
                'guest_email' => $request->email,
                'guest_phone' => $request->phone,
                'locale' => 'en',
                'store_id' => $request->store_id,
                'source' => 'backoffice',
                'created_by_user_id' => auth()->id(),
                'shipping_first_name' => $request->first_name,
                'shipping_last_name' => $request->last_name,
                'shipping_address_line1' => $request->shipping_address_line1 ?? '',
                'shipping_address_line2' => $request->shipping_address_line2 ?? '',
                'shipping_city' => $request->shipping_city ?? '',
                'shipping_postal_code' => $request->shipping_postal_code ?? '',
                'shipping_state' => $request->shipping_state ?? '',
                'shipping_country' => $request->shipping_country ?? '',
                'shipping_phone' => $request->phone ?? '',
                'subtotal' => $subtotal,
                'shipping_cost' => 0,
                'tax' => 0,
                'discount' => $discountAmount,
                'total' => $total,
                'deposit_amount' => $depositAmount,
                'deposit_paid' => $depositPaid,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $paymentMethod,
                'payment_type' => $request->payment_type,
                'admin_notes' => $request->admin_notes,
            ]);

            foreach ($itemsData as $itemData) {
                $order->items()->create($itemData);
            }

            foreach ($optionsData as $optionData) {
                $order->items()->create($optionData);
            }

            // Generate payment link only if payment_type is payment_link
            if ($request->payment_type === 'payment_link') {
                $token = Str::random(48);
                $locale = $order->locale ?? 'en';
                $paymentUrl = config('app.website_url') . '/' . $locale . '/special-order/' . $order->id . '/' . $token;
                $order->update([
                    'payment_token' => $token,
                    'payment_link_url' => $paymentUrl,
                ]);
            }

            return $order;
        });

        if ($request->payment_type === 'payment_link') {
            return redirect()->route('special-orders.show', $order)
                ->with('success', __('messages.special_order.created_with_link'));
        }

        return redirect()->route('special-orders.show', $order)
            ->with('success', __('messages.special_order.created'));
    }

    public function show(WebsiteOrder $order)
    {
        if ($order->source !== 'backoffice') {
            abort(404);
        }

        $order->load(['items', 'transactions', 'store', 'createdByUser']);

        return view('special-orders.show', compact('order'));
    }

    public function edit(WebsiteOrder $order)
    {
        if ($order->source !== 'backoffice') {
            abort(404);
        }

        $order->load(['items', 'store']);
        $stores = Store::all();
        $warehouseId = Store::warehouseId();

        return view('special-orders.edit', compact('order', 'stores', 'warehouseId'));
    }

    public function update(Request $request, WebsiteOrder $order)
    {
        if ($order->source !== 'backoffice') {
            abort(404);
        }

        // If items are submitted, this is the full edit form
        if ($request->has('items')) {
            $rules = [
                'status' => 'required|in:' . implode(',', WebsiteOrder::statuses()),
                'deposit_amount' => 'nullable|numeric|min:0',
                'deposit_paid' => 'nullable|boolean',
                'admin_notes' => 'nullable|string|max:2000',
                'tracking_url' => 'nullable|url|max:2000',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.custom_price' => 'required|numeric|min:0',
                'options' => 'nullable|array',
                'options.*.label' => 'required|string|max:255',
                'options.*.amount' => 'required|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
            ];

            // Allow changing payment type as long as order is not paid
            if ($order->payment_status !== 'paid') {
                $rules['payment_type'] = 'required|in:payment_link,cash,bank_transfer';
            }

            // Allow editing client info and store when pending
            if ($order->status === 'pending') {
                $rules = array_merge($rules, [
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'email' => 'nullable|email|max:255',
                    'phone' => 'nullable|string|max:50',
                    'store_id' => 'required|exists:stores,id',
                    'shipping_address_line1' => 'nullable|string|max:255',
                    'shipping_address_line2' => 'nullable|string|max:255',
                    'shipping_city' => 'nullable|string|max:255',
                    'shipping_postal_code' => 'nullable|string|max:20',
                    'shipping_state' => 'nullable|string|max:255',
                    'shipping_country' => 'nullable|string|max:255',
                ]);
            }

            $request->validate($rules);

            $wasDepositPaid = (bool) $order->deposit_paid;

            DB::transaction(function () use ($request, $order) {
                // Rebuild items
                $order->items()->delete();

                $subtotal = 0;
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    $lineTotal = round($item['custom_price'] * $item['quantity'], 5);
                    $subtotal += $lineTotal;

                    $productName = $product->name;
                    if (is_array($productName)) {
                        $productName = $productName['en'] ?? $productName['fr'] ?? array_values($productName)[0] ?? '';
                    }

                    $productImage = $product->images()->first();

                    $order->items()->create([
                        'product_id' => $product->id,
                        'item_type' => 'product',
                        'product_name' => $productName,
                        'product_sku' => $product->ean,
                        'product_image' => $productImage?->path,
                        'unit_price' => $item['custom_price'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $lineTotal,
                    ]);
                }

                // Paid options
                if ($request->options) {
                    foreach ($request->options as $option) {
                        if (empty($option['label']) || !isset($option['amount'])) continue;
                        $optionAmount = round((float) $option['amount'], 5);
                        $subtotal += $optionAmount;

                        $order->items()->create([
                            'product_id' => null,
                            'item_type' => 'option',
                            'product_name' => $option['label'],
                            'product_sku' => null,
                            'product_image' => null,
                            'unit_price' => $optionAmount,
                            'quantity' => 1,
                            'subtotal' => $optionAmount,
                        ]);
                    }
                }

                $discountAmount = round((float) ($request->discount_amount ?? 0), 5);
                $total = max(0, $subtotal - $discountAmount);

                $updateData = [
                    'status' => $request->status,
                    'subtotal' => $subtotal,
                    'discount' => $discountAmount,
                    'total' => $total,
                    'deposit_amount' => $request->deposit_amount ?? $order->deposit_amount,
                    'deposit_paid' => $request->boolean('deposit_paid'),
                    'admin_notes' => $request->admin_notes,
                    'tracking_url' => $request->tracking_url,
                ];

                // Update payment type as long as order is not paid
                if ($order->payment_status !== 'paid' && $request->has('payment_type')) {
                    $paymentMethod = match ($request->payment_type) {
                        'cash' => 'cash',
                        'bank_transfer' => 'bank_transfer',
                        default => 'cards',
                    };

                    $updateData['payment_type'] = $request->payment_type;
                    $updateData['payment_method'] = $paymentMethod;
                }

                // Update client info and store when order was pending
                if ($order->status === 'pending') {
                    $updateData = array_merge($updateData, [
                        'shipping_first_name' => $request->first_name,
                        'shipping_last_name' => $request->last_name,
                        'guest_email' => $request->email,
                        'guest_phone' => $request->phone,
                        'shipping_phone' => $request->phone ?? '',
                        'store_id' => $request->store_id,
                        'shipping_address_line1' => $request->shipping_address_line1 ?? '',
                        'shipping_address_line2' => $request->shipping_address_line2 ?? '',
                        'shipping_city' => $request->shipping_city ?? '',
                        'shipping_postal_code' => $request->shipping_postal_code ?? '',
                        'shipping_state' => $request->shipping_state ?? '',
                        'shipping_country' => $request->shipping_country ?? '',
                    ]);
                }

                $order->update($updateData);
            });

            $service = new SpecialOrderService();

            // Create deposit financial transaction when deposit transitions to paid
            $order->refresh();
            if (!$wasDepositPaid && $order->deposit_paid && $order->payment_status !== 'paid') {
                $service->createDepositFinancialTransaction($order, $order->payment_method ?? 'cash');
            }

            // Deduct stock when status changes to shipped or delivered
            if (in_array($request->status, ['shipped', 'delivered'])) {
                $service->deductStockIfNeeded($order);
            }

            return redirect()->route('special-orders.show', $order)
                ->with('success', __('messages.special_order.updated'));
        }

        // Simple update (from show page forms)
        $request->validate([
            'status' => 'required|in:' . implode(',', WebsiteOrder::statuses()),
            'deposit_amount' => 'nullable|numeric|min:0',
            'deposit_paid' => 'nullable|boolean',
            'admin_notes' => 'nullable|string|max:2000',
            'tracking_url' => 'nullable|url|max:2000',
        ]);

        $wasDepositPaid = (bool) $order->deposit_paid;

        $order->update([
            'status' => $request->status,
            'deposit_amount' => $request->deposit_amount ?? $order->deposit_amount,
            'deposit_paid' => $request->boolean('deposit_paid'),
            'admin_notes' => $request->admin_notes,
            'tracking_url' => $request->tracking_url,
        ]);

        $service = new SpecialOrderService();

        // Create deposit financial transaction when deposit transitions to paid
        if (!$wasDepositPaid && $order->deposit_paid && $order->payment_status !== 'paid') {
            $service->createDepositFinancialTransaction($order, $order->payment_method ?? 'cash');
        }

        // Deduct stock when status changes to shipped or delivered
        if (in_array($request->status, ['shipped', 'delivered'])) {
            $service->deductStockIfNeeded($order);
        }

        return back()->with('success', __('messages.special_order.updated'));
    }

    public function invoice(WebsiteOrder $order)
    {
        if ($order->source !== 'backoffice') {
            abort(404);
        }

        $order->load(['items', 'store']);

        $invoiceNumber = 'KB-' . str_pad($order->id, 5, '0', STR_PAD_LEFT) . '/' . substr($order->created_at->year, -2);

        $pdf = Pdf::loadView('special-orders.invoice-pdf', [
            'order' => $order,
            'invoiceNumber' => $invoiceNumber,
            'productItems' => $order->items->where('item_type', 'product'),
            'optionItems' => $order->items->where('item_type', 'option'),
        ]);

        $pdf->setPaper('A4');

        return $pdf->stream("invoice-{$order->order_number}.pdf");
    }

    public function searchProducts(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'store_id' => 'nullable|integer',
        ]);

        $storeId = $request->store_id ?? Store::warehouseId();

        $products = Product::search($request->q)
            ->take(20)
            ->get()
            ->map(function ($product) use ($storeId) {
                $stock = DB::table('stock_batches')
                    ->where('store_id', $storeId)
                    ->where('product_id', $product->id)
                    ->sum('quantity');

                $name = $product->name;
                if (is_array($name)) {
                    $name = $name['en'] ?? $name['fr'] ?? array_values($name)[0] ?? '';
                }

                return [
                    'id' => $product->id,
                    'name' => $name,
                    'ean' => $product->ean,
                    'price' => (float) $product->price,
                    'stock' => (int) $stock,
                ];
            });

        return response()->json($products);
    }

    public function regenerateLink(WebsiteOrder $order)
    {
        if ($order->source !== 'backoffice') {
            return back()->with('error', __('messages.special_order.not_special_order'));
        }

        $token = Str::random(48);
        $locale = $order->locale ?? 'en';
        $paymentUrl = config('app.website_url') . '/' . $locale . '/special-order/' . $order->id . '/' . $token;
        $order->update([
            'payment_token' => $token,
            'payment_link_url' => $paymentUrl,
        ]);

        return back()->with('success', __('messages.special_order.link_regenerated'));
    }

    public function sendLinkEmail(WebsiteOrder $order)
    {
        if (!$order->payment_link_url) {
            return back()->with('error', __('messages.special_order.no_link_to_send'));
        }

        $email = $order->contact_email;
        if (!$email) {
            return back()->with('error', __('messages.special_order.no_email'));
        }

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(
                new \App\Mail\SpecialOrderPaymentLinkMail($order)
            );

            return back()->with('success', __('messages.special_order.email_sent', ['email' => $email]));
        } catch (\Exception $e) {
            Log::error('Failed to send special order payment link email', [
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('messages.special_order.email_failed'));
        }
    }

    public function markAsPaid(Request $request, WebsiteOrder $order)
    {
        if ($order->source !== 'backoffice' || $order->payment_status === 'paid') {
            return back()->with('error', __('messages.special_order.cannot_mark_paid'));
        }

        $request->validate([
            'paid_at' => 'required|date',
            'payment_type' => 'nullable|in:cash,bank_transfer',
        ]);

        $paymentType = $request->input('payment_type', $order->payment_type ?? 'cash');

        $service = new SpecialOrderService();
        $service->handleDirectPayment($order, $paymentType);

        $order->update([
            'paid_at' => $request->paid_at,
            'payment_type' => $paymentType,
        ]);

        return back()->with('success', __('messages.special_order.marked_as_paid'));
    }

    /**
     * Public payment page — no auth required.
     */
    public function showPaymentPage(int $orderId, string $token)
    {
        $order = WebsiteOrder::where('id', $orderId)
            ->where('payment_token', $token)
            ->where('source', 'backoffice')
            ->firstOrFail();

        if ($order->payment_status === 'paid') {
            return redirect()->route('special-orders.pay.success', [
                'order' => $order->id,
                'token' => $token,
            ]);
        }

        if ($order->payment_link_expired) {
            abort(410, 'This payment link has expired.');
        }

        // Generate PayWay form data
        $merchantId = config('payway.merchant_id');
        $apiKey = config('payway.api_key');
        $purchaseUrl = config('payway.api_url');

        $tranId = 'SO-' . $order->id . '-' . time();
        $merchantRefNo = 'SO-' . $order->order_number;
        $amount = number_format($order->total, 2, '.', '');

        // Build items string for PayWay
        $itemNames = $order->items->map(fn ($item) => $item->product_name . ' x' . $item->quantity)->implode(', ');

        $reqTime = now()->format('YmdHis');
        $hash = base64_encode(hash_hmac('sha512', $merchantId . $tranId . $amount . $itemNames . $reqTime, $apiKey, true));

        // Create payment_transaction record
        WebsitePaymentTransaction::create([
            'order_id' => $order->id,
            'tran_id' => null,
            'merchant_ref_no' => $merchantRefNo,
            'amount' => $order->total,
            'currency' => 'USD',
            'status' => '11',
            'status_label' => 'Awaiting PopUp',
            'internal_status' => 'initiated',
        ]);

        $formData = [
            'hash' => $hash,
            'tran_id' => $tranId,
            'amount' => $amount,
            'firstname' => $order->shipping_first_name,
            'lastname' => $order->shipping_last_name,
            'email' => $order->guest_email ?? '',
            'phone' => $order->guest_phone ?? '',
            'items' => $itemNames,
            'return_params' => $merchantRefNo,
            'type' => 'purchase',
            'payment_option' => 'cards abapay',
            'merchant_id' => $merchantId,
            'req_time' => $reqTime,
            'continue_success_url' => route('special-orders.pay.success', ['order' => $order->id, 'token' => $token]),
        ];

        return view('special-orders.pay', compact('order', 'token', 'formData', 'purchaseUrl'));
    }

    /**
     * AJAX endpoint to check if payment went through — no auth required.
     */
    public function checkPaymentStatus(Request $request)
    {
        $request->validate([
            'tran_id' => 'required|string',
        ]);

        $tranId = $request->input('tran_id');

        // Extract order ID from tran_id format: SO-{id}-{timestamp}
        $parts = explode('-', $tranId);
        $orderId = $parts[1] ?? null;

        if (!$orderId) {
            return response()->json(['status' => 'unknown']);
        }

        $order = WebsiteOrder::find($orderId);

        if (!$order) {
            return response()->json(['status' => 'unknown']);
        }

        return response()->json([
            'status' => $order->payment_status,
        ]);
    }

    /**
     * Payment success page — no auth required.
     */
    public function showPaymentSuccess(int $orderId, string $token)
    {
        $order = WebsiteOrder::where('id', $orderId)
            ->where('payment_token', $token)
            ->where('source', 'backoffice')
            ->firstOrFail();

        return view('special-orders.pay-success', compact('order'));
    }
}
