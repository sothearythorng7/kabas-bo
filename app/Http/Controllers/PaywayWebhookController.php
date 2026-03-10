<?php

namespace App\Http\Controllers;

use App\Models\WebsiteOrder;
use App\Models\WebsitePaymentTransaction;
use App\Services\SpecialOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaywayWebhookController extends Controller
{
    public function __construct(
        protected SpecialOrderService $specialOrderService,
    ) {}

    public function handlePaymentLinkCallback(Request $request)
    {
        Log::info('PayWay payment link callback received', $request->all());

        $merchantRefNo = $request->input('merchant_ref_no');
        $status = $request->input('status');
        $tranId = $request->input('tran_id');

        if (!$merchantRefNo) {
            Log::warning('PayWay callback missing merchant_ref_no');
            return response()->json(['message' => 'Missing merchant_ref_no'], 400);
        }

        $transaction = WebsitePaymentTransaction::where('merchant_ref_no', $merchantRefNo)->first();

        if (!$transaction) {
            Log::warning('PayWay callback: transaction not found', ['merchant_ref_no' => $merchantRefNo]);
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $order = WebsiteOrder::find($transaction->order_id);

        if (!$order) {
            Log::warning('PayWay callback: order not found', ['order_id' => $transaction->order_id]);
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Status 0 = Approved
        if ((string) $status === '0') {
            $this->specialOrderService->handleOrderPaid($order, [
                'merchant_ref_no' => $merchantRefNo,
                'tran_id' => $tranId,
                'status' => $status,
                'payment_option' => $request->input('payment_option', 'cards'),
                'apv' => $request->input('apv'),
            ]);

            return response()->json(['message' => 'OK']);
        }

        // Non-approved status: update transaction with the status
        $transaction->update([
            'tran_id' => $tranId,
            'status' => (string) $status,
            'internal_status' => 'failed',
            'raw_response' => $request->all(),
        ]);

        Log::info('PayWay callback: non-approved status', [
            'order_number' => $order->order_number,
            'status' => $status,
        ]);

        return response()->json(['message' => 'OK']);
    }
}
