<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Site;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Site $site)
    {
        $accounts = $site->accounts()->get();
        return view('accounts.index', compact('site', 'accounts'));
    }

    public function create(Site $site)
    {
        return view('accounts.create', compact('site'));
    }

    public function store(Request $request, Site $site)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'balance' => 'required|numeric',
        ]);

        $site->accounts()->create($data);

        return redirect()->route('stores.accounts.index', $site)->with('success', __('messages.account.created'));
    }

    public function edit(Site $site, Account $account)
    {
        return view('accounts.edit', compact('site', 'account'));
    }

    public function update(Request $request, Site $site, Account $account)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'balance' => 'required|numeric',
        ]);

        $account->update($data);

        return redirect()->route('stores.accounts.index', $site)->with('success', __('messages.account.updated'));
    }

    public function destroy(Site $site, Account $account)
    {
        $account->delete();
        return redirect()->route('stores.accounts.index', $site)->with('success', __('messages.account.deleted'));
    }
}
