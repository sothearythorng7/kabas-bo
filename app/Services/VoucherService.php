<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\Exchange;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VoucherService
{
    /**
     * Generate a unique voucher code (KBA + 9 digits)
     */
    public function generateCode(): string
    {
        do {
            $code = 'KBA' . str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (Voucher::where('code', $code)->exists());

        return $code;
    }

    /**
     * Create a voucher from an exchange (when customer has credit)
     */
    public function createFromExchange(Exchange $exchange, User $user, Store $store): Voucher
    {
        return Voucher::create([
            'code' => $this->generateCode(),
            'amount' => $exchange->balance,
            'status' => 'active',
            'source_type' => 'exchange',
            'source_exchange_id' => $exchange->id,
            'expires_at' => Carbon::now()->addMonths(6),
            'created_by_user_id' => $user->id,
            'created_at_store_id' => $store->id,
        ]);
    }

    /**
     * Create a manual voucher (admin only)
     * @param string $validity Validity period: 1_month, 3_months, 6_months, 1_year, 5_years
     */
    public function createManual(float $amount, User $createdBy, ?Store $store = null, string $validity = '6_months'): Voucher
    {
        return Voucher::create([
            'code' => $this->generateCode(),
            'amount' => $amount,
            'status' => 'active',
            'source_type' => 'manual',
            'expires_at' => $this->calculateExpirationDate($validity),
            'created_by_user_id' => $createdBy->id,
            'created_at_store_id' => $store?->id,
        ]);
    }

    /**
     * Calculate expiration date based on validity period
     */
    protected function calculateExpirationDate(string $validity): Carbon
    {
        return match($validity) {
            '1_month' => Carbon::now()->addMonth(),
            '3_months' => Carbon::now()->addMonths(3),
            '6_months' => Carbon::now()->addMonths(6),
            '1_year' => Carbon::now()->addYear(),
            '5_years' => Carbon::now()->addYears(5),
            default => Carbon::now()->addMonths(6),
        };
    }

    /**
     * Validate a voucher by code
     * Returns array with 'valid' boolean and 'voucher' or 'error' message
     */
    public function validate(string $code): array
    {
        $voucher = Voucher::where('code', strtoupper($code))->first();

        if (!$voucher) {
            return [
                'valid' => false,
                'error' => 'Voucher not found',
                'error_code' => 'not_found'
            ];
        }

        if ($voucher->status === 'used') {
            return [
                'valid' => false,
                'error' => 'Voucher has already been used',
                'error_code' => 'already_used',
                'voucher' => $voucher
            ];
        }

        if ($voucher->status === 'expired' || $voucher->expires_at->isPast()) {
            return [
                'valid' => false,
                'error' => 'Voucher has expired',
                'error_code' => 'expired',
                'voucher' => $voucher
            ];
        }

        if ($voucher->status === 'cancelled') {
            return [
                'valid' => false,
                'error' => 'Voucher has been cancelled',
                'error_code' => 'cancelled',
                'voucher' => $voucher
            ];
        }

        return [
            'valid' => true,
            'voucher' => $voucher
        ];
    }

    /**
     * Apply voucher to a sale
     */
    public function applyToSale(Voucher $voucher, Sale $sale, Store $store): Voucher
    {
        $voucher->update([
            'status' => 'used',
            'used_at' => now(),
            'used_in_sale_id' => $sale->id,
            'used_at_store_id' => $store->id,
        ]);

        return $voucher->fresh();
    }

    /**
     * Cancel a voucher
     */
    public function cancel(Voucher $voucher, User $cancelledBy, string $reason): Voucher
    {
        $voucher->update([
            'status' => 'cancelled',
            'cancelled_by_user_id' => $cancelledBy->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $voucher->fresh();
    }

    /**
     * Mark expired vouchers as expired
     * Should be called by scheduled task
     */
    public function expireVouchers(): int
    {
        return Voucher::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    /**
     * Get voucher statistics
     */
    public function getStatistics(?Carbon $from = null, ?Carbon $to = null, ?int $storeId = null): array
    {
        $query = Voucher::query();

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        if ($storeId) {
            $query->where('created_at_store_id', $storeId);
        }

        $stats = [
            'total_issued' => (clone $query)->count(),
            'total_issued_value' => (clone $query)->sum('amount'),
            'active' => (clone $query)->where('status', 'active')->count(),
            'active_value' => (clone $query)->where('status', 'active')->sum('amount'),
            'used' => (clone $query)->where('status', 'used')->count(),
            'used_value' => (clone $query)->where('status', 'used')->sum('amount'),
            'expired' => (clone $query)->where('status', 'expired')->count(),
            'expired_value' => (clone $query)->where('status', 'expired')->sum('amount'),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'cancelled_value' => (clone $query)->where('status', 'cancelled')->sum('amount'),
        ];

        return $stats;
    }
}
