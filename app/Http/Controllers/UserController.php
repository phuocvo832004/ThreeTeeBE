<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
    
}
