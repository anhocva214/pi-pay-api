<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTypes extends Model
{
  
    protected $fillable = [
        'name',
        'slug',
        'is_add',
        'order_no',
        'updated_at',
    ];
 
}
