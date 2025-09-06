<?php

namespace App\Http\Controllers;

use App\Models\WarehouseInvoice;
use App\Models\WarehouseInvoiceFile;
use App\Models\WarehouseInvoiceHistory;
use App\Enums\InvoiceType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class WarehouseInvoiceController extends Controller
{
    public function index()
    {
        $invoices = WarehouseInvoice::latest()->paginate(15);
        return view('warehouse-invoices.index', compact('invoices'));
    }

    public function create()
    {
        // On envoie éventuellement les enums pour le select
        $types = InvoiceType::cases();
        $paymentTypes = PaymentType::cases();

        return view('warehouse-invoices.create', compact('types', 'paymentTypes'));
    }

    public function edit(WarehouseInvoice $invoice)
    {
        $invoice->load(['files', 'histories.user']);
        return view('warehouse-invoices.edit', compact('invoice'));
    }

    public function update(Request $request, WarehouseInvoice $invoice)
    {
        $request->validate([
            'creditor_name'  => 'required|string|max:255',
            'description'    => 'nullable|string',
            'type'           => 'required|in:' . implode(',', InvoiceType::options()),
            'status'         => 'required|in:' . implode(',', InvoiceStatus::options()),
            'invoice_number' => 'nullable|string|max:255',
            'amount_usd'     => 'nullable|numeric|min:0',
            'amount_riel'    => 'nullable|numeric|min:0',
            'payment_number' => 'nullable|string|max:255',
            'payment_type'   => 'nullable|in:' . implode(',', PaymentType::options()),
            'files.*'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // Conversion automatique USD/Riel
        if ($request->amount_usd && !$request->amount_riel) {
            $request->merge(['amount_riel' => $request->amount_usd * 4000]);
        } elseif ($request->amount_riel && !$request->amount_usd) {
            $request->merge(['amount_usd' => $request->amount_riel / 4000]);
        }

        // Champs à suivre pour l'historique
        $fieldsToTrack = [
            'creditor_name', 'description', 'type', 'status',
            'invoice_number', 'amount_usd', 'amount_riel',
            'payment_number', 'payment_type',
        ];

        $changes = [];
        foreach ($fieldsToTrack as $field) {
            $oldValue = $invoice->{$field};
            $newValue = $request->get($field);

            // Si c’est un Enum, récupérer la valeur scalar
            if ($oldValue instanceof \BackedEnum) {
                $oldValue = $oldValue->value;
            }

            if ($oldValue != $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        // Mise à jour principale
        $invoice->update($request->only($fieldsToTrack));

        // Historique du changement (uniquement s’il y a des changements)
        if (!empty($changes)) {
            WarehouseInvoiceHistory::create([
                'warehouse_invoice_id' => $invoice->id,
                'user_id'              => Auth::id(),
                'changes'              => $changes,
            ]);
        }

        // Gestion des fichiers
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('warehouse_invoices');
                $invoice->files()->create(['path' => $path]);
            }
        }

        return redirect()->route('warehouse-invoices.edit', $invoice)
                        ->with('success', 'Facture mise à jour.');
    }

    public function uploadFiles(Request $request, WarehouseInvoice $invoice)
    {
        $request->validate([
            'files.*'  => 'required|file|mimes:jpg,png,pdf',
            'labels.*' => 'nullable|string|max:255',
        ]);

        foreach ($request->file('files') as $index => $file) {
            $label = $request->input('labels')[$index] ?? basename($file->getClientOriginalName());

            $path = $file->store('warehouse_invoices', 'public');

            $invoice->files()->create([
                'path'  => $path,
                'label' => $label,
            ]);
        }

        return back()->with('success', 'Fichier(s) ajouté(s) avec succès.');
    }

    public function deleteFile(WarehouseInvoice $invoice, $fileId)
    {
        $file = $invoice->files()->findOrFail($fileId);
        Storage::delete($file->path);
        $file->delete();

        return redirect()->back()->with('success', 'Fichier supprimé.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'creditor_name'   => 'required|string|max:255',
            'description'     => 'nullable|string',
            'type'            => 'required|in:' . implode(',', InvoiceType::options()),
            'invoice_number'  => 'nullable|string|max:255',
            'amount_usd'      => 'nullable|numeric|min:0',
            'amount_riel'     => 'nullable|numeric|min:0',
            'payment_number'  => 'nullable|string|max:255',
            'payment_type'    => 'nullable|in:' . implode(',', PaymentType::options()),
            'files.*'         => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // Conversion automatique USD/Riel
        if ($request->amount_usd && !$request->amount_riel) {
            $request->merge(['amount_riel' => $request->amount_usd * 4000]);
        } elseif ($request->amount_riel && !$request->amount_usd) {
            $request->merge(['amount_usd' => $request->amount_riel / 4000]);
        }

        $invoice = WarehouseInvoice::create([
            ...$request->only([
                'creditor_name', 'description', 'type',
                'invoice_number', 'amount_usd', 'amount_riel',
                'payment_number', 'payment_type'
            ]),
            'status' => InvoiceStatus::TO_PAY->value,
        ]);

        // Historique initial
        WarehouseInvoiceHistory::create([
            'warehouse_invoice_id' => $invoice->id,
            'user_id'              => Auth::id(),
            'changes'              => [], // Pas de changement initial
        ]);

        // Gestion des fichiers
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('warehouse_invoices');
                $invoice->files()->create(['path' => $path]);
            }
        }

        return redirect()->route('warehouse-invoices.edit', $invoice)
                        ->with('success', 'Facture créée avec succès.');
    }

    public function bills(Request $request)
    {
        $statuses = \App\Enums\InvoiceStatus::cases();

        // Pour chaque statut, on récupère les factures paginées
        $invoicesByStatus = [];
        foreach ($statuses as $status) {
            $invoicesByStatus[$status->value] = WarehouseInvoice::where('status', $status->value)
                ->latest()
                ->paginate(10, ['*'], $status->value); // pagination indépendante
        }

        return view('warehouse-invoices.bills', compact('statuses', 'invoicesByStatus'));
    }
}
