<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderDetailRequest;
use App\Http\Requests\UpdateOrderDetailRequest;
use App\Http\Resources\OrderDetailsResource;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderDetailController extends Controller
{
    public function store(StoreOrderDetailRequest $request)
    {
        $validated = $request->validated();

        $orderDetail = OrderDetail::create($validated);

        return new OrderDetailsResource($orderDetail);
    }

    public function update(UpdateOrderDetailRequest $request, OrderDetail $orderDetail)
    {
        $validated = $request->validated();

        $orderDetail->update($validated);

        return new OrderDetailsResource($orderDetail);
    }
}
