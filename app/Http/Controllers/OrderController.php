<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetAllOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;

class OrderController extends Controller
{
    // Hiển thị danh sách sản phẩm
    public function index(Request $request)
    {
        $order = QueryBuilder::for(Order::class)
                    ->allowedFilters([
                        'status',
                        'totalprice', 
                        'payment_status',
                        AllowedFilter::callback('order_date_year', function ($query, $value) {
                            $query->whereYear('order_date', $value);
                        }),
                        AllowedFilter::callback('order_date_month', function ($query, $value) {
                            $query->whereMonth('order_date', $value);
                        }),
                        AllowedFilter::callback('order_date_day', function ($query, $value) {
                            $query->whereDay('order_date', $value);
                        }),
                        AllowedFilter::callback('order_date_range', function ($query, $value) {
                            if (is_array($value)) {
                                $startDate = $value[0] ?? null;
                                $endDate = $value[1] ?? null;
                            } elseif (is_string($value)) {
                                $dates = explode(',', $value);
                                $startDate = $dates[0] ?? null;
                                $endDate = $dates[1] ?? null;
                            } else {
                                return;
                            }
                
                            if ($startDate && $endDate) {
                                $query->whereBetween('order_date', [$startDate, $endDate]);
                            }
                        })
                    ])                    
                    ->defaultSort('status')
                    ->allowedSorts('status','totalprice','payment_status','payment_date','order_date')
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

    public function getAllOrders(GetAllOrderRequest $request)
    {

        $validated = $request->validated();

        $orders = QueryBuilder::for(Order::with('orderDetails'))
        ->allowedFilters([
            'status',
            'totalprice',
            'payment_status',
            AllowedFilter::callback('order_date_year', function ($query, $value) {
                $query->whereYear('order_date', $value);
            }),
            AllowedFilter::callback('order_date_month', function ($query, $value) {
                $query->whereMonth('order_date', $value);
            }),
            AllowedFilter::callback('order_date_day', function ($query, $value) {
                $query->whereDay('order_date', $value);
            }),
            AllowedFilter::callback('order_date_range', function ($query, $value) {
                if (is_array($value)) {
                    $startDate = $value[0] ?? null;
                    $endDate = $value[1] ?? null;
                } elseif (is_string($value)) {
                    $dates = explode(',', $value);
                    $startDate = $dates[0] ?? null;
                    $endDate = $dates[1] ?? null;
                } else {
                    return;
                }

                if ($startDate && $endDate) {
                    $query->whereBetween('order_date', [$startDate, $endDate]);
                }
            })
        ])
        ->defaultSort('status') // Sắp xếp mặc định
        ->allowedSorts('status', 'totalprice', 'payment_status', 'payment_date', 'order_date') // Các trường được phép sắp xếp
        ->paginate(); 

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }
}
