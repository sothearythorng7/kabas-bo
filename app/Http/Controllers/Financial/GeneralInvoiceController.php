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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
    
class GeneralInvoiceController extends Controller
{
    public function index(Store $store, Request $request)
    {
        $statusFilter = request('status') === 'paid' ? 'paid' : 'pending';

        // Factures générales filtrées par statut
        $generalInvoices = GeneralInvoice::where('store_id', $store->id)
            ->with(['account', 'category'])
            ->when($statusFilter === 'paid', fn($q) => $q->where('status', 'paid'))
            ->when($statusFilter === 'pending', fn($q) => $q->where('status', 'pending'))
            // Filtre par catégorie
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
            // Filtre par date - avant
            ->when($request->filled('date_before'), fn($q) => $q->whereDate('due_date', '<=', $request->date_before))
            // Filtre par date - après
            ->when($request->filled('date_after'), fn($q) => $q->whereDate('due_date', '>=', $request->date_after))
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
        $categories = \App\Models\InvoiceCategory::orderBy('name')->get();

        return view('financial.general-invoices.index', compact('invoices', 'store', 'statusFilter', 'accounts', 'categories'));
    }

    public function export(Store $store, Request $request)
    {
        $statusFilter = request('status') === 'paid' ? 'paid' : 'pending';
        $locale = app()->getLocale();

        // Récupérer les factures avec les mêmes filtres que l'index
        $invoices = GeneralInvoice::where('store_id', $store->id)
            ->with(['account', 'category'])
            ->when($statusFilter === 'paid', fn($q) => $q->where('status', 'paid'))
            ->when($statusFilter === 'pending', fn($q) => $q->where('status', 'pending'))
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('date_before'), fn($q) => $q->whereDate('due_date', '<=', $request->date_before))
            ->when($request->filled('date_after'), fn($q) => $q->whereDate('due_date', '>=', $request->date_after))
            ->orderBy('due_date')
            ->get();

        // Créer le fichier Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(__('messages.General invoices'));

        // En-têtes (traduits)
        $headers = [
            'A1' => __('messages.Libellé'),
            'B1' => __('messages.Catégorie'),
            'C1' => __('messages.Compte'),
            'D1' => __('messages.Montant'),
            'E1' => __('messages.Due to'),
            'F1' => __('messages.Date de paiement'),
            'G1' => __('messages.Statut'),
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD9E1F2');
            $sheet->getStyle($cell)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Auto-size des colonnes
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Remplir les données
        $row = 2;
        foreach ($invoices as $invoice) {
            $sheet->setCellValue("A{$row}", $invoice->label);
            $sheet->setCellValue("B{$row}", $invoice->category ? $invoice->category->name : '-');
            $sheet->setCellValue("C{$row}", $invoice->account ? $invoice->account->name : '-');
            $sheet->setCellValue("D{$row}", number_format($invoice->amount, 2) . ' $');
            $sheet->setCellValue("E{$row}", $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-');
            $sheet->setCellValue("F{$row}", $invoice->payment_date ? $invoice->payment_date->format('d/m/Y') : '-');

            // Statut traduit
            $status = $invoice->status === 'paid' ? __('messages.Payée') : __('messages.À payer');
            $sheet->setCellValue("G{$row}", $status);

            $row++;
        }

        // Nom du fichier avec statut et date
        $statusLabel = $statusFilter === 'paid' ? __('messages.paid') : __('messages.to_pay');
        $filename = 'factures_' . $statusLabel . '_' . $store->name . '_' . date('Y-m-d_His') . '.xlsx';
        $filename = str_replace(' ', '_', $filename);

        // Sauvegarder et télécharger
        $tempFile = tempnam(sys_get_temp_dir(), 'invoices_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function create(Store $store)
    {
        $accounts = FinancialAccount::all();
        $categories = \App\Models\InvoiceCategory::orderBy('name')->get();
        return view('financial.general-invoices.create', compact('accounts', 'categories', 'store'));
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
            'category_id' => 'nullable|exists:invoice_categories,id',
        ]);

        $path = $request->file('attachment')->store("stores/{$store->id}/invoices", 'public');

        GeneralInvoice::create([
            'store_id' => $store->id,
            'label' => $request->label,
            'note' => $request->note,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'status' => $request->status,
            'attachment' => $path,
            'account_id' => $request->account_id,
            'category_id' => $request->category_id,
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
        $categories = \App\Models\InvoiceCategory::orderBy('name')->get();
        return view('financial.general-invoices.edit', compact('generalInvoice', 'accounts', 'categories', 'store'));
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
            'category_id' => 'nullable|exists:invoice_categories,id',
        ]);

        if ($request->hasFile('attachment')) {
            Storage::disk('public')->delete($generalInvoice->attachment);
            $generalInvoice->attachment = $request->file('attachment')->store("stores/{$store->id}/invoices", 'public');
        }

        $generalInvoice->update([
            'label' => $request->label,
            'note' => $request->note,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'status' => $request->status,
            'account_id' => $request->account_id,
            'category_id' => $request->category_id,
        ]);

        return redirect()->route('financial.general-invoices.index', $store->id)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Store $store, GeneralInvoice $generalInvoice)
    {
        $this->authorizeInvoice($generalInvoice, $store->id);
        Storage::disk('public')->delete($generalInvoice->attachment);
        $generalInvoice->delete();

        return redirect()->route('financial.general-invoices.index', $store->id)
            ->with('success', 'Invoice deleted successfully.');
    }

    public function markAsPaid(Store $store, GeneralInvoice $generalInvoice)
    {
        $this->authorizeInvoice($generalInvoice, $store->id);

        $generalInvoice->update([
            'status' => 'paid',
            'payment_date' => now()->toDateString(),
        ]);

        return redirect()->route('financial.general-invoices.index', $store->id)
            ->with('success', __('messages.invoice_marked_paid'));
    }

    public function downloadAttachment(Store $store, GeneralInvoice $generalInvoice)
    {
        $this->authorizeInvoice($generalInvoice, $store->id);

        // Try public disk first (new files)
        if (Storage::disk('public')->exists($generalInvoice->attachment)) {
            return Storage::disk('public')->download($generalInvoice->attachment);
        }

        // Fallback to default disk (old files)
        if (Storage::exists($generalInvoice->attachment)) {
            return Storage::download($generalInvoice->attachment);
        }

        abort(404, 'Attachment not found');
    }

    protected function authorizeInvoice(GeneralInvoice $invoice, $storeId)
    {
        if ($invoice->store_id != $storeId) {
            abort(403);
        }
    }
}
