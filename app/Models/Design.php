<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Design extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'file_path', 
        'description',
    ];

    /**
     * Quan hệ với User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quan hệ với Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope để lọc các thiết kế theo người tạo
     */
    protected static function booted(): void
    {
        static::addGlobalScope('creator', function (Builder $builder) {
            if (Auth::check() && !Auth::user()->isAdmin()) {
                $builder->where('user_id', Auth::id());
            }
        });
    }

    public function getFileUrlAttribute()
    {
        return Storage::disk('gcs')->url($this->file_path);
    }
}
