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
    
        // Tìm chi tiết đơn hàng
        $orderDetail = OrderDetail::where('order_id', $order_id)
                                   ->where('product_detail_id', $product_detail_id)
                                   ->first();
    
        if (!$orderDetail) {
            return response()->json(['message' => 'Order detail not found'], 404);
        }
    
        // Cập nhật amount nếu có
        if (isset($validated['amount'])) {
            $orderDetail->amount = $validated['amount'];
        }
    
        $orderDetail->updated_at = now();
    
        // Thực hiện cập nhật thủ công
        OrderDetail::where('order_id', $order_id)
                    ->where('product_detail_id', $product_detail_id)
                    ->update([
                        'amount' => $orderDetail->amount,
                        'updated_at' => $orderDetail->updated_at,
                    ]);
    
        // Tải lại dữ liệu từ cơ sở dữ liệu và load mối quan hệ productDetail
        $orderDetail = OrderDetail::with('productDetail')
                                   ->where('order_id', $order_id)
                                   ->where('product_detail_id', $product_detail_id)
                                   ->first();
    
        // Trả về resource
        return new OrderDetailsResource($orderDetail);
    }
    
    public function destroy($order_id, $product_detail_id)
    {
        // Tìm đơn hàng theo order_id
        $order = Order::find($order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        // Tìm chi tiết đơn hàng
        $orderDetail = OrderDetail::where('order_id', $order_id)
                                ->where('product_detail_id', $product_detail_id)
                                ->first();

        if (!$orderDetail) {
            return response()->json(['message' => 'Order detail not found'], 404);
        }

        // Lưu thông tin productDetail trước khi xóa
        $productDetail = $orderDetail->productDetail;

        // Xóa thủ công với điều kiện đúng
        OrderDetail::where('order_id', $order_id)
                ->where('product_detail_id', $product_detail_id)
                ->delete();

        // Trả về phản hồi sau khi xóa, bao gồm thông tin productDetail
        return response()->json([
            'message' => 'Order detail deleted successfully.',
        ]);
    }


}
