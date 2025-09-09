<?php

namespace App\Http\Controllers;

use App\Models\ResellerInvoice;
use Illuminate\Http\Request;

class ResellerInvoiceController extends Controller
{
    public function index()
    {
        // Tous les statuts possibles
        $statuses = ['unpaid', 'partially_paid', 'paid'];

        // Regrouper les factures par statut
        $invoicesByStatus = [];
        foreach ($statuses as $status) {
            $invoicesByStatus[$status] = ResellerInvoice::with('reseller', 'resellerStockDelivery')
                ->where('status', $status)
                ->orderByDesc('created_at')
                ->paginate(10);
        }

        return view('reseller_invoices.index', compact('statuses', 'invoicesByStatus'));
    }
}
