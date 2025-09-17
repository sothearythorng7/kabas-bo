<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneralInvoice;
use App\Models\FinancialAccount;
use App\Models\Store;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class GeneralInvoiceController extends Controller
{
    public function index(Store $store, Request $request)
    {
        $statusFilter = request('status') === 'paid' ? 'paid' : 'pending';

        // Factures générales filtrées par statut
        $generalInvoices = GeneralInvoice::where('store_id', $store->id)
            ->with('account')
            ->when($statusFilter === 'paid', fn($q) => $q->where('status', 'paid'))
            ->when($statusFilter === 'pending', fn($q) => $q->where('status', 'pending'))
            ->get()
            ->each(fn($invoice) => $invoice->type = 'general'); // ajout d'un attribut type

        // Factures fournisseurs filtrées par statut
        $supplierInvoices = \App\Models\SupplierOrder::with('supplier')
            ->where('status', 'received')
            ->when($statusFilter === 'paid', fn($q) => $q->where('is_paid', true))
            ->when($statusFilter === 'pending', fn($q) => $q->where('is_paid', false))
            ->get()
            ->each(fn($order) => $order->type = 'supplier'); // ajout d'un attribut type

        // Fusionner et trier par date décroissante
        $allInvoices = $generalInvoices->merge($supplierInvoices)->sortByDesc('created_at')->values();

        // Pagination manuelle
        $perPage = 10;
        $page = Paginator::resolveCurrentPage('page');
        $invoices = new LengthAwarePaginator(
            $allInvoices->forPage($page, $perPage),
            $allInvoices->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );

        $accounts = FinancialAccount::all();

        return view('financial.general-invoices.index', compact('invoices', 'store', 'statusFilter', 'accounts'));
    }


    public function create(Store $store)
    {
        $accounts = FinancialAccount::all();
        return view('financial.general-invoices.create', compact('accounts', 'store'));
    }

    public function store(Store $store, Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'note' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'status' => 'required|in:pending,paid',
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png',
            'account_id' => 'required|exists:financial_accounts,id',
        ]);

        $path = $request->file('attachment')->store("stores/{$store->id}/invoices");

        GeneralInvoice::create([
            'store_id' => $store->id,
            'label' => $request->label,
            'note' => $request->note,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'status' => $request->status,
            'attachment' => $path,
            'account_id' => $request->account_id,
        ]);

        return redirect()->route('financial.general-invoices.index', $store->id)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Store $store, GeneralInvoice $generalInvoice)
    {
        $this->authorizeInvoice($generalInvoice, $store->id);
        return view('financial.general-invoices.show', compact('generalInvoice', 'store'));
    }

    public function edit(Store $store, GeneralInvoice $generalInvoice)
    {
        $this->authorizeInvoice($generalInvoice, $store->id);
        $accounts = FinancialAccount::all();
        return view('financial.general-invoices.edit', compact('generalInvoice', 'accounts', 'store'));
    }

    public function update(Store $store, Request $request, GeneralInvoice $generalInvoice)
    {
        $this->authorizeInvoice($generalInvoice, $store->id);

        $request->validate([
            'label' => 'required|string|max:255',
            'note' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'status' => 'required|in:pending,paid',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'account_id' => 'required|exists:financial_accounts,id',
        ]);

        if ($request->hasFile('attachment')) {
            Storage::delete($generalInvoice->attachment);
            $generalInvoice->attachment = $request->file('attachment')->store("stores/{$store->id}/invoices");
        }

        $generalInvoice->update([
            'label' => $request->label,
            'note' => $request->note,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'status' => $request->status,
            'account_id' => $request->account_id,
        ]);

        return redirect()->route('financial.general-invoices.index', $store->id)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Store $store, GeneralInvoice $generalInvoice)
    {
        $this->authorizeInvoice($generalInvoice, $store->id);
        Storage::delete($generalInvoice->attachment);
        $generalInvoice->delete();

        return redirect()->route('financial.general-invoices.index', $store->id)
            ->with('success', 'Invoice deleted successfully.');
    }

    protected function authorizeInvoice(GeneralInvoice $invoice, $storeId)
    {
        if ($invoice->store_id != $storeId) {
            abort(403);
        }
    }
}
