<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Services\ExchangeService;
use App\Services\VoucherService;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExchangeController extends Controller
{
    protected ExchangeService $exchangeService;
    protected VoucherService $voucherService;

    public function __construct(ExchangeService $exchangeService, VoucherService $voucherService)
    {
        $this->exchangeService = $exchangeService;
        $this->voucherService = $voucherService;
    }

    /**
     * Lookup a sale for exchange
     * GET /api/pos/exchange/lookup-sale?sale_id={id}
     */
    public function lookupSale(Request $request): JsonResponse
    {
        $request->validate([
            'sale_id' => 'required|integer',
        ]);

        $result = $this->exchangeService->lookupSale($request->sale_id);

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? 'unknown') {
                'not_found' => 404,
                'too_old' => 400,
                default => 400
            };

            return response()->json($result, $statusCode);
        }

        return response()->json($result);
    }

    /**
     * Process an exchange
     * POST /api/pos/exchange/process
     */
    public function process(Request $request): JsonResponse
    {
        $data = $request->validate([
            'original_sale_id' => 'required|exists:sales,id',
            'shift_id' => 'required|exists:shifts,id',
            'returned_items' => 'required|array|min:1',
            'returned_items.*.sale_item_id' => 'required|exists:sale_items,id',
            'returned_items.*.quantity' => 'nullable|integer|min:1',
            'new_items' => 'nullable|array',
            'new_items.*.product_id' => 'required|exists:products,id',
            'new_items.*.quantity' => 'nullable|integer|min:1',
            // Support both single payment and multiple payments
            'payment' => 'nullable|array',
            'payment.method' => 'nullable|string',
            'payment.amount' => 'nullable|numeric|min:0',
            'payment.voucher_code' => 'nullable|string',
            'payments' => 'nullable|array',
            'payments.*.method' => 'required_with:payments|string',
            'payments.*.amount' => 'required_with:payments|numeric|min:0',
            'payments.*.voucher_code' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $shift = Shift::findOrFail($data['shift_id']);
        $store = $shift->store;
        $user = $shift->user;

        $result = $this->exchangeService->processExchange($data, $user, $store, $shift);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        $exchange = $result['exchange'];

        // Build updated sale data for frontend
        $sale = $result['sale'];
        $updatedSaleItems = $sale->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product?->name ?? ['en' => 'Unknown'],
                'ean' => $item->product?->ean ?? '',
                'price' => (float) $item->price,
                'quantity' => $item->quantity,
                'line_total' => (float) $item->price * $item->quantity,
                'discounts' => $item->discounts ?? [],
                'exchanged_at' => $item->exchanged_at?->toISOString(),
                'added_via_exchange_id' => $item->added_via_exchange_id,
            ];
        });

        $response = [
            'success' => true,
            'exchange' => [
                'id' => $exchange->id,
                'return_total' => (float) $exchange->return_total,
                'new_items_total' => (float) $exchange->new_items_total,
                'balance' => (float) $exchange->balance,
                'payment_received' => $exchange->payment_amount ? (float) $exchange->payment_amount : 0,
                'voucher_generated' => null,
            ],
            'updated_sale' => [
                'id' => $sale->id,
                'total' => (float) $sale->total,
                'items' => $updatedSaleItems,
            ],
            'receipt' => [
                'type' => $result['generated_voucher'] ? 'exchange_with_voucher' : 'exchange',
            ],
        ];

        if ($result['generated_voucher']) {
            $voucher = $result['generated_voucher'];
            $response['exchange']['voucher_generated'] = [
                'code' => $voucher->code,
                'amount' => (float) $voucher->amount,
                'expires_at' => $voucher->expires_at->format('Y-m-d'),
            ];
        }

        return response()->json($response);
    }

    /**
     * Validate a voucher
     * GET /api/pos/voucher/validate?code={code}
     */
    public function validateVoucher(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:12',
        ]);

        $result = $this->voucherService->validate($request->code);

        if (!$result['valid']) {
            $statusCode = match($result['error_code'] ?? 'unknown') {
                'not_found' => 404,
                default => 400
            };

            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'error_code' => $result['error_code'],
            ], $statusCode);
        }

        $voucher = $result['voucher'];

        return response()->json([
            'success' => true,
            'voucher' => [
                'code' => $voucher->code,
                'amount' => (float) $voucher->amount,
                'status' => $voucher->status,
                'expires_at' => $voucher->expires_at->format('Y-m-d'),
                'created_at' => $voucher->created_at->format('Y-m-d'),
                'source' => $voucher->source_type,
            ],
        ]);
    }

    /**
     * Apply a voucher to a sale
     * POST /api/pos/voucher/apply
     */
    public function applyVoucher(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'voucher_code' => 'required|string|size:12',
            'store_id' => 'required|exists:stores,id',
        ]);

        $result = $this->voucherService->validate($data['voucher_code']);

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 400);
        }

        $voucher = $result['voucher'];
        $sale = \App\Models\Sale::findOrFail($data['sale_id']);
        $store = Store::findOrFail($data['store_id']);

        $this->voucherService->applyToSale($voucher, $sale, $store);

        $remainingToPay = max(0, $sale->total - $voucher->amount);

        return response()->json([
            'success' => true,
            'voucher_amount' => (float) $voucher->amount,
            'remaining_to_pay' => round($remainingToPay, 2),
        ]);
    }
}
