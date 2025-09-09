<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\ResellerContact;
use Illuminate\Http\Request;

class ResellerContactController extends Controller
{
    public function store(Request $request, Reseller $reseller)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
        ]);

        $reseller->contacts()->create($data);

        return back()->with('success', 'Contact added.');
    }

    public function destroy(Reseller $reseller, ResellerContact $contact)
    {
        $contact->delete();
        return back()->with('success', 'Contact deleted.');
    }
}
