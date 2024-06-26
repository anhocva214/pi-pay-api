<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallBackLogs extends Model
{
  
    protected $fillable = [
        'callback_id',
        'result_code',
        'merchant_no',
        'order_no',
        'ylt_order_no',
        'amount',
        'channel',
        'extra_param',
        'payload',
        'sign',
        'user_amount',
        'updated_at',
    ];
   
    
  
}
