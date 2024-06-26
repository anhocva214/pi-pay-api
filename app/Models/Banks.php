<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banks extends Model
{
  
    protected $fillable = [
        'name',
        'bank_code',
        'bank_qr',
        'bank_transfer',
        'upacp_pc',
        'updated_at',
    ];
  
}
