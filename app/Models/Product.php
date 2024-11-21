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
        'amount',
        'description',
        'create',
        'sold',
        'price',
        'size',
        'rate',
    ];

    public $timestamps = true; 
}
