<?php

namespace App\Http\Controllers\Financial;

use App\Models\Store;
use App\Models\Shift;
use App\Models\Sale;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FinancialShiftController extends Controller
{
    public function index(Store $store, Request $request)
    {
        // Récupérer le shift en cours ou filtré
        $shiftQuery = Shift::where('store_id', $store->id)
            ->with('user');

        // Filtre par date
        if ($request->has('date_from') && $request->date_from) {
            $shiftQuery->whereDate('started_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $shiftQuery->whereDate('started_at', '<=', $request->date_to);
        }

        // Filtre par utilisateur
        if ($request->has('user_id') && $request->user_id) {
            $shiftQuery->where('user_id', $request->user_id);
        }

        // Si aucun filtre, récupérer uniquement le shift en cours
        if (!$request->hasAny(['date_from', 'date_to', 'user_id'])) {
            $shiftQuery->whereNull('ended_at');
        }

        $shift = $shiftQuery->first();

        // Si plusieurs shifts ouverts, ajouter une alerte
        $openShiftsCount = Shift::where('store_id', $store->id)
            ->whereNull('ended_at')
            ->count();

        if ($openShiftsCount > 1) {
            session()->flash('warning', __('messages.financial_shift.multiple_open_warning'));
        }

        $sales = $shift ? $shift->sales()->with('items.product')->get() : collect();

$shiftStats = [
    'number_of_sales' => $sales->count(),
    'total_sales' => $sales->sum('total'),
    'total_items' => $sales->flatMap(fn($s) => $s->items)->sum('quantity'),
    'total_discounts' => $sales->sum(function($s) {
        return collect($s->discounts)->sum(fn($d) => $d['amount'] ?? 0);
    }) + $sales->flatMap(fn($s) => $s->items)->sum(function($i) {
        return collect($i->discounts)->sum(fn($d) => $d['amount'] ?? 0);
    }),
];

        // Récupérer tous les utilisateurs qui ont des shifts pour les filtres
        $users = \App\Models\User::whereHas('shifts', function($q) use ($store) {
            $q->where('store_id', $store->id);
        })->get();

        return view('financial.shifts.index', compact('store', 'shift', 'sales', 'shiftStats', 'users'));
    }

}
