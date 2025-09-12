<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Store;
use App\Models\Account;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function index(Store $site)
    {
        $journals = $site->journals()->latest()->paginate(20);
        return view('journals.index', compact('site', 'journals'));
    }

    public function create(Store $site)
    {
        $accounts = Account::all();
        return view('journals.create', compact('site', 'accounts'));
    }

    public function store(Request $request, Store $site)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
        ]);

        $site->journals()->create($data);

        return redirect()->route('stores.journals.index', $site)->with('success', 'Transaction ajoutée avec succès.');
    }

    public function show(Store $site, Journal $journal)
    {
        return view('journals.show', compact('site', 'journal'));
    }

    public function destroy(Store $site, Journal $journal)
    {
        $journal->delete();
        return redirect()->route('stores.journals.index', $site)->with('success', 'Transaction supprimée.');
    }
}
