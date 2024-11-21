<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Design extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'file_path',
        'description',
    ];

    // Relation to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation to Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Accessor for file URL
    public function getFileUrlAttribute()
    {
        return Storage::url($this->file_path);
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
