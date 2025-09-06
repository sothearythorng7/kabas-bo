<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function attach(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $product->categories()->syncWithoutDetaching([$request->category_id]);

        return back()->with('success', 'Category added to product.');
    }

    public function detach(Product $product, Category $category)
    {
        $product->categories()->detach($category->id);

        return back()->with('success', 'Category removed from product.');
    }
}

    