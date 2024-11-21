<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // Hiển thị danh sách sản phẩm
    public function index(Request $request)
    {
        return new OrderCollection(Order::all());
    }

    // Hiển thị chi tiết sản phẩm
    public function show(Request $request, Order $Order)
    {
        return new OrderResource($Order);
    }

    // Thêm mới sản phẩm
    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();

        $Order = Order::create($validated);

        return new OrderResource($Order);
    }

    // Cập nhật thông tin sản phẩm
    public function update(UpdateOrderRequest $request, Order $Order)
    {
        $validated = $request->validated();

        $Order->update($validated);

        return new OrderResource($Order);
    }

    // Xóa sản phẩm
    public function destroy(Request $request, Order $Order)
    {
        $Order->delete();

        return response()->noContent();
    }
}
