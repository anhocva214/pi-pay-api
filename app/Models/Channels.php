<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channels extends Model
{
  
    protected $fillable = [
        'name',
        'slug',
        'fee_deposit',
        'fee_withdraw',
        'description',
        'is_online',
        'updated_at',
    ];
    
  
}
