<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
  
    protected $fillable = [
        'setting_key',
        'setting_value',
        'updated_at',
    ];

    
  
}
