<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallets extends Model
{
  
    protected $fillable = [
        'merchant_id',
        'wallet_type_id',
        'wallet_name',
        'balance',
        'updated_at',
    ];
    public function format() {
      
        return [
          'wallet_name' => $this->wallet_name,
          'balance' => $this->balance,
        ];
    }
 
}
