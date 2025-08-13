<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PromoCode extends Model
{
    protected $fillable = [
        'code', 'discount_type', 'discount_value',
        'min_order', 'max_discount', 'start_date', 'end_date'
    ];

    protected $dates = ['start_date', 'end_date'];

    public function isValid()
    {
        $now = Carbon::now();
        return $this->start_date <= $now && $this->end_date >= $now;
    }

    public function calculateDiscount($subtotal)
    {
        if ($subtotal < $this->min_order) {
            return 0;
        }

        $discount = $this->discount_type === 'percentage' 
            ? $subtotal * ($this->discount_value / 100)
            : $this->discount_value;

        if ($this->max_discount && $discount > $this->max_discount) {
            return $this->max_discount;
        }

        return $discount;
    }
}
