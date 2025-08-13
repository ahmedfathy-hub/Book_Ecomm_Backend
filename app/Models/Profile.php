<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'address', 'city', 'state', 'country', 'postal_code', 'payment_details','user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
