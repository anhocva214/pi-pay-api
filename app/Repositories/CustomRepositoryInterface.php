<?php

namespace App\Repositories;

interface CustomRepositoryInterface
{
    public function updateWallet($merchantId, $walletTypeId, $amount);
    public function updateBalance($merchantId, $walletTypeSlug, $balance);
}
