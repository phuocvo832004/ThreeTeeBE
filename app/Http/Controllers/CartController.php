<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $carts = Auth::user()->carts()
        ->with(['productDetail.product.images']) 
        ->orderBy('created_at', 'desc') 
        ->paginate(10);

        return CartResource::collection($carts);
    }

public function store(StoreCartRequest $request)
{
    $validated = $request->validated();

    // Tìm xem sản phẩm này đã tồn tại trong giỏ hàng của user chưa
    $existingCart = Cart::where('user_id', Auth::id())
        ->where('product_detail_id', $validated['product_detail_id'])
        ->first();

    if ($existingCart) {

        $affectedRows = Cart::where('user_id', Auth::id())
        ->where('product_detail_id', $validated['product_detail_id'])
        ->update([
            'amount' => $existingCart->amount + $validated['amount'],
            'updated_at' => now(),
        ]);

        $updatedCart = Cart::where('user_id', Auth::id())
            ->where('product_detail_id', $validated['product_detail_id'])
            ->first();
        return new CartResource($updatedCart);
    } else {
        // Nếu chưa tồn tại, tạo mới
        $cart = new Cart();
        $cart->user_id = Auth::id();
        $cart->product_detail_id = $validated['product_detail_id'];
        $cart->amount = $validated['amount'];
        $cart->save();

        return new CartResource($cart);
    }
}


    public function destroy($product_detail_id)
    {
        $affectedRows = \App\Models\Cart::where('user_id', Auth::id())
            ->where('product_detail_id', $product_detail_id)
            ->delete();

        if ($affectedRows === 0) {
            return response()->json(['message' => 'Không tìm thấy mục cần xóa'], 404);
        }

        return response()->json(['message' => 'Xoá thành công'], 200);
    }

    public function update(Request $request, $product_detail_id)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $affectedRows = Cart::where('user_id', Auth::id())
            ->where('product_detail_id', $product_detail_id)
            ->update([
                'amount' => $validated['amount'],
            ]);

        if ($affectedRows === 0) {
            return response()->json(['message' => 'Không tìm thấy mục cần cập nhật'], 404);
        }


        return response()->json([
            'message' => 'Cập nhật thành công',
        ], 200);
    }

    public function index5()
    {
        $carts = Auth::user()->carts()
            ->with(['productDetail.product.images']) 
            ->orderBy('created_at', 'desc') 
            ->take(5) 
            ->get();

        return CartResource::collection($carts);
    }

}
