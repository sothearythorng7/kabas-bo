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
        ]);

        $shift = Shift::where('user_id', $request->user_id)
                    ->whereNull('ended_at')
                    ->first();

        if (!$shift) {
            return response()->json(['error' => 'No shift in progress'], 422);
        }

        $shift->update([
            'closing_cash' => $request->end_amount,
            'ended_at' => Carbon::now(),
        ]);

        return response()->json($shift);
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
