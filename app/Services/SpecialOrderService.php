<?php

namespace App\Services;

use App\Mail\OrderConfirmationMail;
use App\Models\WebsiteOrder;
use App\Models\WebsitePaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SpecialOrderService
{
    const FINANCIAL_ACCOUNT_ID = 17;  // code 701 = Shop Sales
    const SYSTEM_USER_ID = 1;

    /**
     * Handle order paid: confirm order, deduct stock, create financial transaction, notify.
     */
    public function handleOrderPaid(WebsiteOrder $order, array $callbackData = []): void
    {
        // Guard against double-processing
        if ($order->payment_status === 'paid') {
            Log::info('Special order already paid, skipping', ['order_number' => $order->order_number]);
            return;
        }

        DB::transaction(function () use ($order, $callbackData) {
            // Update order status
            $order->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => 'cards',
                'payway_tran_id' => $callbackData['tran_id'] ?? null,
            ]);

            // Update payment transaction
            $transaction = WebsitePaymentTransaction::where('order_id', $order->id)
                ->where('merchant_ref_no', $callbackData['merchant_ref_no'] ?? null)
                ->first();

            if ($transaction) {
                $transaction->update([
                    'tran_id' => $callbackData['tran_id'] ?? null,
                    'status' => '0',
                    'status_label' => 'Approved',
                    'payment_option' => $callbackData['payment_option'] ?? 'cards',
                    'apv' => $callbackData['apv'] ?? null,
                    'internal_status' => 'completed',
                    'paid_at' => now(),
                    'raw_response' => $callbackData,
                ]);
            }

            // Deduct stock
            $this->deductStock($order);

            // Create financial transaction
            $this->createFinancialTransaction($order);
        });

        // Send confirmation email
        $this->sendConfirmationEmail($order);

        // Send Telegram notification
        $this->sendTelegramNotification($order);

        Log::info('Special order confirmed after payment', [
            'order_number' => $order->order_number,
            'total' => $order->total,
        ]);
    }

    /**
     * Deduct stock FIFO from warehouse (store_id=3).
     */
    public function deductStock(WebsiteOrder $order): void
    {
        $storeId = $order->store_id ?? 3;
        $order->load('items');

        foreach ($order->items as $item) {
            if ($item->item_type !== 'product' || !$item->product_id) {
                continue;
            }

            $remaining = $item->quantity;

            $batches = DB::table('stock_batches')
                ->where('store_id', $storeId)
                ->where('product_id', $item->product_id)
                ->where('quantity', '>', 0)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $deduct = min($remaining, $batch->quantity);

                DB::table('stock_batches')
                    ->where('id', $batch->id)
                    ->decrement('quantity', $deduct);

                DB::table('stock_transactions')->insert([
                    'stock_batch_id' => $batch->id,
                    'store_id' => $storeId,
                    'product_id' => $item->product_id,
                    'type' => 'out',
                    'quantity' => $deduct,
                    'reason' => 'website_sale',
                    'sale_id' => null,
                    'shift_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $remaining -= $deduct;
            }

            if ($remaining > 0) {
                Log::warning('Insufficient stock for special order item', [
                    'order_number' => $order->order_number,
                    'product_id' => $item->product_id,
                    'requested' => $item->quantity,
                    'short' => $remaining,
                ]);
            }
        }
    }

    /**
     * Create a credit financial transaction on account 701.
     */
    public function createFinancialTransaction(WebsiteOrder $order, string $paymentType = 'cards'): void
    {
        $storeId = $order->store_id ?? 3;

        $lastTxn = DB::table('financial_transactions')
            ->where('store_id', $storeId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first(['balance_after']);

        $balanceBefore = $lastTxn ? (float) $lastTxn->balance_after : 0;
        $balanceAfter = $balanceBefore + (float) $order->total;

        $paymentMethodId = $this->resolvePaymentMethodId($paymentType);

        DB::table('financial_transactions')->insert([
            'store_id' => $storeId,
            'account_id' => self::FINANCIAL_ACCOUNT_ID,
            'amount' => $order->total,
            'currency' => 'USD',
            'direction' => 'credit',
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'label' => "Special Order #{$order->order_number}",
            'description' => "Payment for special order {$order->order_number} - {$order->shipping_full_name}",
            'status' => 'validated',
            'transaction_date' => now(),
            'payment_method_id' => $paymentMethodId,
            'user_id' => $order->created_by_user_id ?? self::SYSTEM_USER_ID,
            'external_reference' => "WEB-SO-{$order->id}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Handle direct payment (cash or bank transfer) — no PayWay involved.
     */
    public function handleDirectPayment(WebsiteOrder $order, string $paymentType): void
    {
        DB::transaction(function () use ($order, $paymentType) {
            $order->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => $paymentType,
            ]);

            $this->deductStock($order);
            $this->createFinancialTransaction($order, $paymentType);
        });

        $this->sendTelegramNotification($order);

        Log::info('Special order confirmed with direct payment', [
            'order_number' => $order->order_number,
            'payment_type' => $paymentType,
            'total' => $order->total,
        ]);
    }

    protected function resolvePaymentMethodId(string $paymentType = 'cards'): int
    {
        $code = match ($paymentType) {
            'cash' => 'CASH',
            'bank_transfer' => 'BANK TRANSFER',
            'abapay_khqr' => 'ABA KHQR',
            default => 'VISA CARD',
        };

        $method = DB::table('financial_payment_methods')
            ->where('code', $code)
            ->first(['id']);

        return $method ? $method->id : 2;
    }

    protected function sendConfirmationEmail(WebsiteOrder $order): void
    {
        $email = $order->contact_email;
        if (!$email) {
            return;
        }

        try {
            Mail::to($email)->send(new OrderConfirmationMail($order->load('items')));
        } catch (\Exception $e) {
            Log::error('Failed to send special order confirmation email', [
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendTelegramNotification(WebsiteOrder $order): void
    {
        try {
            $telegram = new TelegramService();
            $message = "💳 <b>Special Order Paid</b>\n"
                . "Order: {$order->order_number}\n"
                . "Client: {$order->shipping_full_name}\n"
                . "Total: $" . number_format($order->total, 2) . "\n"
                . "Created by: " . ($order->createdByUser ? $order->createdByUser->name : 'System');

            $telegram->sendMessage($message);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification for special order', [
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

}
