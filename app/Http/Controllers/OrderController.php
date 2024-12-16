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
                'orderCode' => $order->id,
                'amount' => $order->totalprice,
                'currency' => 'VND',
                'description' => "Payment for Order #" . $order->id,
                'callback_url' => route('orders.payment.callback', ['order' => $order->id]),
                'returnUrl' => route('orders.payment.return', ['order' => $order->id]), 
                'cancelUrl' => route('orders.payment.cancel', ['order' => $order->id]), 
            ];
    
            $response = $this->payOS->createPaymentLink($body);
    
            if (isset($response['checkoutUrl'])) {
                $order->update([
                    'payment_link' => $response['checkoutUrl'],
                    'payment_link_id' => $response['orderCode'], 
                ]);
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
        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => true,
                'message' => 'Payment was successful',
                'order_id' => $order->id,
            ]);
        }
    
        $order->update([
            'payment_status' => 'unpaid', 
            'payment_date' => now(), 
        ]);
    
        return response()->json([
            'success' => false,
            'message' => 'Payment was not successful',
            'order_id' => $order->id,
        ]);
    }
      

    public function paymentCancel(Order $order)
    {
        $order->update([
            'payment_status' => 'cancelled',
            'payment_date' => now(),
        ]);
    
        // Sử dụng biến môi trường FRONTEND_URL
        $frontendUrl = env('FRONTEND_URL', 'https://threetee.netlify.app') . '/cancel';
    
        return redirect()->away($frontendUrl);
    }
    
    public function getPaymentInfo(Order $order)
    {
        try {
            if (!$order->payment_link_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment link ID is missing',
                ], 400);
            }
    
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
        try {
            $data = $request->all();
            
            if (!isset($data['paymentLinkId']) || !isset($data['status'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing paymentLinkId or status in the callback data'
                ], 400);
            }
    
            $order = Order::where('payment_link_id', $data['paymentLinkId'])->first();
    
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }
    
            $order->update([
                'payment_status' => $data['status'], 
                'payment_date' => now(),
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Callback processed successfully',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $th->getMessage()
            ], 500);
        }
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
