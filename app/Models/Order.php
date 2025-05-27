<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'quantity',
        'type',
        'status',
        'amount',
        'currency',
        'airalo_order_id',
        'order_data',
    ];

    protected $casts = [
        'order_data' => 'array',
        'amount'     => 'decimal:3',
    ];
}
