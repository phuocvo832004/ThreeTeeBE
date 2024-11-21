<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    // Hiển thị danh sách sản phẩm
    public function index(Request $request)
    {
        $order = QueryBuilder::for(Order::class)
                    ->allowedFilters('status','totalprice','payment_status')
                    ->defaultSort('status')
                    ->allowedSorts('status','totalprice','payment_status','payment_date')
                    ->paginate();
        return new OrderCollection($order);
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

        $order = Auth::user()->orders()->create($validated);

        return new OrderResource($order);
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
