<?php

    namespace App\Http\Controllers;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use App\Models\Financial\GeneralInvoice;
    use App\Models\Financial\FinancialAccount;
    use Illuminate\Support\Facades\Storage;

    class GeneralInvoiceController extends Controller
    {
        public function index($storeId)
        {
            $invoices = GeneralInvoice::where('store_id', $storeId)
                ->with('account')
                ->latest()
                ->paginate(20);

            return view('financial.general_invoices.index', compact('invoices', 'storeId'));
        }

        public function create($storeId)
        {
            $accounts = FinancialAccount::where('store_id', $storeId)->get();
            return view('financial.general_invoices.create', compact('accounts', 'storeId'));
        }

        public function store(Request $request, $storeId)
        {
            $request->validate([
                'label' => 'required|string|max:255',
                'note' => 'nullable|string',
                'amount' => 'required|numeric|min:0',
                'due_date' => 'nullable|date',
                'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png',
                'account_id' => 'required|exists:financial_accounts,id',
            ]);

            $path = $request->file('attachment')->store("stores/$storeId/invoices");

            GeneralInvoice::create([
                'store_id' => $storeId,
                'label' => $request->label,
                'note' => $request->note,
                'amount' => $request->amount,
                'due_date' => $request->due_date,
                'status' => 'pending',
                'attachment' => $path,
                'account_id' => $request->account_id,
            ]);

            return redirect()->route('financial.general-invoices.index', $storeId)
                ->with('success', 'Invoice created successfully.');
        }

        public function show($storeId, GeneralInvoice $generalInvoice)
        {
            $this->authorizeInvoice($generalInvoice, $storeId);
            return view('financial.general_invoices.show', compact('generalInvoice', 'storeId'));
        }

        public function edit($storeId, GeneralInvoice $generalInvoice)
        {
            $this->authorizeInvoice($generalInvoice, $storeId);
            $accounts = FinancialAccount::where('store_id', $storeId)->get();
            return view('financial.general_invoices.edit', compact('generalInvoice', 'accounts', 'storeId'));
        }

        public function update(Request $request, $storeId, GeneralInvoice $generalInvoice)
        {
            $this->authorizeInvoice($generalInvoice, $storeId);

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
                $generalInvoice->attachment = $request->file('attachment')->store("stores/$storeId/invoices");
            }

            $generalInvoice->update([
                'label' => $request->label,
                'note' => $request->note,
                'amount' => $request->amount,
                'due_date' => $request->due_date,
                'status' => $request->status,
                'account_id' => $request->account_id,
            ]);

            return redirect()->route('financial.general-invoices.index', $storeId)
                ->with('success', 'Invoice updated successfully.');
        }

        public function destroy($storeId, GeneralInvoice $generalInvoice)
        {
            $this->authorizeInvoice($generalInvoice, $storeId);
            Storage::delete($generalInvoice->attachment);
            $generalInvoice->delete();

            return redirect()->route('financial.general-invoices.index', $storeId)
                ->with('success', 'Invoice deleted successfully.');
        }

        public function markAsPaid($storeId, GeneralInvoice $generalInvoice)
        {
            $this->authorizeInvoice($generalInvoice, $storeId);

            $generalInvoice->update([
                'status' => 'paid',
            ]);

            return redirect()->route('financial.general-invoices.index', $storeId)
                ->with('success', __('messages.invoice_marked_paid'));
        }

        protected function authorizeInvoice(GeneralInvoice $invoice, $storeId)
        {
            if ($invoice->store_id != $storeId) {
                abort(403);
            }
        }
    }
