<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Http\Request;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductDetailResource;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Sorts\MaxPriceSort;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $perPage = 9; 
        $products = QueryBuilder::for(
            Product::query()
                ->leftJoin('product_details', 'products.id', '=', 'product_details.product_id')
        )
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::exact('size', 'productDetails.size'),
                AllowedFilter::exact('category'),
                AllowedFilter::callback('price', function ($query, $value) {
                    if (is_array($value) && count($value) === 2) {
                        $query->whereBetween('product_details.price', [$value[0], $value[1]]);
                    }
                }),
            ])
            ->defaultSort('-created_at')
            ->allowedSorts([
                'rate',
                'sold',
                AllowedSort::custom('price', new MaxPriceSort()),
                AllowedSort::field('stock', 'product_details.stock'),
            ])
            ->select('products.*')
            ->groupBy('products.id')
            ->with(['productDetails', 'images'])
            ->paginate($perPage); 
        
        $totalPages = $products->lastPage(); 
    
        return response()->json([
            'data' => ProductResource::collection($products->items()), 
            'current_page' => $products->currentPage(), 
            'per_page' => $products->perPage(), 
            'total_pages' => $totalPages,
            'total_items' => $products->total(),
        ]);
    }

    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'name' => 'required|string|max:30',
            'description' => 'nullable|string',
            'sold' => 'integer|nullable',
            'rate' => 'numeric|nullable|min:0|max:5',
            'category' => 'nullable|string',
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
        $product = Product::with(['productDetails', 'images'])->find($id); 

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
            'name' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'sold' => 'nullable|integer',
            'rate' => 'nullable|numeric|min:0|max:5',
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
    public function getProductRevenue()
    {
        $productRevenue = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.id') 
            ->join('product_details', 'order_details.product_detail_id', '=', 'product_details.id')
            ->join('products', 'product_details.product_id', '=', 'products.id')
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(order_details.amount * product_details.price) as total_revenue')
            )
            ->where('orders.status', 'success') 
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_revenue', 'desc') 
            ->limit(10)
            ->get();

        $revenueArray = $productRevenue->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'total_revenue' => $item->total_revenue,
            ];
        });

        return response()->json($revenueArray);
    }

    public function getTop3ProductRevenuePerMonth()
    {
        $topProductsPerMonth = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('product_details', 'order_details.product_detail_id', '=', 'product_details.id')
            ->join('products', 'product_details.product_id', '=', 'products.id')
            ->select(
                DB::raw('YEAR(orders.created_at) as year'),
                DB::raw('MONTH(orders.created_at) as month'),
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(order_details.amount * product_details.price) as total_revenue')
            )
            ->where('orders.status', 'success') 
            ->groupBy(DB::raw('YEAR(orders.created_at)'), DB::raw('MONTH(orders.created_at)'), 'products.id', 'products.name')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->orderBy('total_revenue', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return $item->year . '-' . $item->month;
            })
            ->map(function ($monthlyProducts) {
                return $monthlyProducts->take(3); // Lấy top 3 sản phẩm cho mỗi tháng
            });

        return response()->json($topProductsPerMonth);
    }

}
