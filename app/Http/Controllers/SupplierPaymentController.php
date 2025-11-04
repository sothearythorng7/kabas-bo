<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupplierPaymentController extends Controller
{
    public function index(Store $site)
    {
        $payments = $site->supplierPayments()->latest()->paginate(20);
        return view('payments.index', compact('site', 'payments'));
    }

    public function create(Store $site)
    {
        return view('payments.create', compact('site'));
    }

    public function store(Request $request, Store $site)
    {
        $data = $request->validate([
            'supplier_name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric',
            'due_date' => 'nullable|date',
            'document' => 'nullable|file|mimes:pdf,jpg,png',
        ]);

        if ($request->hasFile('document')) {
            $data['document'] = $request->file('document')->store('supplier_payments');
        }

        $site->supplierPayments()->create($data);

        return redirect()->route('stores.payments.index', $site)->with('success', __('messages.supplier_payment.created'));
    }

    public function edit(Store $site, SupplierPayment $payment)
    {
        return view('payments.edit', compact('site', 'payment'));
    }

    public function update(Request $request, Store $site, SupplierPayment $payment)
    {
        $data = $request->validate([
            'supplier_name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric',
            'due_date' => 'nullable|date',
            'document' => 'nullable|file|mimes:pdf,jpg,png',
        ]);

        if ($request->hasFile('document')) {
            if ($payment->document) {
                Storage::delete($payment->document);
            }
            $data['document'] = $request->file('document')->store('supplier_payments');
        }

        $payment->update($data);

        return redirect()->route('stores.payments.index', $site)->with('success', __('messages.supplier_payment.updated'));
    }

    public function destroy(Store $site, SupplierPayment $payment)
    {
        if ($payment->document) {
            Storage::delete($payment->document);
        }
        $payment->delete();

        return redirect()->route('stores.payments.index', $site)->with('success', __('messages.supplier_payment.deleted'));
    }
}
