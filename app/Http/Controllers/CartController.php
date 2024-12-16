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
        ->with(['product.firstImage'])
        ->orderBy('created_at', 'desc') 
        ->paginate(10);

        return CartResource::collection($carts);
    }

    public function store(StoreCartRequest $request)
    {
        $validated = $request->validated();
        $cart = new Cart();

        $cart->user_id = Auth::id();  
        $cart->product_id = $validated['product_id'];
        $cart->amount = $validated['amount'];
    
        $cart->save();

        return new CartResource($cart);
    }

    public function destroy($product_id)
    {
        $affectedRows = \App\Models\Cart::where('user_id', Auth::id())
            ->where('product_id', $product_id)
            ->delete();

        if ($affectedRows === 0) {
            return response()->json(['message' => 'Không tìm thấy mục cần xóa'], 404);
        }

        return response()->json(['message' => 'Xoá thành công'], 200);
    }

    public function update(Request $request, $product_id)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $affectedRows = Cart::where('user_id', Auth::id())
            ->where('product_id', $product_id)
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
            ->with('product.firstImage') 
            ->orderBy('created_at', 'desc') 
            ->take(5) 
            ->get();

        return CartResource::collection($carts);
    }

}
