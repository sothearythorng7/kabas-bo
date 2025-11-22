<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\User;
use App\Models\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ShiftController extends Controller
{
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
        ]);

        // Check that there is no active shift already
        $existing = Shift::where('user_id', $request->user_id)
                        ->whereNull('ended_at')
                        ->first();
        if ($existing) {
            return response()->json(['error' => 'Shift already open'], 422);
        }

        $user = User::findOrFail($request->user_id);

        $shift = Shift::create([
            'user_id' => $request->user_id,
            'store_id' => $user->store_id,
            'opening_cash' => $request->start_amount,
            'started_at' => Carbon::now(),
        ]);

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
        ]);

        $shift = Shift::where('user_id', $request->user_id)
                    ->whereNull('ended_at')
                    ->first();

        if (!$shift) {
            return response()->json(['error' => 'No shift in progress'], 422);
        }

        $shift->update([
            'closing_cash' => $request->end_amount,
            'visitors_count' => $request->visitors_count,
            'cash_difference' => $request->cash_difference,
            'ended_at' => Carbon::now(),
        ]);

        return response()->json($shift);
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
        $cashSales = Sale::where('shift_id', $shift->id)
            ->where(function($query) {
                $query->where('payment_type', 'cash')
                    ->orWhere('payment_type', 'espèces')
                    ->orWhereRaw("JSON_CONTAINS(split_payments, JSON_OBJECT('method', 'cash'))")
                    ->orWhereRaw("JSON_CONTAINS(split_payments, JSON_OBJECT('method', 'espèces'))");
            })
            ->get();

        $totalCashFromSales = 0;

        foreach ($cashSales as $sale) {
            if ($sale->split_payments && is_array($sale->split_payments)) {
                // If split payments, only count cash portion
                foreach ($sale->split_payments as $payment) {
                    if (isset($payment['method']) && in_array(strtolower($payment['method']), ['cash', 'espèces'])) {
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
            'user_id' => 'required|exists:users,id',
        ]);

        $date = Carbon::parse($request->date);
        $user = User::findOrFail($request->user_id);

        // Get all shifts for this user
        $userShiftIds = Shift::where('user_id', $user->id)->pluck('id');

        // Find all sales created on this date from user's shifts
        $sales = Sale::whereIn('shift_id', $userShiftIds)
            ->whereDate('created_at', $date)
            ->with(['items.product', 'shift'])
            ->orderBy('created_at', 'desc')
            ->get();

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

}
