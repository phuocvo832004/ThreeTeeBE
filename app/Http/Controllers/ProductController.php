<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {

        $products = QueryBuilder::for(Product::class)
        ->allowedFilters('name' )
        ->defaultSort('-create')
        ->allowedSorts('price', 'rate')
        ->paginate();

        return new ProductCollection($products);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Authentication required'], 401);
        }
    
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $validatedData = $request->validate([
            'name' => 'required|string|max:30',
            'amount' => 'required|integer',
            'description' => 'nullable|string',
            'create' => 'required|date',
            'sold' => 'integer|nullable',
            'price' => 'required|integer',
            'size' => 'required|integer',
            'rate' => 'numeric|nullable|min:0|max:5',
        ]);
    
        $product = Product::withoutGlobalScopes()->create($validatedData);
    
        return new ProductResource($product);
    }
    

    public function show($id)
    {
        $product = Product::find($id);
    
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    
        return new ProductResource($product); 
    }
    

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
    
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    
        $validatedData = $request->validate([
            'name' => 'string|max:30',
            'amount' => 'integer',
            'description' => 'nullable|string',
            'create' => 'date',
            'sold' => 'integer|nullable',
            'price' => 'integer',
            'size' => 'integer',
            'rate' => 'numeric|nullable|min:0|max:5',
        ]);
    
        $product->update($validatedData);
    
        return new ProductResource($product); 
    }
    

    public function destroy($id)
    {
        $product = Product::find($id);
    
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    
        $product->delete();
    
        return response()->json(['message' => 'Product deleted successfully']);
    }
    

    public function patchUpdateProduct(Request $request, $id)
    {
        $product = Product::find($id);
    
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:30',
            'amount' => 'nullable|integer',
            'description' => 'nullable|string',
            'create' => 'nullable|date',
            'sold' => 'nullable|integer',
            'price' => 'nullable|integer',
            'size' => 'nullable|integer',
            'rate' => 'nullable|numeric|min:0|max:5',
        ]);
    
        $product->update($validatedData);
    
        return new ProductResource($product); 
    }
    
}
