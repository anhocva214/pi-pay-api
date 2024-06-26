<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Errors extends Model
{
  
    protected $fillable = [
        'slug',
        'msg',
        'updated_at',
    ];

    
  
}
