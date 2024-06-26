<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantChannels extends Model
{
    protected $fillable = [
        'merchant_id',
        'channel_id',
        'fee_deposit',
        'fee_withdraw',
        'created_at',
        'updated_at',
        'slug'
    ];
}
