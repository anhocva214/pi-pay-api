<?php

namespace App\Repositories;

use App\Models\VoucherMerchantLogs as MainModel;

class VoucherMerchantRepository extends BaseRepository
{
    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
}
