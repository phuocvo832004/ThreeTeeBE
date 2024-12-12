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
        // Tìm đơn hàng theo id
        $order = Order::find($order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        // Kiểm tra xem đơn hàng có thuộc về người dùng hiện tại không
        if ($order->user_id !== Auth::id()) {
            return response()->json(['message' => 'You are not authorized to view the details of this order.'], 403);
        }

        // Lấy danh sách chi tiết đơn hàng theo id của đơn hàng
        $orderDetails = OrderDetail::where('order_id', $order_id)->get();

        return OrderDetailsResource::collection($orderDetails);
    }

    public function store(StoreOrderDetailRequest $request)
    {
        $validated = $request->validated();

        $order = Order::find($order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->user_id !== Auth::id()) {
            return response()->json(['message' => 'You are not authorized to add details to this order.'], 403);
        }

        $orderDetail = OrderDetail::create($validated);

        return new OrderDetailsResource($orderDetail);
    }

    public function update(UpdateOrderDetailRequest $request, $order_id, $product_id)
    {
        // Xác thực dữ liệu từ request
        $validated = $request->validated();
    
        // Tìm đơn hàng theo order_id
        $order = Order::find($order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }
        
        if ($order->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    
        $orderDetail = OrderDetail::where('order_id', $order_id)
                                   ->where('product_id', $product_id)
                                   ->first();
    
        if (!$orderDetail) {
            return response()->json(['message' => 'Order detail not found'], 404);
        }

        if (isset($validated['amount'])) {
            $orderDetail->amount = $validated['amount'];
        }

        $orderDetail->updated_at = now();
    

        OrderDetail::where('order_id', $order_id)
                    ->where('product_id', $product_id)
                    ->update([
                        'amount' => $orderDetail->amount,
                        'updated_at' => $orderDetail->updated_at,
                    ]);
    
        return new OrderDetailsResource($orderDetail);
    }
    
}
