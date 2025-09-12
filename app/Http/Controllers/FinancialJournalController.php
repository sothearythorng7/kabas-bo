<?php

namespace App\Http\Controllers;

use App\Models\FinancialJournal;
use App\Models\Store;
use Illuminate\Http\Request;

class FinancialJournalController extends Controller
{
    public function index(Store $site)
    {
        $journals = $site->financialJournals()->with('ledgerEntries.account')->get();
        return view('journals.index', compact('site', 'journals'));
    }

    public function create(Store $site)
    {
        return view('journals.create', compact('site'));
    }

    public function store(Request $request, Store $site)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        $site->financialJournals()->create($data);

        return redirect()->route('stores.journals.index', $site)->with('success', 'Journal créé');
    }

    public function show(Store $site, FinancialJournal $journal)
    {
        $journal->load('ledgerEntries.account');
        return view('journals.show', compact('site', 'journal'));
    }
}
