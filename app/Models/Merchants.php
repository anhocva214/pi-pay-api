<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchants extends Model
{
  
    protected $fillable = [
        'name',
        'merchant_no',
        'token_key',
        'api_key',
        'webhook_url',
        'ip_whitelist',
        'is_ban',
        'max_number_transaction',
        'max_deposit_amount',
        'max_withdraw_amount',
        'updated_at',
    ];
    public function balance() {
        return $this->hasOne(Balances::class,'merchant_id','id');
    }
    public function wallets() {
        return $this->hasMany(Wallets::class,'merchant_id','id');
    }
    
  
}
