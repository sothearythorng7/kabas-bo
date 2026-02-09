<?php

namespace App\Http\Controllers;

use App\Models\WebsiteOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebsiteOrderController extends Controller
{
    const FINANCIAL_ACCOUNT_ID = 17;  // code 701 = Shop Sales
    const SYSTEM_USER_ID = 1;

    public function index(Request $request)
    {
        $query = WebsiteOrder::orderBy('created_at', 'desc');

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
            'total' => WebsiteOrder::count(),
            'pending' => WebsiteOrder::where('status', 'pending')->count(),
            'processing' => WebsiteOrder::where('status', 'processing')->count(),
        ];

        return view('website-orders.index', compact('orders', 'counts'));
    }

    public function show(WebsiteOrder $order)
    {
        $order->load(['items', 'transactions']);

        return view('website-orders.show', compact('order'));
    }

    public function updateStatus(Request $request, WebsiteOrder $order)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', WebsiteOrder::statuses()),
        ]);

        $newStatus = $request->status;
        $oldStatus = $order->status;

        // If cancelling a paid order → refund PayWay + reverse stock + reverse financial
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled' && $order->payment_status === 'paid') {
            $order->load('items');

            // 1. Attempt PayWay refund
            $refundResult = $this->refundPayWay($order);

            // 2. Reverse stock + financial (even if PayWay refund fails — manual refund may be needed)
            DB::transaction(function () use ($order) {
                $this->reverseStock($order);
                $this->reverseFinancialTransaction($order);
            });

            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
            ]);

            // Update the payment transaction record
            $latestTxn = DB::table('payment_transactions')
                ->where('order_id', $order->id)
                ->orderBy('id', 'desc')
                ->first();

            if ($latestTxn) {
                DB::table('payment_transactions')
                    ->where('id', $latestTxn->id)
                    ->update([
                        'internal_status' => 'refunded',
                        'refunded_at' => now(),
                        'refund_amount' => $order->total,
                        'updated_at' => now(),
                    ]);
            }

            Log::info('Website order cancelled with full reversal', [
                'order_number' => $order->order_number,
                'payway_refund' => $refundResult['success'] ? 'success' : ($refundResult['error'] ?? 'failed'),
            ]);

            if ($refundResult['success']) {
                return back()->with('success', __('messages.website_order.cancelled_with_reversal'));
            }

            // PayWay refund failed but internal reversal done
            return back()->with('warning', __('messages.website_order.cancelled_payway_failed', [
                'error' => $refundResult['error'] ?? 'Unknown error',
            ]));
        }

        $order->update(['status' => $newStatus]);

        return back()->with('success', __('messages.website_order.status_updated'));
    }

    public function updateNotes(Request $request, WebsiteOrder $order)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $order->update(['admin_notes' => $request->admin_notes]);

        return back()->with('success', __('messages.website_order.notes_updated'));
    }

    /**
     * Attempt PayWay refund via API.
     */
    protected function refundPayWay(WebsiteOrder $order): array
    {
        $merchantId = config('payway.merchant_id');
        $apiKey = config('payway.api_key');
        $rsaPublicKey = config('payway.rsa_public_key');
        $refundUrl = config('payway.refund_url');

        if (empty($merchantId) || empty($apiKey)) {
            return ['success' => false, 'error' => 'PayWay credentials not configured in BO.'];
        }

        if (empty($rsaPublicKey)) {
            Log::warning('PayWay refund skipped: RSA public key not configured', [
                'order_number' => $order->order_number,
            ]);
            return ['success' => false, 'error' => 'PayWay RSA public key not configured. Contact ABA Bank.'];
        }

        // Get the original tran_id
        $tranId = $order->payway_tran_id;
        if (empty($tranId)) {
            return ['success' => false, 'error' => 'No PayWay transaction ID on this order.'];
        }

        // Build merchant_auth JSON
        $merchantAuth = json_encode([
            'mc_id' => $merchantId,
            'tran_id' => $tranId,
            'refund_amount' => number_format($order->total, 2, '.', ''),
        ]);

        // RSA encrypt in 117-byte chunks
        $encrypted = $this->rsaEncrypt($merchantAuth, $rsaPublicKey);
        if ($encrypted === null) {
            return ['success' => false, 'error' => 'RSA encryption failed.'];
        }

        // Request time in YYYYMMDDHHmmss UTC
        $requestTime = gmdate('YmdHis');

        // Hash: request_time + merchant_id + merchant_auth
        $hashString = $requestTime . $merchantId . $encrypted;
        $hash = base64_encode(hash_hmac('sha512', $hashString, $apiKey, true));

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($refundUrl, [
                'request_time' => $requestTime,
                'merchant_id' => $merchantId,
                'merchant_auth' => $encrypted,
                'hash' => $hash,
            ]);

            $data = $response->json();

            Log::info('PayWay refund response', [
                'order_number' => $order->order_number,
                'tran_id' => $tranId,
                'http_status' => $response->status(),
                'response' => $data,
            ]);

            if ($response->successful() && isset($data['status']['code']) && $data['status']['code'] === '00') {
                return ['success' => true, 'data' => $data];
            }

            return [
                'success' => false,
                'error' => $data['status']['message'] ?? 'PayWay refund rejected.',
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('PayWay refund exception', [
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Could not connect to PayWay for refund.'];
        }
    }

    /**
     * RSA encrypt data in 117-byte chunks.
     */
    protected function rsaEncrypt(string $data, string $publicKeyPem): ?string
    {
        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if (!$publicKey) {
            Log::error('PayWay RSA: invalid public key');
            return null;
        }

        $encrypted = '';
        $chunks = str_split($data, 117);

        foreach ($chunks as $chunk) {
            $encryptedChunk = '';
            if (!openssl_public_encrypt($chunk, $encryptedChunk, $publicKey)) {
                Log::error('PayWay RSA: encryption failed for chunk');
                return null;
            }
            $encrypted .= $encryptedChunk;
        }

        return base64_encode($encrypted);
    }

    /**
     * Reverse stock: re-add quantities to warehouse batches.
     */
    protected function reverseStock(WebsiteOrder $order): void
    {
        $storeId = $order->store_id ?? 3;

        foreach ($order->items as $item) {
            if ($item->item_type !== 'product' || !$item->product_id) {
                continue;
            }

            $batch = DB::table('stock_batches')
                ->where('store_id', $storeId)
                ->where('product_id', $item->product_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($batch) {
                DB::table('stock_batches')
                    ->where('id', $batch->id)
                    ->increment('quantity', $item->quantity);

                $batchId = $batch->id;
            } else {
                $batchId = DB::table('stock_batches')->insertGetId([
                    'store_id' => $storeId,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('stock_transactions')->insert([
                'stock_batch_id' => $batchId,
                'store_id' => $storeId,
                'product_id' => $item->product_id,
                'type' => 'in',
                'quantity' => $item->quantity,
                'reason' => 'website_cancellation',
                'sale_id' => null,
                'shift_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Create a reverse (debit) financial transaction.
     */
    protected function reverseFinancialTransaction(WebsiteOrder $order): void
    {
        $storeId = $order->store_id ?? 3;

        $lastTxn = DB::table('financial_transactions')
            ->where('store_id', $storeId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first(['balance_after']);

        $balanceBefore = $lastTxn ? (float) $lastTxn->balance_after : 0;
        $balanceAfter = $balanceBefore - (float) $order->total;

        $paymentMethodId = $this->resolvePaymentMethodId($order);

        DB::table('financial_transactions')->insert([
            'store_id' => $storeId,
            'account_id' => self::FINANCIAL_ACCOUNT_ID,
            'amount' => $order->total,
            'currency' => 'USD',
            'direction' => 'debit',
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'label' => "Annulation Website Order #{$order->order_number}",
            'description' => "Cancellation of website order {$order->order_number} - {$order->shipping_full_name}",
            'status' => 'validated',
            'transaction_date' => now(),
            'payment_method_id' => $paymentMethodId,
            'user_id' => auth()->id() ?? self::SYSTEM_USER_ID,
            'external_reference' => "WEB-CANCEL-{$order->id}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Resolve payment method ID from order's latest PayWay transaction.
     */
    protected function resolvePaymentMethodId(WebsiteOrder $order): int
    {
        $paymentOption = DB::table('payment_transactions')
            ->where('order_id', $order->id)
            ->orderBy('id', 'desc')
            ->value('payment_option');

        $code = match ($paymentOption) {
            'abapay_khqr' => 'ABA KHQR',
            'cards' => 'VISA CARD',
            default => 'BANK TRANSFER',
        };

        $method = DB::table('financial_payment_methods')
            ->where('code', $code)
            ->first(['id']);

        return $method ? $method->id : 2;
    }
}
