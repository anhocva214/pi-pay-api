<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultLogs extends Model
{
    protected $fillable = [
        'transaction_id',
        'METHOD',
        'request_URL',
        'net_connect',
        'status_code',
        'request',
        'response',
        'created_at',
        'updated_at',
        'merchant_no'
    ];
}
