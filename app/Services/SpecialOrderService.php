<?php

namespace App\Services;

use App\Mail\OrderConfirmationMail;
use App\Models\FinancialAccount;
use App\Models\Store;
use App\Models\WebsiteOrder;
use App\Models\WebsitePaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SpecialOrderService
{

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

            // Stock is deducted only when status changes to shipped/delivered
            // (triggered from SpecialOrderController::update)

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
     * Check if stock has already been deducted for this order.
     */
    public function hasStockBeenDeducted(WebsiteOrder $order): bool
    {
        return DB::table('stock_transactions')
            ->where('reason', 'special_order_' . $order->id)
            ->exists();
    }

    /**
     * Deduct stock FIFO if not already done.
     */
    public function deductStockIfNeeded(WebsiteOrder $order): bool
    {
        if ($this->hasStockBeenDeducted($order)) {
            return false;
        }

        $this->deductStock($order);
        return true;
    }

    /**
     * Deduct stock FIFO from the order's store.
     */
    public function deductStock(WebsiteOrder $order): void
    {
        $storeId = $order->store_id ?? Store::warehouseId();
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
                    'reason' => 'special_order_' . $order->id,
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
        $storeId = $order->store_id ?? Store::warehouseId();

        // If a deposit was already credited separately, only credit the remainder.
        $alreadyCredited = (float) DB::table('financial_transactions')
            ->where('external_reference', "WEB-SO-{$order->id}-DEPOSIT")
            ->sum('amount');

        $amount = round((float) $order->total - $alreadyCredited, 5);

        if ($amount <= 0) {
            return;
        }

        $lastTxn = DB::table('financial_transactions')
            ->where('store_id', $storeId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first(['balance_after']);

        $balanceBefore = $lastTxn ? (float) $lastTxn->balance_after : 0;
        $balanceAfter = $balanceBefore + $amount;

        $paymentMethodId = $this->resolvePaymentMethodId($paymentType);

        DB::table('financial_transactions')->insert([
            'store_id' => $storeId,
            'account_id' => FinancialAccount::idByCode('701'),
            'amount' => $amount,
            'currency' => 'USD',
            'direction' => 'credit',
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'label' => "Special Order #{$order->order_number}",
            'description' => "Payment for special order {$order->order_number} - {$order->shipping_full_name}",
            'status' => 'validated',
            'transaction_date' => now(),
            'payment_method_id' => $paymentMethodId,
            'user_id' => $order->created_by_user_id ?? 1,
            'external_reference' => "WEB-SO-{$order->id}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create a credit financial transaction for a deposit payment on a special order.
     * Idempotent: skips if a deposit transaction already exists for this order.
     */
    public function createDepositFinancialTransaction(WebsiteOrder $order, string $paymentType = 'cash'): void
    {
        $storeId = $order->store_id ?? Store::warehouseId();
        $amount = round((float) $order->deposit_amount, 5);

        if ($amount <= 0) {
            return;
        }

        $exists = DB::table('financial_transactions')
            ->where('external_reference', "WEB-SO-{$order->id}-DEPOSIT")
            ->exists();

        if ($exists) {
            return;
        }

        $lastTxn = DB::table('financial_transactions')
            ->where('store_id', $storeId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first(['balance_after']);

        $balanceBefore = $lastTxn ? (float) $lastTxn->balance_after : 0;
        $balanceAfter = $balanceBefore + $amount;

        $paymentMethodId = $this->resolvePaymentMethodId($paymentType);

        DB::table('financial_transactions')->insert([
            'store_id' => $storeId,
            'account_id' => FinancialAccount::idByCode('701'),
            'amount' => $amount,
            'currency' => 'USD',
            'direction' => 'credit',
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'label' => "Special Order #{$order->order_number} - Deposit",
            'description' => "Deposit for special order {$order->order_number} - {$order->shipping_full_name}",
            'status' => 'validated',
            'transaction_date' => now(),
            'payment_method_id' => $paymentMethodId,
            'user_id' => $order->created_by_user_id ?? 1,
            'external_reference' => "WEB-SO-{$order->id}-DEPOSIT",
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

            // Stock is deducted only when status changes to shipped/delivered
            // (triggered from SpecialOrderController::update)

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

            $telegram->sendMessage($message, 'HTML', config('services.telegram.private_chat_id'));
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification for special order', [
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

}
