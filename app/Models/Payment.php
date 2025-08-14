<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'order_id',
        'transaction_id',
        'amount',
        'payment_method',
        'status'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function generateTransactionId()
    {
        return 'TXN-' . strtoupper(uniqid());
    }
}