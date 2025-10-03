<?php

namespace App\Http\Controllers;

use App\Models\VariationValue;
use App\Models\VariationType;
use Illuminate\Http\Request;

class VariationValueController extends Controller
{
    public function index()
    {
        $values = VariationValue::with('type')->paginate(15);
        return view('variation_values.index', compact('values'));
    }

    public function create()
    {
        $types = VariationType::all();
        return view('variation_values.create', compact('types'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'variation_type_id' => 'required|exists:variation_types,id',
            'value' => 'required|string',
        ]);

        VariationValue::create($request->only('variation_type_id', 'value'));

        return redirect()->route('variation-values.index')
                         ->with('success', __('Valeur de déclinaison créée'));
    }

    public function edit(VariationValue $variationValue)
    {
        $types = VariationType::all();
        return view('variation_values.edit', compact('variationValue', 'types'));
    }

    public function update(Request $request, VariationValue $variationValue)
    {
        $request->validate([
            'variation_type_id' => 'required|exists:variation_types,id',
            'value' => 'required|string',
        ]);

        $variationValue->update($request->only('variation_type_id', 'value'));

        return redirect()->route('variation-values.index')
                         ->with('success', __('Valeur de déclinaison mise à jour'));
    }

    public function destroy(VariationValue $variationValue)
    {
        $variationValue->delete();
        return redirect()->route('variation-values.index')
                         ->with('success', __('Valeur de déclinaison supprimée'));
    }
}
