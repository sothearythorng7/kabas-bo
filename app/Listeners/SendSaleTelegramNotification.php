<?php

namespace App\Listeners;

use App\Events\SaleCreated;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;

class SendSaleTelegramNotification
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    public function handle(SaleCreated $event): void
    {
        // Prevent duplicate notifications (cache for 60 seconds)
        $cacheKey = 'telegram_sale_notification_' . $event->sale->id;
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, 60);
        $sale = $event->sale;
        $sale->load(['shift.user', 'shift.store', 'items.product']);

        $storeName = $sale->shift->store->name ?? 'N/A';
        $storeId = $sale->shift->store->id ?? null;
        $sellerName = $sale->shift->user->name ?? 'N/A';
        $paymentType = $sale->payment_type;

        // Calculate totals
        $subtotal = 0;
        $totalItemDiscounts = 0;

        // Build items list
        $itemsText = '';
        foreach ($sale->items as $item) {
            $lineTotal = $item->price * $item->quantity;
            $subtotal += $lineTotal;

            if ($item->is_custom_service) {
                $name = '⚙️ ' . ($item->custom_service_description ?? 'Custom service');
            } elseif ($item->is_delivery) {
                $name = '🚚 Delivery';
            } else {
                $name = $item->product->name['en'] ?? $item->product->name['fr'] ?? 'Product';
            }

            $itemLine = "  • {$item->quantity}x {$name} - \${$item->price}";

            // Item-level discounts
            if (!empty($item->discounts)) {
                foreach ($item->discounts as $discount) {
                    $discountAmount = 0;
                    if (($discount['type'] ?? '') === 'percent') {
                        $discountAmount = $lineTotal * (($discount['value'] ?? 0) / 100);
                        $itemLine .= " <i>(-{$discount['value']}%)</i>";
                    } else {
                        $discountAmount = $discount['value'] ?? 0;
                        $itemLine .= " <i>(-\${$discountAmount})</i>";
                    }
                    $totalItemDiscounts += $discountAmount;
                }
            }

            $itemsText .= $itemLine . "\n";
        }

        // Sale-level discounts
        $saleDiscountsText = '';
        $totalSaleDiscounts = 0;
        if (!empty($sale->discounts)) {
            $afterItemDiscounts = $subtotal - $totalItemDiscounts;
            foreach ($sale->discounts as $discount) {
                $label = $discount['label'] ?? 'Discount';
                if (($discount['type'] ?? '') === 'percent') {
                    $discountAmount = $afterItemDiscounts * (($discount['value'] ?? 0) / 100);
                    $saleDiscountsText .= "  🏷️ {$label}: -{$discount['value']}% (-\$" . number_format($discountAmount, 2) . ")\n";
                } else {
                    $discountAmount = $discount['value'] ?? 0;
                    $saleDiscountsText .= "  🏷️ {$label}: -\$" . number_format($discountAmount, 2) . "\n";
                }
                $totalSaleDiscounts += $discountAmount;
            }
        }

        $message = "🛒 <b>New sale #{$sale->id}</b>\n\n";
        $message .= "🏪 <b>Store:</b> {$storeName}\n";
        $message .= "👤 <b>Seller:</b> {$sellerName}\n";
        $message .= "💳 <b>Payment:</b> {$paymentType}\n\n";
        $message .= "📦 <b>Items:</b>\n{$itemsText}";

        if ($saleDiscountsText) {
            $message .= "\n🎫 <b>Discounts:</b>\n{$saleDiscountsText}";
        }

        $message .= "\n💰 <b>Total:</b> \${$sale->total}";

        // Daily revenue summary
        if ($storeId) {
            $today = now()->startOfDay();
            $todayRevenue = \App\Models\Sale::where('store_id', $storeId)
                ->whereDate('created_at', $today)
                ->sum('total');

            // Same day last year
            $lastYearSameDay = now()->subYear()->startOfDay();
            $lastYearRevenue = \App\Models\Sale::where('store_id', $storeId)
                ->whereDate('created_at', $lastYearSameDay)
                ->sum('total');

            $message .= "\n\n━━━━━━━━━━━━━━━━━━━━";
            $message .= "\n📊 <b>Today's revenue:</b> \$" . number_format($todayRevenue, 2);

            if ($lastYearRevenue > 0) {
                $diff = $todayRevenue - $lastYearRevenue;
                $diffPercent = round(($diff / $lastYearRevenue) * 100, 1);
                $diffSign = $diff >= 0 ? '+' : '';
                $message .= "\n📅 <b>Same day last year:</b> \$" . number_format($lastYearRevenue, 2);
                $message .= " <i>({$diffSign}{$diffPercent}%)</i>";
            } else {
                $message .= "\n📅 <b>Same day last year:</b> No data";
            }
        }

        $message .= "\n\n################################";

        $this->telegram->sendMessage($message);
    }
}
