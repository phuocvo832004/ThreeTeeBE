<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderDetailRequest;
use App\Http\Requests\UpdateOrderDetailRequest;
use App\Http\Resources\OrderDetailsResource;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderDetailController extends Controller
{
    public function index($order_id)
    {
        $order = Order::find($order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->user_id !== Auth::id()) {
            return response()->json(['message' => 'You are not authorized to view the details of this order.'], 403);
        }

        $orderDetails = OrderDetail::with('productDetail')
                                    ->where('order_id', $order_id)
                                    ->get();


        return OrderDetailsResource::collection($orderDetails);
    }

    public function store(StoreOrderDetailRequest $request)
    {
        $validated = $request->validated();

        $order = Order::find($validated['order_id']);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->user_id !== Auth::id()) {
            return response()->json(['message' => 'You are not authorized to add details to this order.'], 403);
        }

        try {
            $orderDetail = OrderDetail::create($validated);

            $orderDetail->load('productDetail');

            return new OrderDetailsResource($orderDetail);

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '45000') {
                return response()->json([
                    'message' => 'Not enough stock for the product detail!',
                ], 400); 
            }

            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 500); 
        }
    }


    public function update(UpdateOrderDetailRequest $request, $order_id, $product_detail_id)
    {
        $validated = $request->validated();
    
        $order = Order::find($order_id);
    
        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }
    
        if ($order->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    
        $orderDetail = OrderDetail::where('order_id', $order_id)
                                   ->where('product_detail_id', $product_detail_id)
                                   ->first();
    
        if (!$orderDetail) {
            return response()->json(['message' => 'Order detail not found'], 404);
        }
    
        if (isset($validated['amount'])) {
            $orderDetail->amount = $validated['amount'];
        }
    
        $orderDetail->updated_at = now();
    
        OrderDetail::where('order_id', $order_id)
                    ->where('product_detail_id', $product_detail_id)
                    ->update([
                        'amount' => $orderDetail->amount,
                        'updated_at' => $orderDetail->updated_at,
                    ]);
    
        $orderDetail = OrderDetail::with('productDetail')
                                   ->where('order_id', $order_id)
                                   ->where('product_detail_id', $product_detail_id)
                                   ->first();
    
        return new OrderDetailsResource($orderDetail);
    }
    
    public function destroy($order_id, $product_detail_id)
    {
        $order = Order::find($order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $orderDetail = OrderDetail::where('order_id', $order_id)
                                ->where('product_detail_id', $product_detail_id)
                                ->first();

        if (!$orderDetail) {
            return response()->json(['message' => 'Order detail not found'], 404);
        }

        $productDetail = $orderDetail->productDetail;

        OrderDetail::where('order_id', $order_id)
                ->where('product_detail_id', $product_detail_id)
                ->delete();

        return response()->json([
            'message' => 'Order detail deleted successfully.',
        ]);
    }

    public function orderDetail($order_id)
    {
        $order = Order::find($order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }



        $orderDetails = OrderDetail::with('productDetail')
                                    ->where('order_id', $order_id)
                                    ->get();


        return OrderDetailsResource::collection($orderDetails);
    }
}
