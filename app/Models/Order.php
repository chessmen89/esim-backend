<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Ensure these fields are fillable for mass assignment.
    protected $fillable = [
        'user_id',
        'airalo_order_id',
        'order_data',
        'status',
    ];

    // Cast order_data to array (so JSON is decoded automatically).
    protected $casts = [
        'order_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $table = 'orders';

}
