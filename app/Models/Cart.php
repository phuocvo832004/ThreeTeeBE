<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Database\Eloquent\Builder;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'amount',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('creator', function (Builder $builder) {
            if (!Auth::check() || !Auth::user()->isAdmin()) {
                $builder->where('user_id', Auth::id());
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class); 
    }
}
