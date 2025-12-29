<?php

namespace App\Http\Controllers;

use App\Models\Exchange;
use App\Models\Store;
use App\Models\User;
use App\Services\ExchangeService;
use Illuminate\Http\Request;

class ExchangeController extends Controller
{
    protected ExchangeService $exchangeService;

    public function __construct(ExchangeService $exchangeService)
    {
        $this->exchangeService = $exchangeService;
    }

    public function index(Request $request)
    {
        $query = Exchange::with(['store', 'user', 'originalSale', 'generatedVoucher', 'items']);

        // Filters
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('original_sale_id')) {
            $query->where('original_sale_id', $request->original_sale_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $exchanges = $query->orderBy('created_at', 'desc')->paginate(50);
        $stores = Store::all();
        $users = User::whereNotNull('store_id')->get();

        // Statistics
        $stats = $this->exchangeService->getStatistics(
            $request->filled('date_from') ? \Carbon\Carbon::parse($request->date_from) : null,
            $request->filled('date_to') ? \Carbon\Carbon::parse($request->date_to) : null,
            $request->store_id
        );

        return view('exchanges.index', compact('exchanges', 'stores', 'users', 'stats'));
    }

    public function show(Exchange $exchange)
    {
        $exchange->load([
            'store',
            'user',
            'originalSale.items.product',
            'items.product',
            'items.stockBatch',
            'generatedVoucher',
            'paymentVoucher',
            'financialTransaction',
            'newSale.items.product'
        ]);

        return view('exchanges.show', compact('exchange'));
    }
}
