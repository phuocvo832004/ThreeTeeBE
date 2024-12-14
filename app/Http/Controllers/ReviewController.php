<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $productId = $request->input('product_id');
        $orderId = $request->input('order_id');
        $reviews = Review::with(['user', 'product', 'order'])
                          ->when($productId, function ($query, $productId) {
                              return $query->where('product_id', $productId);
                          })
                          ->when($orderId, function ($query, $orderId) {
                              return $query->where('order_id', $orderId);
                          })
                          ->paginate();
    
        return ReviewResource::collection($reviews);
    }
    

    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:users,id',
            'score' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:255',
        ])->validate();
    
        $order = Order::find($request->order_id);
        if ($order && $order->status !== 'success') {
            return response()->json(['message' => 'Order status must be success to create a review.'], 400);
        }
    
        $review = Review::create($validated);
    
        return (new ReviewResource($review))
            ->additional(['message' => 'Review created successfully'])
            ->response()
            ->setStatusCode(201);
    }
    

    public function show($id)
    {
        $review = Review::with(['user', 'product', 'order'])->find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        return new ReviewResource($review);
    }

    public function update(Request $request, $id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        $validated = Validator::make($request->all(), [
            'score' => 'integer|min:1|max:5',
            'comment' => 'nullable|string|max:255',
        ])->validate();

        $review->update($validated);

        return (new ReviewResource($review))
            ->additional(['message' => 'Review updated successfully']);
    }

    public function destroy($id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }
}
