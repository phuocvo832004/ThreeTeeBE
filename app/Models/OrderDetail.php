<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OrderDetail extends Model
{
    /** @use HasFactory<\Database\Factories\OrderDetailFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'amount',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'updated_at',
        'created_at'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class); 
    }

    protected static function booted(): void
    {
        static::addGlobalScope('creator', function (Builder $builder) {
            if (!Auth::check() || !Auth::user()->isAdmin()) {
                $builder->whereHas('order', function ($query) {
                    $query->where('user_id', Auth::id());
                });
            }
        });
    }
}
