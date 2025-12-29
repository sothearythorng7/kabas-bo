<?php

namespace App\Services;

use App\Models\Exchange;
use App\Models\ExchangeItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\User;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\StockBatch;
use App\Models\StockTransaction;
use App\Models\Shift;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use App\Models\FinancialPaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExchangeService
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * Lookup a sale for exchange eligibility
     */
    public function lookupSale(int $saleId): array
    {
        $sale = Sale::with(['items.product', 'store'])->find($saleId);

        if (!$sale) {
            return [
                'success' => false,
                'error' => 'Sale not found',
                'error_code' => 'not_found'
            ];
        }

        $daysSincePurchase = $sale->created_at->diffInDays(now());
        $isExchangeable = $daysSincePurchase <= 30;

        if (!$isExchangeable) {
            return [
                'success' => false,
                'error' => 'Sale is older than 30 days',
                'error_code' => 'too_old',
                'days_since_purchase' => $daysSincePurchase
            ];
        }

        $items = $sale->items->map(function ($item) {
            $isExchangeable = $item->isExchangeable();
            $exchangeReason = null;

            if (!$isExchangeable) {
                $exchangeReason = 'Already exchanged';
            }

            return [
                'sale_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'Unknown product',
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->price,
                'is_exchangeable' => $isExchangeable,
                'exchanged_at' => $item->exchanged_at?->toISOString(),
                'exchange_reason' => $exchangeReason,
            ];
        });

        return [
            'success' => true,
            'sale' => [
                'id' => $sale->id,
                'created_at' => $sale->created_at->toISOString(),
                'store' => $sale->store?->name ?? 'Unknown',
                'days_since_purchase' => $daysSincePurchase,
                'is_exchangeable' => true,
                'items' => $items,
            ]
        ];
    }

    /**
     * Process an exchange - modifies the original sale
     */
    public function processExchange(array $data, User $user, Store $store, ?Shift $shift = null): array
    {
        $originalSale = Sale::with('items')->find($data['original_sale_id']);

        if (!$originalSale) {
            return [
                'success' => false,
                'error' => 'Original sale not found',
                'error_code' => 'sale_not_found'
            ];
        }

        // Validate sale is within 30 days
        if ($originalSale->created_at->diffInDays(now()) > 30) {
            return [
                'success' => false,
                'error' => 'Sale is older than 30 days',
                'error_code' => 'too_old'
            ];
        }

        // Validate returned items
        $returnedItems = $data['returned_items'] ?? [];
        if (empty($returnedItems)) {
            return [
                'success' => false,
                'error' => 'No items to return',
                'error_code' => 'no_items'
            ];
        }

        $result = DB::transaction(function () use ($data, $originalSale, $user, $store, $shift, $returnedItems) {
            $returnTotal = 0;
            $validatedReturnItems = [];

            // Validate and calculate return total
            foreach ($returnedItems as $returnItem) {
                $saleItem = $originalSale->items->find($returnItem['sale_item_id']);

                if (!$saleItem) {
                    throw new \Exception("Sale item {$returnItem['sale_item_id']} not found");
                }

                if (!$saleItem->isExchangeable()) {
                    throw new \Exception("Item {$returnItem['sale_item_id']} has already been exchanged");
                }

                $quantity = $returnItem['quantity'] ?? $saleItem->quantity;
                if ($quantity > $saleItem->quantity) {
                    throw new \Exception("Cannot return more than purchased quantity");
                }

                $unitPrice = (float) $saleItem->price;
                $totalPrice = $unitPrice * $quantity;
                $returnTotal += $totalPrice;

                $validatedReturnItems[] = [
                    'sale_item' => $saleItem,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ];
            }

            // Calculate new items total
            $newItemsTotal = 0;
            $newItems = $data['new_items'] ?? [];
            $newItemsData = [];

            foreach ($newItems as $newItem) {
                $product = Product::find($newItem['product_id']);
                if (!$product) {
                    throw new \Exception("Product {$newItem['product_id']} not found");
                }
                $quantity = $newItem['quantity'] ?? 1;
                $itemTotal = $product->price * $quantity;
                $newItemsTotal += $itemTotal;

                $newItemsData[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'total' => $itemTotal,
                ];
            }

            // Calculate balance (positive = customer credit, negative = customer owes)
            $balance = $returnTotal - $newItemsTotal;

            // Create the exchange record for history/audit
            $exchange = Exchange::create([
                'original_sale_id' => $originalSale->id,
                'store_id' => $store->id,
                'user_id' => $user->id,
                'return_total' => $returnTotal,
                'new_items_total' => $newItemsTotal,
                'balance' => $balance,
                'notes' => $data['notes'] ?? null,
            ]);

            // Process returned items - handle partial exchanges
            foreach ($validatedReturnItems as $item) {
                $saleItem = $item['sale_item'];
                $returnedQty = $item['quantity'];
                $originalQty = $saleItem->quantity;

                // Create exchange item record for history
                $exchangeItem = ExchangeItem::create([
                    'exchange_id' => $exchange->id,
                    'original_sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $returnedQty,
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'type' => 'returned',
                ]);

                if ($returnedQty >= $originalQty) {
                    // Full exchange - mark entire item as exchanged
                    $saleItem->update([
                        'exchanged_at' => now(),
                        'exchanged_in_exchange_id' => $exchange->id,
                    ]);
                } else {
                    // Partial exchange - reduce quantity on original item
                    $saleItem->update([
                        'quantity' => $originalQty - $returnedQty,
                    ]);
                }

                // Return stock to inventory
                $stockBatch = StockBatch::create([
                    'product_id' => $saleItem->product_id,
                    'store_id' => $store->id,
                    'quantity' => $returnedQty,
                    'unit_price' => $item['unit_price'],
                    'source_exchange_id' => $exchange->id,
                ]);

                $exchangeItem->update(['stock_batch_id' => $stockBatch->id]);

                // Create stock transaction for audit
                StockTransaction::create([
                    'stock_batch_id' => $stockBatch->id,
                    'store_id' => $store->id,
                    'product_id' => $saleItem->product_id,
                    'type' => 'in',
                    'quantity' => $returnedQty,
                    'reason' => 'exchange_return',
                    'shift_id' => $shift?->id,
                ]);
            }

            // Process new items - ADD to original sale
            foreach ($newItemsData as $newItemData) {
                $product = $newItemData['product'];
                $quantity = $newItemData['quantity'];

                // Create new sale item on the ORIGINAL sale
                $newSaleItem = SaleItem::create([
                    'sale_id' => $originalSale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'discounts' => [],
                    'added_via_exchange_id' => $exchange->id, // Track that this was added via exchange
                ]);

                // Record in exchange items for history
                ExchangeItem::create([
                    'exchange_id' => $exchange->id,
                    'new_sale_item_id' => $newSaleItem->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'total_price' => $product->price * $quantity,
                    'type' => 'new', // Mark as new item
                ]);

                // Decrement stock FIFO
                $this->decrementStockFIFO($store->id, $product->id, $quantity, $originalSale->id, $shift?->id, 'exchange_new_item');
            }

            // Recalculate sale total (only non-exchanged items)
            $newTotal = $originalSale->items()
                ->whereNull('exchanged_at')
                ->get()
                ->sum(function ($item) {
                    return $item->price * $item->quantity;
                });

            $originalSale->update([
                'total' => $newTotal,
                'updated_at' => now(),
            ]);

            // Handle balance - voucher generation or payment
            $generatedVoucher = null;
            $financialTransaction = null;

            // Get financial account for POS sales (701)
            $financialAccount = FinancialAccount::where('code', '701')->first();

            if ($balance > 0) {
                // Customer has credit - generate voucher
                $generatedVoucher = $this->voucherService->createFromExchange($exchange, $user, $store);
                $exchange->update(['generated_voucher_id' => $generatedVoucher->id]);

                // Create a DEBIT financial transaction (money goes out as voucher credit)
                if ($financialAccount) {
                    $financialTransaction = $this->createFinancialTransaction(
                        $store,
                        $financialAccount,
                        $balance,
                        'debit',
                        "Échange #{$exchange->id} - Voucher {$generatedVoucher->code}",
                        "Crédit client suite à échange. Vente originale #{$originalSale->id}. Voucher généré: {$generatedVoucher->code}",
                        $user,
                        $exchange,
                        'voucher'
                    );
                    $exchange->update(['financial_transaction_id' => $financialTransaction->id]);
                }
            } elseif ($balance < 0) {
                // Customer owes money - handle split payments
                $payments = $data['payments'] ?? [];

                // Backward compatibility: convert single payment to array
                if (empty($payments) && !empty($data['payment'])) {
                    $payments = [$data['payment']];
                }

                // Validate that payment is provided when customer owes money
                if (empty($payments)) {
                    throw new \Exception("Payment required: customer owes $" . number_format(abs($balance), 2));
                }

                // Validate total payment matches amount due
                $totalPaymentAmount = array_sum(array_column($payments, 'amount'));
                $amountDue = abs($balance);
                if (abs($totalPaymentAmount - $amountDue) > 0.01) {
                    throw new \Exception("Payment amount ($" . number_format($totalPaymentAmount, 2) . ") does not match amount due ($" . number_format($amountDue, 2) . ")");
                }

                if (!empty($payments)) {
                    $paymentMethods = [];
                    $totalPaid = 0;
                    $firstTransactionId = null;

                    foreach ($payments as $paymentData) {
                        $paymentMethod = $paymentData['method'] ?? 'cash';
                        $paymentAmount = (float)($paymentData['amount'] ?? 0);
                        $voucherCode = $paymentData['voucher_code'] ?? null;

                        $paymentMethods[] = $paymentMethod;
                        $totalPaid += $paymentAmount;

                        // Handle voucher payment
                        if ($paymentMethod === 'voucher' && $voucherCode) {
                            $voucherValidation = $this->voucherService->validate($voucherCode);

                            if (!$voucherValidation['valid']) {
                                throw new \Exception($voucherValidation['error']);
                            }

                            $paymentVoucher = $voucherValidation['voucher'];

                            if ($paymentVoucher->amount > $paymentAmount) {
                                // Voucher has more value than used - generate change voucher
                                $changeAmount = $paymentVoucher->amount - $paymentAmount;

                                $this->voucherService->useVoucher($paymentVoucher, $originalSale, $store);

                                $changeVoucher = Voucher::create([
                                    'code' => $this->voucherService->generateCode(),
                                    'amount' => $changeAmount,
                                    'status' => 'active',
                                    'source_type' => 'exchange_change',
                                    'source_exchange_id' => $exchange->id,
                                    'expires_at' => Carbon::now()->addMonths(6),
                                    'created_by_user_id' => $user->id,
                                    'created_at_store_id' => $store->id,
                                ]);

                                $exchange->update([
                                    'payment_voucher_id' => $paymentVoucher->id,
                                    'generated_voucher_id' => $changeVoucher->id,
                                ]);

                                $generatedVoucher = $changeVoucher;
                            } else {
                                // Use full voucher value
                                $this->voucherService->useVoucher($paymentVoucher, $originalSale, $store);
                                $exchange->update(['payment_voucher_id' => $paymentVoucher->id]);
                            }
                            // No financial transaction for voucher payments
                        } else {
                            // Cash or card payment - create CREDIT financial transaction
                            if ($financialAccount && $paymentAmount > 0) {
                                $transaction = $this->createFinancialTransaction(
                                    $store,
                                    $financialAccount,
                                    $paymentAmount,
                                    'credit',
                                    "Échange #{$exchange->id} - Paiement {$paymentMethod}",
                                    "Paiement reçu suite à échange. Vente originale #{$originalSale->id}. Méthode: {$paymentMethod}. Montant: {$paymentAmount}",
                                    $user,
                                    $exchange,
                                    $paymentMethod
                                );

                                if (!$firstTransactionId) {
                                    $firstTransactionId = $transaction->id;
                                }
                            }
                        }
                    }

                    // Update exchange with payment info
                    $exchange->update([
                        'payment_method' => implode(',', array_unique($paymentMethods)),
                        'payment_amount' => $totalPaid,
                    ]);

                    if ($firstTransactionId) {
                        $exchange->update(['financial_transaction_id' => $firstTransactionId]);
                    }
                }
            }

            // Reload the sale with updated items
            $originalSale->refresh();
            $originalSale->load('items.product');

            return [
                'success' => true,
                'exchange' => $exchange->fresh()->load(['items', 'generatedVoucher', 'paymentVoucher']),
                'generated_voucher' => $generatedVoucher,
                'sale' => $originalSale, // Return the updated sale
            ];
        });

        return $result;
    }

    /**
     * Decrement stock using FIFO method
     */
    protected function decrementStockFIFO(int $storeId, int $productId, int $quantity, ?int $saleId = null, ?int $shiftId = null, string $reason = 'sale'): void
    {
        $remaining = $quantity;

        $batches = StockBatch::where('store_id', $storeId)
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $deduct = min($batch->quantity, $remaining);
            if ($deduct <= 0) continue;

            $batch->decrement('quantity', $deduct);

            StockTransaction::create([
                'stock_batch_id' => $batch->id,
                'store_id' => $storeId,
                'product_id' => $productId,
                'type' => 'out',
                'quantity' => $deduct,
                'reason' => $reason,
                'sale_id' => $saleId,
                'shift_id' => $shiftId,
            ]);

            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            Log::warning("Stock insuffisant pour l'échange", [
                'store_id' => $storeId,
                'product_id' => $productId,
                'missing' => $remaining,
            ]);
        }
    }

    /**
     * Get exchange statistics
     */
    public function getStatistics(?Carbon $from = null, ?Carbon $to = null, ?int $storeId = null): array
    {
        $query = Exchange::query();

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return [
            'total_exchanges' => (clone $query)->count(),
            'total_return_value' => (clone $query)->sum('return_total'),
            'total_new_items_value' => (clone $query)->sum('new_items_total'),
            'total_balance' => (clone $query)->sum('balance'),
            'exchanges_with_voucher' => (clone $query)->whereNotNull('generated_voucher_id')->count(),
            'exchanges_with_payment' => (clone $query)->where('balance', '<', 0)->count(),
        ];
    }

    /**
     * Create a financial transaction for exchange
     */
    protected function createFinancialTransaction(
        Store $store,
        FinancialAccount $account,
        float $amount,
        string $direction,
        string $label,
        string $description,
        User $user,
        Exchange $exchange,
        string $paymentMethodCode = 'cash'
    ): FinancialTransaction {
        // Get current balance
        $lastTransaction = FinancialTransaction::where('account_id', $account->id)
            ->orderBy('id', 'desc')
            ->first();

        $balanceBefore = $lastTransaction ? (float)$lastTransaction->balance_after : 0;

        // Calculate new balance
        if ($direction === 'credit') {
            $balanceAfter = $balanceBefore + $amount;
        } else {
            $balanceAfter = $balanceBefore - $amount;
        }

        // Get payment method - try exact match first (case-insensitive), then use mapping
        $paymentMethod = FinancialPaymentMethod::whereRaw('LOWER(code) = ?', [strtolower($paymentMethodCode)])->first();

        if (!$paymentMethod) {
            // Fallback mapping for common aliases
            $paymentMethodMap = [
                'cash' => 'CASH',
                'card' => 'VISA CARD',
                'credit_card' => 'VISA CARD',
                'cb' => 'VISA CARD',
                'voucher' => 'VOUCHER',
            ];
            $code = $paymentMethodMap[strtolower($paymentMethodCode)] ?? 'CASH';
            $paymentMethod = FinancialPaymentMethod::where('code', $code)->first();
        }

        return FinancialTransaction::create([
            'store_id' => $store->id,
            'account_id' => $account->id,
            'amount' => $amount,
            'currency' => 'USD',
            'direction' => $direction,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'label' => $label,
            'description' => $description,
            'status' => 'validated',
            'transaction_date' => now(),
            'payment_method_id' => $paymentMethod?->id,
            'user_id' => $user->id,
            'external_reference' => "exchange_{$exchange->id}",
        ]);
    }
}
