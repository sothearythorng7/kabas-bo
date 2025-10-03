<?php

namespace App\Http\Controllers;

use App\Models\VariationType;
use Illuminate\Http\Request;


class VariationTypeController extends Controller
{
    public function index()
    {
        $types = VariationType::paginate(15);
        return view('variation_types.index', compact('types'));
    }

    public function create()
    {
        return view('variation_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:variation_types,name',
            'label' => 'required|string',
        ]);

        VariationType::create($request->only('name', 'label'));

        return redirect()->route('variation-types.index')
                         ->with('success', __('Type de déclinaison créé'));
    }

    public function edit(VariationType $variationType)
    {
        return view('variation_types.edit', compact('variationType'));
    }

    public function update(Request $request, VariationType $variationType)
    {
        $request->validate([
            'name' => 'required|string|unique:variation_types,name,' . $variationType->id,
            'label' => 'required|string',
        ]);

        $variationType->update($request->only('name', 'label'));

        return redirect()->route('variation-types.index')
                         ->with('success', __('Type de déclinaison mis à jour'));
    }

    public function destroy(VariationType $variationType)
    {
        $variationType->delete();
        return redirect()->route('variation-types.index')
                         ->with('success', __('Type de déclinaison supprimé'));
    }

    public function values($id)
    {
        $type = VariationType::with('values')->findOrFail($id);
        return response()->json($type->values->map(fn($v) => [
            'id' => $v->id,
            'value' => $v->value,
        ]));
    }
}
