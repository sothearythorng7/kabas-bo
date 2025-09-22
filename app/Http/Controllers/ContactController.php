<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request, Supplier $supplier)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'telegram'   => 'nullable|string|max:100',
        ]);

        $supplier->contacts()->create($request->all());

        return redirect()->back()->with('success', 'Contact added successfully');
    }

    public function update(Request $request, Supplier $supplier, Contact $contact)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'telegram'   => 'nullable|string|max:100',
        ]);

        $contact->update($request->all());

        return redirect()->back()->with('success', 'Contact updated successfully');
    }

    public function destroy(Supplier $supplier, Contact $contact)
    {
        $contact->delete();

        return redirect()->back()->with('success', 'Contact deleted successfully');
    }
}
