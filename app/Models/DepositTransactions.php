<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepositTransactions extends Model
{
  
    protected $fillable = [
        'transaction_id',
        'status',
        'is_cancel',
        'is_refuse',
        'is_maintenance',
        'is_error',
        'channel',
        'merchant_no',
        'order_id',
        'bank_code',
        'amount',
        'signature',
        'order_description',
        'url_merchant_notify',
        'url_merchant_redirect',
        'url_payment',
        'url_vnpay_payment',
        'params_merchant_data',
        'params_vnpay_data',
        'response_vnpay_data',
        'expired_at',
        'updated_at',
    ];
    
  
}
