<?php

namespace App\Http\Controllers;

use App\Models\WarehouseInvoice;
use App\Enums\InvoiceStatus;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Factures à payer
        $invoicesToPay = WarehouseInvoice::where('status', InvoiceStatus::TO_PAY->value);

        $invoicesToPayCount = $invoicesToPay->count();
        $invoicesToPayTotal = $invoicesToPay->sum('amount_usd');

        return view('dashboard', compact('invoicesToPayCount', 'invoicesToPayTotal'));
    }
}