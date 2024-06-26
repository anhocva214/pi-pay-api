<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayBillLogs extends Model
{
    protected $table='paybill_logs';
    protected $fillable = [
        'merchant_id',
        'bill_number',
        'amount',
        'type_bill',
        'signature',
        'customer_code',
        'transaction_id',
        'created_at',
        'updated_at',
    ];
}

