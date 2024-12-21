<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'sold',
        'rate',
        'category',
    ];

    public function images()
    {
        return $this->hasMany(Image::class, 'product_id', 'id');
    }

    public function designs()
    {
        return $this->hasMany(Design::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }


    public function productDetails()
    {
        return $this->hasMany(ProductDetail::class, 'product_id', 'id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class,'product_id','id');
    }
    public $timestamps = true; 




}
