<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable =[
        'phonenumber',
        'address',
        'totalprice',
        'status',
        'payment_status'
    ];
    protected $hidden=[
        'updated_at',
        'created_at'
    ];
}
