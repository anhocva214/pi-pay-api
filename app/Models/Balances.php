<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Balances extends Model
{
  
    protected $fillable = [
        'merchant_id',
        'zalo',
        'viettel',
        'payout',
        'momo',
        'online',
        'usdt',
        'total_wallet',
        'recharge',
    ];
    public function merchant() {
        return $this->belongsTo(Merchants::class,'merchant_id','id');
    }
  
}
