<?php

namespace App\Http\Controllers\Financial;

use App\Models\Store;
use App\Models\Shift;
use App\Models\Sale;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FinancialShiftController extends Controller
{
    public function index(Store $store)
    {
        // Récupérer le shift en cours
        $shift = Shift::where('store_id', $store->id)
            ->whereNull('ended_at')
            ->first();

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

        return view('financial.shifts.index', compact('store', 'shift', 'sales', 'shiftStats'));
    }

}
