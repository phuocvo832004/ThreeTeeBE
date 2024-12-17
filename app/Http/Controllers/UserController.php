<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderCollection;
use App\Models\Image;
use App\Models\Order;
use App\Models\User;
use Cloudinary\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);
    
        $user = $request->user();
    
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không chính xác.',
            ], 400);
        }
    
        $user->password = Hash::make($request->new_password);
        $user->save();

        $user->tokens()->delete();
    
        return response()->json([
            'message' => 'Cập nhật mật khẩu thành công!',
        ]);
    }
    
    public function getAllUsers(Request $request)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
    
        $users = QueryBuilder::for(User::class)
                    ->allowedFilters([
                        AllowedFilter::partial('name'),
                        AllowedFilter::partial('email')
                    ])
                    ->defaultSort('created_at')
                    ->paginate();
    
        return response()->json([
            'users' => $users
        ]);
    }


    public function updateUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255', 
            'avatar' => 'sometimes|required|file|mimes:jpg,jpeg,png,gif|max:20480', 
        ]);
    
        $user = auth()->user(); 
    
        $image = $request->file('avatar');
        $imageUrl = $user->avatar; // Mặc định giữ nguyên avatar hiện tại
    
        if ($image) {
            try {
                $cloudinary = new Cloudinary();
                $preset = 'unsigned'; 
    
                $upload = $cloudinary->uploadApi()->upload(
                    $image->getRealPath(),
                    [
                        'upload_preset' => $preset,
                    ]
                );
    
                $imageUrl = $upload['secure_url'];
            } catch (\Exception $e) {
                return response()->json(['message' => 'Upload failed: ' . $e->getMessage()], 500);
            }
        }
    
        $user->update([
            'name' => $request->input('name', $user->name), 
            'avatar' => $imageUrl,
        ]);
    

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ], 200);
    }
    public function updateRole(Request $request, $id)
    {
        $validated = $request->validate([
            'role' => 'required|string|in:admin,user,staff', // Các vai trò hợp lệ
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->role = $validated['role'];
        $user->save();

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => $user,
        ], 200);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'user' => $user
        ], 200);
    }

    public function showUserOrders($id)
    {
        $user = User::findOrFail($id);
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
}
