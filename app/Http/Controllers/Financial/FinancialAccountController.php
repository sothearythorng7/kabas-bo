<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Enums\FinancialAccountType;

class FinancialAccountController extends Controller
{
    public function index(Store $store)
    {
        $accounts = FinancialAccount::with('parent')->paginate(20);
        return view('financial.accounts.index', compact('accounts', 'store'));
    }

    public function create(Store $store)
    {
        $parents = FinancialAccount::all(); // tous les comptes possibles comme parent
        return view('financial.accounts.create', compact('store', 'parents'));
    }

    public function store(Store $store, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:financial_accounts,code',
            'type' => 'required|string|in:' . implode(',', FinancialAccountType::options()),
            'parent_id' => 'nullable|exists:financial_accounts,id',
        ]);

        FinancialAccount::create([
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'parent_id' => $request->parent_id,
        ]);

        return redirect()->route('financial.accounts.index', $store->id)
            ->with('success', 'Compte créé.');
    }

    public function edit(Store $store, FinancialAccount $account)
    {
        $parents = FinancialAccount::where('id', '!=', $account->id)->get(); // éviter de se choisir soi-même comme parent
        return view('financial.accounts.edit', compact('account', 'store', 'parents'));
    }

    public function update(Store $store, Request $request, FinancialAccount $account)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:financial_accounts,code,' . $account->id,
            'type' => 'required|string|in:' . implode(',', FinancialAccountType::options()),
            'parent_id' => 'nullable|exists:financial_accounts,id',
        ]);

        $account->update([
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'parent_id' => $request->parent_id,
        ]);

        return redirect()->route('financial.accounts.index', $store->id)
            ->with('success', 'Compte mis à jour.');
    }

    public function destroy(Store $store, FinancialAccount $account)
    {
        $account->delete();

        return redirect()->route('financial.accounts.index', $store->id)
            ->with('success', 'Compte supprimé.');
    }
}
