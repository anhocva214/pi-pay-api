<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherMerchantLogs extends Model
{
    protected $table='voucher_merchants_logs';
    protected $fillable = [
        'merchant_no',
        'amount',
        'transactionId',
        'voucher_link',
        'voucher_code',
        'voucher_serial',
        'expiryDate',
        'created_at',
        'updated_at',
        'voucherName',
        'signature'
    ];
}
