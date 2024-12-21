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
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use PayOS\PayOS;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{

    protected PayOS $payOS;

    protected function hashOrderId($orderId)
    {
        $secretKey = config('app.key');
        $hash = hash_hmac('sha256', $orderId, $secretKey);
        $numericHash = hexdec(substr($hash, 0, 15)); 
        return $numericHash % 9007199254740991; 
    }

    public function resolveOrderByHashedId($hashedOrderId)
    {
        // Tìm đơn hàng bằng hashed_order_id trước
        $order = Order::where('hashed_order_id', $hashedOrderId)->first();
    
        // Nếu không tìm thấy, kiểm tra thông qua hàm hashOrderId
        if (!$order) {
            $orders = Order::all(); // Lấy tất cả các đơn hàng
            foreach ($orders as $order) {
                if ($this->hashOrderId($order->id) === intval($hashedOrderId)) {
                    return $order;
                }
            }
        }
    
        // Nếu không tìm thấy, ném ngoại lệ
        if (!$order) {
            throw new ModelNotFoundException("Order not found for hashed ID: $hashedOrderId");
        }
    
        return $order;
    }
    
    
    public function createPaymentLink(Request $request, Order $order)
    {
        $hashedOrderId = $this->hashOrderId($order->id);
        try {
            $body = [
                'orderCode' => $hashedOrderId,
                'amount' => $order->totalprice,
                'currency' => 'VND',
                'description' => "Payment for Order #" . $order->id,
                'callback_url' => route('orders.payment.callback', ['order' => $hashedOrderId]),
                'returnUrl' => route('orders.payment.return', ['order' => $hashedOrderId]),
                'cancelUrl' => route('orders.payment.cancel', ['order' => $hashedOrderId]),
            ];
    
            $response = $this->payOS->createPaymentLink($body);
    
            if (isset($response['checkoutUrl'])) {
                $order->update([
                    'payment_link' => $response['checkoutUrl'],
                    'payment_link_id' => $response['orderCode'],
                    'hashed_order_id' => $hashedOrderId,
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
        $frontendBaseUrl = env('FRONTEND_URL', 'https://threetee.netlify.app');
    
        if ($order->payment_status === 'paid') {
            $frontendUrl = $frontendBaseUrl . '/success';
            return redirect()->away($frontendUrl);
        }
    
        $order->update([
            'payment_status' => 'unpaid', 
            'payment_date' => now(), 
        ]);
    
        $frontendUrl = $frontendBaseUrl . '/success';
        return redirect()->away($frontendUrl);
    }
    
      
    public function paymentCancel($hashedOrderId)
    {
        try {
            $order = $this->resolveOrderByHashedId($hashedOrderId);
            $order->update([
                'payment_status' => 'cancelled',
                'payment_date' => now(),
            ]);
    
            $frontendUrl = env('FRONTEND_URL', 'https://threetee.netlify.app') . '/cancel';
            return redirect()->away($frontendUrl);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Order not found'], 404);
        }
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
        $data = $request->all();
    
        // Log dữ liệu callback để debug
        Log::info('Payment Callback Data:', $data);
    
        if (!isset($data['paymentLinkId'])) {
            return response()->json([
                'success' => false,
                'message' => 'Missing paymentLinkId in the callback data'
            ], 400);
        }
    
        $hashedOrderId = $data['paymentLinkId'];
        
        // Tìm order dựa vào hashed_order_id
        $order = Order::where('hashed_order_id', $hashedOrderId)->first();
    
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }
    
        // Cập nhật trạng thái thanh toán
        $order->update([
            'payment_status' => $data['status'] ?? 'unknown',
            'payment_date' => now(),
        ]);
    
        return response()->json([
            'success' => true,
            'message' => 'Callback processed successfully',
        ]);
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
        $user = Auth::user();

        $order = QueryBuilder::for(Order::class)
                    ->where('user_id', $user->id)
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
        ->defaultSort('status') 
        ->allowedSorts('status', 'totalprice', 'payment_status', 'payment_date', 'order_date') // Các trường được phép sắp xếp
        ->paginate(); 

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }
    public function getOrderStatistics()
    {
        $orderStatistics = DB::table('orders')
            ->selectRaw('YEAR(order_date) as year, MONTH(order_date) as month, COUNT(*) as total_orders')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        $statisticsArray = $orderStatistics->map(function ($item) {
            return [
                'year' => $item->year,
                'month' => $item->month,
                'total_orders' => $item->total_orders,
            ];
        });

        return response()->json($statisticsArray);
    }

    public function getProfitStatistics()
    {
        $orderStatistics = DB::table('orders')
            ->selectRaw('YEAR(order_date) as year, MONTH(order_date) as month, SUM(totalprice) as total_price')
            ->where('status', 'success')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        $statisticsArray = $orderStatistics->map(function ($item) {
            return [
                'year' => $item->year,
                'month' => $item->month,
                'total_price' => $item->total_price,
            ];
        });

        return response()->json($statisticsArray);
    }

    public function getOrderAdmin($orderId)
    {
        $order = Order::with(['user', 'orderDetails.productDetail.product'])->find($orderId);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }
    
        return response()->json($order);
    }
}
