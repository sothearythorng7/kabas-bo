<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialJournal;
use App\Models\Store;
use Illuminate\Http\Request;

class FinancialJournalController extends Controller
{
    public function index(Store $store)
    {
        $journals = FinancialJournal::where('store_id', $store->id)
            ->latest()
            ->paginate(20);

        return view('financial.journals.index', compact('store', 'journals'));
    }

    public function show(Store $store, FinancialJournal $journal)
    {
        $this->authorize('view', $journal); // facultatif si tu gères des permissions

        return view('financial.journals.show', compact('store', 'journal'));
    }
}
