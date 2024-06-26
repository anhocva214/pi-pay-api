<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceLogs extends Model
{
    protected $fillable = [
        'merchant_id',
        'wallet_type_id',
        'order_id',
        'old_balance',
        'channel',
        'new_balance',
        'type_change',
        'amount',
        'created_at',
        'updated_at',
    ];
}

