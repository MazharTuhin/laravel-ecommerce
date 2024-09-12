<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'total',
        'discount',
        'vat',
        'payable',
        'customer_details',
        'ship_details',
        'shipping_method',
        'transaction_id',
        'delivery_status',
        'payment_status',
        'user_id',
    ];
}
