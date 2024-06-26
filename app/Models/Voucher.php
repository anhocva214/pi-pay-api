<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table='vouchers';
    protected $fillable = [
        'productId',
        'productNm',
        'productDesc',
        'productShortDesc',
        'priceId',
        'priceValue',
        'created_at',
        'updated_at',
    ];
}

