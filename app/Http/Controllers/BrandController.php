<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::paginate(20);
        return view('brands.index', compact('brands'));
    }

    public function create()
    {
        return view('brands.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        Brand::create($request->all());
        return redirect()->route('brands.index')->with('success', __('messages.flash.brand_created'));
    }

    public function edit(Brand $brand)
    {
        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $brand->update($request->all());
        return redirect()->route('brands.index')->with('success', __('messages.flash.brand_updated'));
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        return redirect()->route('brands.index')->with('success', __('messages.flash.brand_deleted'));
    }
}
