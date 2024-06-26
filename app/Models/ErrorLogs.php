<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLogs extends Model
{

    protected $fillable = [
        'log_id',
        'api_name',
        'order_id',
        'error_id',
        'msg',
        'data',
        'updated_at',
    ];
    
}
