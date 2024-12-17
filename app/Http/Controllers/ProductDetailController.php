<?php

namespace App\Http\Controllers;

use App\Models\ProductDetail;
use Illuminate\Http\Request;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductDetailCollection;
use Illuminate\Support\Facades\Auth;

class ProductDetailController extends Controller
{
    public function index()
    {
        $productDetails = ProductDetail::paginate();
        return new ProductDetailCollection($productDetails);
    }

    public function store(Request $request)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validate([
            'product_id' => 'required|exists:products,id',
            'stock' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'size' => 'required|string|max:10',
        ]);

        $productDetail = ProductDetail::create($validatedData);

        return new ProductDetailResource($productDetail);
    }

    public function show($id)
    {
        $productDetail = ProductDetail::find($id);

        if (!$productDetail) {
            return response()->json(['message' => 'ProductDetail not found'], 404);
        }

        return new ProductDetailResource($productDetail);
    }

    public function update(Request $request, $id)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $productDetail = ProductDetail::find($id);

        if (!$productDetail) {
            return response()->json(['message' => 'ProductDetail not found'], 404);
        }

        $validatedData = $request->validate([
            'product_id' => 'exists:products,id',
            'stock' => 'nullable|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'size' => 'nullable|string|max:10',
        ]);

        $productDetail->update($validatedData);

        return new ProductDetailResource($productDetail);
    }

    public function patchUpdate(Request $request, $id)
    {
        $productDetail = ProductDetail::find($id);

        if (!$productDetail) {
            return response()->json(['message' => 'ProductDetail not found'], 404);
        }

        $validatedData = $request->validate([
            'stock' => 'sometimes|integer|min:0',
            'price' => 'sometimes|numeric|min:0',
            'size' => 'sometimes|string|max:10',
        ]);

        $productDetail->fill($validatedData);
        $productDetail->save();

        return new ProductDetailResource($productDetail);
    }


    public function destroy($id)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $productDetail = ProductDetail::find($id);

        if (!$productDetail) {
            return response()->json(['message' => 'ProductDetail not found'], 404);
        }

        $productDetail->delete();

        return response()->json(['message' => 'ProductDetail deleted successfully']);
    }
}
