<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Carbon\Carbon;

class StoreDashboardController extends Controller
{
    public function index(Store $site)
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Chiffre d'affaire du mois (income)
        $revenue = $site->journals()
            ->where('type', 'income')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Total des dépenses du mois (expense)
        $expensesFromJournals = $site->journals()
            ->where('type', 'expense')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $expensesFromExpenses = $site->expenses()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $totalExpenses = $expensesFromJournals + $expensesFromExpenses;

        // Solde net
        $net = $revenue - $totalExpenses;

        return view('stores.dashboard', compact('site', 'revenue', 'totalExpenses', 'net'));
    }
}
