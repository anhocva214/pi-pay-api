<?php
namespace App\Repositories;

use App\Models\Balances;
use App\Models\Wallets;

class CustomRepository implements CustomRepositoryInterface
{
    public function updateWallet($merchantId, $walletTypeId, $amount)
    {
        $wallet = Wallets::where('merchant_id', $merchantId)
            ->where('wallet_type_id', $walletTypeId)
            ->first();
        $balance = $wallet->balance;
        // $newBalance = $balance + ($amount - ($amount * ($fee / 100)));
        $newBalance = $balance + $amount ;
        $wallet->update(['balance' => $newBalance]);
        return $wallet;
    }
    public function updateBalance($merchantId, $walletTypeSlug, $newBalance)
    {

        $balance = Balances::where('merchant_id', $merchantId)->first();

        if (!$balance) {
            $balance = new Balances();
            $balance->merchant_id = $merchantId;
            $balance->save();
        }
        $walletOfBalance = $walletTypeSlug;

        $balance->$walletOfBalance = $newBalance;

        $balance->save();

        return $balance;
    }
}