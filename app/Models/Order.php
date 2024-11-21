<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth; 
use App\Models\User; 

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'phonenumber',
        'address',
        'totalprice',
        'status',
        'payment_status'
    ];

    protected $hidden = [
        'updated_at',
        'created_at'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    protected static function booted(): void
    {
        static::addGlobalScope('creator', function (Builder $builder) {
            if (!Auth::check() || !Auth::user()->isAdmin()) {
                $builder->where('user_id', Auth::id());
            }
        });
    }
}
