<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ShiftController extends Controller
{
    // Vérifier si un shift est en cours
    public function currentShift($userId)
    {
        $shift = Shift::where('user_id', $userId)
                      ->whereNull('ended_at')
                      ->first();

        return response()->json($shift);
    }

    // Démarrer un shift
    public function start(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_amount' => 'required|numeric',
        ]);

        // Vérifier qu'il n'y a pas déjà un shift actif
        $existing = Shift::where('user_id', $request->user_id)
                        ->whereNull('ended_at')
                        ->first();
        if ($existing) {
            return response()->json(['error' => 'Shift déjà ouvert'], 422);
        }

        $user = User::findOrFail($request->user_id);

        $shift = Shift::create([
            'user_id' => $request->user_id,
            'store_id' => $user->store_id,
            'opening_cash' => $request->start_amount, // <== correction
            'started_at' => Carbon::now(),
        ]);

        return response()->json($shift);
    }

    // Terminer un shift
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
            return response()->json(['error' => 'Aucun shift en cours'], 422);
        }

        $shift->update([
            'closing_cash' => $request->end_amount, // <== correction
            'ended_at' => Carbon::now(),
        ]);

        return response()->json($shift);
    }

}
