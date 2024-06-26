<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawTransactions extends Model
{
   protected $fillable=[
    'transaction_id',
    'merchant_id',
    'channel_id',
    'channel_slug',
    'amount',
    'fee',
    'status',
    'reject_reason',
    'note',
    'order_id',
    'wallet_type_id'
   ];
   
}
