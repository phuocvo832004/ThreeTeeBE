<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Http\Request;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductDetailResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {
        $products = QueryBuilder::for(Product::class)
            ->allowedFilters([AllowedFilter::partial('name')])
            ->defaultSort('-created_at')
            ->allowedSorts('rate', 'sold')
            ->paginate();
    
        return new ProductCollection($products);
    }

    public function indexUnique()
    {
        $products = QueryBuilder::for(Product::with('productDetails'))
            ->allowedFilters([AllowedFilter::partial('name')])
            ->defaultSort('-created_at')
            ->allowedSorts('rate', 'sold')
            ->get()
            ->unique('name')
            ->values();

        $perPage = 10; 
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $paginatedProducts = new LengthAwarePaginator(
            $products->forPage($currentPage, $perPage),
            $products->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return new ProductCollection($paginatedProducts);
    }

    public function store(Request $request)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:30',
            'description' => 'nullable|string',
            'sold' => 'integer|nullable',
            'rate' => 'numeric|nullable|min:0|max:5',
            'product_details' => 'array',
            'product_details.*.detail_name' => 'required|string|max:50',
            'product_details.*.detail_value' => 'required|string|max:255',
        ]);

        $product = Product::create($validatedData);

        if (isset($validatedData['product_details'])) {
            foreach ($validatedData['product_details'] as $detail) {
                $product->productDetails()->create($detail);
            }
        }

        return new ProductResource($product);
    }

    public function show($id)
    {
        $product = Product::with('productDetails')->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return new ProductResource($product);
    }

    public function update(Request $request, $id)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'sold' => 'nullable|integer',
            'rate' => 'nullable|numeric|min:0|max:5',
        ]);

        $product->update($validatedData);

        return new ProductResource($product);
    }

    public function destroy($id)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function addProductDetail(Request $request, $productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'detail_name' => 'required|string|max:50',
            'detail_value' => 'required|string|max:255',
        ]);

        $productDetail = $product->productDetails()->create($validated);

        return response()->json([
            'message' => 'Product detail added successfully.',
            'product_detail' => new ProductDetailResource($productDetail),
        ], 201);
    }

    public function updateProductDetail(Request $request, $productId, $detailId)
    {
        $productDetail = ProductDetail::where('product_id', $productId)->find($detailId);

        if (!$productDetail) {
            return response()->json(['message' => 'Product detail not found'], 404);
        }

        $validated = $request->validate([
            'detail_name' => 'nullable|string|max:50',
            'detail_value' => 'nullable|string|max:255',
        ]);

        $productDetail->update($validated);

        return response()->json([
            'message' => 'Product detail updated successfully.',
            'product_detail' => new ProductDetailResource($productDetail),
        ]);
    }

    public function deleteProductDetail($productId, $detailId)
    {
        $productDetail = ProductDetail::where('product_id', $productId)->find($detailId);

        if (!$productDetail) {
            return response()->json(['message' => 'Product detail not found'], 404);
        }

        $productDetail->delete();

        return response()->json(['message' => 'Product detail deleted successfully.']);
    }
}
