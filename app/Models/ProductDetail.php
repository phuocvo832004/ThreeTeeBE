<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDetail extends Model
{
    use HasFactory;

    protected $table = 'product_details';

    protected $fillable = [
        'product_id',
        'stock',
        'price',
        'size',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
    public $timestamps = true;
}
