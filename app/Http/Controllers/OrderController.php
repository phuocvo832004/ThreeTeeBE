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
use PayOS\PayOS;

class OrderController extends Controller
{

    protected PayOS $payOS;


    public function createPaymentLink(Request $request, Order $order)
    {
        try {
            $body = [
                'orderCode' => $order->id, // Thêm mã đơn hàng
                'amount' => $order->totalprice,
                'currency' => 'VND',
                'description' => "Payment for Order #" . $order->id,
                'callback_url' => route('orders.payment.callback', ['order' => $order->id]),
                'returnUrl' => route('orders.payment.return', ['order' => $order->id]), // Thêm returnUrl
                'cancelUrl' => route('orders.payment.cancel', ['order' => $order->id]), // Thêm cancelUrl
            ];

            $response = $this->payOS->createPaymentLink($body);

            if (isset($response['checkoutUrl'])) {
                $order->update(['payment_link' => $response['checkoutUrl']]); // Lưu link thanh toán vào DB nếu cần
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment link created successfully',
                'payment_link' => $response['checkoutUrl'],
            ]);
        } catch (\Throwable $th) {
            return $this->handleException($th);
        }
    }
    public function paymentReturn(Order $order)
    {
        return response()->json([
            'success' => true,
            'message' => 'Payment was successful',
            'order_id' => $order->id,
        ]);
    }

    public function paymentCancel(Order $order)
    {
        return response()->json([
            'success' => false,
            'message' => 'Payment was canceled',
            'order_id' => $order->id,
        ]);
    }

    public function getPaymentInfo(Order $order)
    {
        try {
            $response = $this->payOS->getPaymentLinkInformation($order->payment_link_id); 

            return response()->json([
                'success' => true,
                'message' => 'Payment info retrieved successfully',
                'data' => $response,
            ]);
        } catch (\Throwable $th) {
            return $this->handleException($th);
        }
    }

    public function handlePaymentCallback(Request $request)
    {
        $data = $request->all();
    
        $order = Order::where('payment_link_id', $data['paymentLinkId'])->first(); 
    
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }
    
        $order->update([
            'payment_status' => $data['status'], 
            'payment_date' => now(),
        ]);
    
        return response()->json(['success' => true, 'message' => 'Callback processed successfully']);
    }
    

    public function cancelPaymentLink(Request $request, Order $order)
    {
        try {
            $body = $request->input('cancellationReason') ? ['cancellationReason' => $request->input('cancellationReason')] : null;

            $response = $this->payOS->cancelPaymentLink($order->payment_link_id, $body); 

            return response()->json([
                'success' => true,
                'message' => 'Payment link canceled successfully',
                'data' => $response,
            ]);
        } catch (\Throwable $th) {
            return $this->handleException($th);
        }
    }

    protected function handleException(\Throwable $th)
    {
        return response()->json([
            'success' => false,
            'message' => $th->getMessage(),
        ], 500);
    }


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

    public function show(Request $request, Order $Order)
    {
        return new OrderResource($Order);
    }

    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();

        $order = Auth::user()->orders()->create($validated);

        return new OrderResource($order);
    }

    public function update(UpdateOrderRequest $request, Order $Order)
    {
        $validated = $request->validated();

        $Order->update($validated);

        return new OrderResource($Order);
    }

    public function destroy(Request $request, Order $Order)
    {
        $Order->delete();

        return response()->noContent();
    }

    public function getAllOrders(GetAllOrderRequest $request)
    {

        $validated = $request->validated();

        $orders = Order::with('orderDetails')->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }
}
