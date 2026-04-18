<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftUser;
use App\Models\User;
use App\Models\Sale;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ShiftController extends Controller
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }
    // Check if a shift is in progress
    public function currentShift($userId)
    {
        $shift = Shift::where('user_id', $userId)
                      ->whereNull('ended_at')
                      ->first();

        return response()->json($shift);
    }

    // Start a shift
    public function start(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_amount' => 'required|numeric',
            'popup_event_id' => 'nullable|exists:popup_events,id',
        ]);

        // Check that there is no active shift already
        $existing = Shift::where('user_id', $request->user_id)
                        ->whereNull('ended_at')
                        ->first();
        if ($existing) {
            return response()->json(['error' => 'Shift already open'], 422);
        }

        $user = User::findOrFail($request->user_id);

        $now = Carbon::now();

        $shift = Shift::create([
            'user_id' => $request->user_id,
            'store_id' => $user->store_id,
            'popup_event_id' => $request->popup_event_id,
            'opening_cash' => $request->start_amount,
            'started_at' => $now,
        ]);

        // Record the first user in shift_users
        ShiftUser::create([
            'shift_id' => $shift->id,
            'user_id' => $request->user_id,
            'started_at' => $now,
        ]);

        // Telegram notification
        $store = $shift->store;
        $this->telegram->sendMessage(
            "🟢 <b>Shift Opened</b>\n\n"
            . "🏪 <b>Store:</b> {$store->name}\n"
            . "👤 <b>Cashier:</b> {$user->name}\n"
            . "💵 <b>Opening cash:</b> $" . number_format($shift->opening_cash, 2) . "\n"
            . "🕐 <b>Time:</b> {$now->format('d/m/Y H:i')}"
        );

        return response()->json($shift);
    }

    // End a shift
    public function end(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'end_amount' => 'required|numeric',
            'visitors_count' => 'nullable|integer|min:0',
            'cash_difference' => 'nullable|numeric',
            'cash_in' => 'nullable|numeric|min:0',
            'cash_out' => 'nullable|numeric|min:0',
        ]);

        $shift = Shift::where('user_id', $request->user_id)
                    ->whereNull('ended_at')
                    ->first();

        if (!$shift) {
            return response()->json(['error' => 'No shift in progress'], 422);
        }

        $now = Carbon::now();

        $shift->update([
            'closing_cash' => $request->end_amount,
            'visitors_count' => $request->visitors_count,
            'cash_difference' => $request->cash_difference,
            'cash_in' => $request->cash_in ?? 0,
            'cash_out' => $request->cash_out ?? 0,
            'ended_at' => $now,
        ]);

        // Close the last active user session
        ShiftUser::where('shift_id', $shift->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => $now]);

        // Telegram notification with shift summary
        $this->sendShiftClosedTelegram($shift, $now);

        return response()->json($shift);
    }

    // Change user during a shift
    public function changeUser(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'old_user_id' => 'required|exists:users,id',
            'new_user_id' => 'required|exists:users,id',
        ]);

        $shift = Shift::where('id', $request->shift_id)
            ->whereNull('ended_at')
            ->first();

        if (!$shift) {
            return response()->json(['error' => 'No shift in progress'], 422);
        }

        $now = Carbon::now();

        // Close the old user's session
        ShiftUser::where('shift_id', $shift->id)
            ->where('user_id', $request->old_user_id)
            ->whereNull('ended_at')
            ->update(['ended_at' => $now]);

        // Start a new session for the new user
        ShiftUser::create([
            'shift_id' => $shift->id,
            'user_id' => $request->new_user_id,
            'started_at' => $now,
        ]);

        // Update the shift's current user
        $shift->update(['user_id' => $request->new_user_id]);

        // Load the new user data
        $newUser = User::find($request->new_user_id);

        return response()->json([
            'shift' => $shift,
            'user' => $newUser,
        ]);
    }

    // Calculate expected cash from shift sales
    public function expectedCash($userId)
    {
        $shift = Shift::where('user_id', $userId)
                    ->whereNull('ended_at')
                    ->first();

        if (!$shift) {
            return response()->json(['error' => 'No shift in progress'], 422);
        }

        // Calculate cash sales for this shift
        // Check for payment_type CASH (case-insensitive) or split_payments containing cash
        $cashSales = Sale::where('shift_id', $shift->id)
            ->where(function($query) {
                $query->whereRaw("LOWER(payment_type) = 'cash'")
                    ->orWhereRaw("LOWER(payment_type) = 'espèces'")
                    // Check split_payments with payment_type key (frontend uses payment_type, not method)
                    ->orWhereRaw("LOWER(JSON_EXTRACT(split_payments, '$[*].payment_type')) LIKE '%cash%'")
                    ->orWhereRaw("LOWER(JSON_EXTRACT(split_payments, '$[*].payment_type')) LIKE '%espèces%'");
            })
            ->get();

        $totalCashFromSales = 0;

        foreach ($cashSales as $sale) {
            if ($sale->split_payments && is_array($sale->split_payments)) {
                // If split payments, only count cash portion
                foreach ($sale->split_payments as $payment) {
                    // Check both 'method' and 'payment_type' keys for compatibility
                    $paymentMethod = $payment['payment_type'] ?? $payment['method'] ?? '';
                    if (in_array(strtolower($paymentMethod), ['cash', 'espèces'])) {
                        $totalCashFromSales += floatval($payment['amount'] ?? 0);
                    }
                }
            } else {
                // Full amount is cash
                $totalCashFromSales += floatval($sale->total ?? 0);
            }
        }

        $expectedCash = floatval($shift->opening_cash) + $totalCashFromSales;

        return response()->json([
            'opening_cash' => floatval($shift->opening_cash),
            'cash_from_sales' => $totalCashFromSales,
            'expected_cash' => $expectedCash,
        ]);
    }

    // Get sales by date
    public function salesByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $date = Carbon::parse($request->date);

        // Build query for sales on this date
        $query = Sale::whereDate('created_at', $date)
            ->with(['items.product', 'shift.user', 'exchanges.items.product', 'exchanges.generatedVoucher']);

        // If user_id is provided, filter by that user's shifts only
        if ($request->user_id) {
            $userShiftIds = Shift::where('user_id', $request->user_id)->pluck('id');
            $query->whereIn('shift_id', $userShiftIds);
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        if ($sales->isEmpty()) {
            return response()->json([
                'shifts' => [],
                'sales' => [],
                'message' => 'No sales found for this date'
            ]);
        }

        // Get the unique shifts for these sales
        $shiftIds = $sales->pluck('shift_id')->unique();
        $shifts = Shift::whereIn('id', $shiftIds)->get();

        return response()->json([
            'shifts' => $shifts,
            'sales' => $sales
        ]);
    }

    /**
     * Send Telegram notification when a shift is closed with full summary.
     */
    protected function sendShiftClosedTelegram(Shift $shift, Carbon $now): void
    {
        try {
            $shift->load(['user', 'store']);
            $sales = Sale::where('shift_id', $shift->id)->get();

            $salesCount = $sales->count();
            $totalIncome = $sales->sum('total');

            // Income breakdown by payment method
            $byMethod = [];
            foreach ($sales as $sale) {
                if ($sale->split_payments && is_array($sale->split_payments)) {
                    foreach ($sale->split_payments as $payment) {
                        $method = strtoupper($payment['payment_type'] ?? $payment['method'] ?? 'OTHER');
                        $byMethod[$method] = ($byMethod[$method] ?? 0) + floatval($payment['amount'] ?? 0);
                    }
                } else {
                    $method = strtoupper($sale->payment_type ?? 'OTHER');
                    $byMethod[$method] = ($byMethod[$method] ?? 0) + floatval($sale->total);
                }
            }

            // Duration
            $startedAt = Carbon::parse($shift->started_at);
            $duration = $startedAt->diff($now);
            $durationStr = $duration->h . 'h' . str_pad($duration->i, 2, '0', STR_PAD_LEFT);

            // Walk-in & conversion
            $visitors = (int) $shift->visitors_count;
            $conversion = $visitors > 0 ? round(($salesCount / $visitors) * 100, 1) : 0;

            // Staff who worked this shift
            $staffNames = $shift->users()->pluck('name')->implode(', ');

            // Cash summary
            $openingCash = floatval($shift->opening_cash);
            $closingCash = floatval($shift->closing_cash);
            $cashIn = floatval($shift->cash_in ?? 0);
            $cashOut = floatval($shift->cash_out ?? 0);
            $cashFromSales = $byMethod['CASH'] ?? $byMethod['ESPÈCES'] ?? 0;
            $expectedCash = $openingCash + $cashFromSales + $cashIn - $cashOut;
            $difference = $closingCash - $expectedCash;

            // Build message
            $msg = "🔴 <b>Shift Closed</b>\n\n";
            $msg .= "🏪 <b>Store:</b> {$shift->store->name}\n";
            $msg .= "👥 <b>Staff:</b> {$staffNames}\n";
            $msg .= "🕐 <b>Duration:</b> {$startedAt->format('H:i')} → {$now->format('H:i')} ({$durationStr})\n";
            $msg .= "\n━━━━━━━━━━━━━━━━━━━━\n";

            // Income
            $msg .= "\n💰 <b>Income:</b> $" . number_format($totalIncome, 2) . "\n";
            $msg .= "🧾 <b>Sales:</b> {$salesCount}\n";
            $avgBasket = $salesCount > 0 ? $totalIncome / $salesCount : 0;
            $msg .= "🛒 <b>Avg basket:</b> $" . number_format($avgBasket, 2) . "\n\n";

            // Payment methods breakdown
            if (!empty($byMethod)) {
                $msg .= "💳 <b>By payment method:</b>\n";
                foreach ($byMethod as $method => $amount) {
                    $msg .= "  • {$method}: $" . number_format($amount, 2) . "\n";
                }
            }

            // Cash summary
            $msg .= "\n━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "\n💵 <b>Cash summary:</b>\n";
            $msg .= "  Opening: $" . number_format($openingCash, 2) . "\n";
            $msg .= "  + Sales (cash): $" . number_format($cashFromSales, 2) . "\n";
            if ($cashIn > 0) $msg .= "  + Cash in: $" . number_format($cashIn, 2) . "\n";
            if ($cashOut > 0) $msg .= "  - Cash out: $" . number_format($cashOut, 2) . "\n";
            $msg .= "  = Expected: $" . number_format($expectedCash, 2) . "\n";
            $msg .= "  Closing: $" . number_format($closingCash, 2) . "\n";
            if (abs($difference) >= 0.01) {
                $diffIcon = $difference > 0 ? '📈' : '📉';
                $msg .= "  {$diffIcon} <b>Difference: $" . number_format($difference, 2) . "</b>\n";
            } else {
                $msg .= "  ✅ <b>Cash balanced</b>\n";
            }

            // Walk-in & conversion
            if ($visitors > 0) {
                $msg .= "\n━━━━━━━━━━━━━━━━━━━━\n";
                $msg .= "\n🚶 <b>Walk-in:</b> {$visitors}\n";
                $msg .= "📊 <b>Conversion:</b> {$conversion}% ({$salesCount}/{$visitors})\n";
            }

            $this->telegram->sendMessage($msg);
        } catch (\Exception $e) {
            \Log::error('Shift Telegram notification failed', ['error' => $e->getMessage()]);
        }
    }
}
