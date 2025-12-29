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
        // Vérifier si des filtres sont appliqués
        $hasFilters = $request->hasAny(['date_from', 'date_to', 'user_id']) &&
                      ($request->date_from || $request->date_to || $request->user_id);

        // Si aucun filtre et aucun shift_id spécifique, afficher le shift en cours
        if (!$hasFilters && !$request->has('shift_id')) {
            $shift = Shift::where('store_id', $store->id)
                ->whereNull('ended_at')
                ->with(['user', 'shiftUsers.user'])
                ->first();

            // Si plusieurs shifts ouverts, ajouter une alerte
            $openShiftsCount = Shift::where('store_id', $store->id)
                ->whereNull('ended_at')
                ->count();

            if ($openShiftsCount > 1) {
                session()->flash('warning', __('messages.financial_shift.multiple_open_warning'));
            }

            $sales = $shift ? $shift->sales()->with('items.product')->get() : collect();
            $shiftStats = $this->calculateShiftStats($sales);
            $shifts = collect();
            $users = \App\Models\User::whereHas('shifts', function($q) use ($store) {
                $q->where('store_id', $store->id);
            })->get();

            return view('financial.shifts.index', compact('store', 'shift', 'sales', 'shiftStats', 'shifts', 'users'));
        }

        // Si shift_id est spécifié, afficher les détails de ce shift
        if ($request->has('shift_id') && $request->shift_id) {
            $shift = Shift::where('store_id', $store->id)
                ->where('id', $request->shift_id)
                ->with(['user', 'shiftUsers.user'])
                ->first();

            $sales = $shift ? $shift->sales()->with('items.product')->get() : collect();
            $shiftStats = $this->calculateShiftStats($sales);
            $shifts = collect();
            $users = \App\Models\User::whereHas('shifts', function($q) use ($store) {
                $q->where('store_id', $store->id);
            })->get();

            return view('financial.shifts.index', compact('store', 'shift', 'sales', 'shiftStats', 'shifts', 'users'));
        }

        // Si des filtres sont appliqués, afficher la liste des shifts
        $shiftsQuery = Shift::where('store_id', $store->id)
            ->with(['user', 'shiftUsers.user']);

        if ($request->date_from) {
            $shiftsQuery->whereDate('started_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $shiftsQuery->whereDate('started_at', '<=', $request->date_to);
        }
        if ($request->user_id) {
            $shiftsQuery->where('user_id', $request->user_id);
        }

        $shifts = $shiftsQuery->orderBy('started_at', 'desc')->get();
        $shift = null;
        $sales = collect();
        $shiftStats = $this->calculateShiftStats($sales);

        $users = \App\Models\User::whereHas('shifts', function($q) use ($store) {
            $q->where('store_id', $store->id);
        })->get();

        return view('financial.shifts.index', compact('store', 'shift', 'sales', 'shiftStats', 'shifts', 'users'));
    }

    private function calculateShiftStats($sales)
    {
        // Calculate total discounts with proper handling of scope and type
        $totalDiscounts = 0;

        foreach ($sales as $sale) {
            // Sale-level discounts
            foreach ($sale->discounts ?? [] as $d) {
                if ($d['type'] === 'amount') {
                    $totalDiscounts += $d['value'];
                } elseif ($d['type'] === 'percent') {
                    // For percent discounts on sale level, calculate based on items gross total
                    $itemsGross = $sale->items->sum(fn($i) => $i->price * $i->quantity);
                    $totalDiscounts += ($d['value'] / 100) * $itemsGross;
                }
            }

            // Item-level discounts
            foreach ($sale->items as $item) {
                foreach ($item->discounts ?? [] as $d) {
                    if ($d['type'] === 'amount') {
                        // Check scope: 'unit' means per unit, otherwise per line
                        if (($d['scope'] ?? 'line') === 'unit') {
                            $totalDiscounts += $d['value'] * $item->quantity;
                        } else {
                            $totalDiscounts += $d['value'];
                        }
                    } elseif ($d['type'] === 'percent') {
                        $totalDiscounts += ($d['value'] / 100) * $item->price * $item->quantity;
                    }
                }
            }
        }

        return [
            'number_of_sales' => $sales->count(),
            'total_sales' => $sales->sum('total'), // sale.total is now the correct net amount
            'total_items' => $sales->flatMap(fn($s) => $s->items)->sum('quantity'),
            'total_discounts' => $totalDiscounts,
        ];
    }

}
