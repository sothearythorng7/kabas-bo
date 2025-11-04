<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialPaymentMethod;
use App\Models\Store;
use Illuminate\Http\Request;

class FinancialPaymentMethodController extends Controller
{
    public function index(Store $store)
    {
        $methods = FinancialPaymentMethod::paginate(20);
        return view('financial.payment_methods.index', compact('methods', 'store'));
    }

    public function create(Store $store)
    {
        return view('financial.payment_methods.create', compact('store'));
    }

    public function store(Store $store, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:financial_payment_methods,code',
        ]);

        FinancialPaymentMethod::create([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return redirect()->route('financial.payment-methods.index', $store->id)
            ->with('success', __('messages.financial_payment_method.created'));
    }

    public function edit(Store $store, FinancialPaymentMethod $paymentMethod)
    {
        return view('financial.payment_methods.edit', compact('paymentMethod', 'store'));
    }

    public function update(Store $store, Request $request, FinancialPaymentMethod $paymentMethod)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:financial_payment_methods,code,' . $paymentMethod->id,
        ]);

        $paymentMethod->update([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return redirect()->route('financial.payment-methods.index', $store->id)
            ->with('success', __('messages.financial_payment_method.updated'));
    }

    public function destroy(Store $store, FinancialPaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return redirect()->route('financial.payment-methods.index', $store->id)
            ->with('success', __('messages.financial_payment_method.deleted'));
    }
}
