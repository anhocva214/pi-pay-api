<?php

namespace App\Repositories;

use App\Models\PayBillLogs as MainModel;

class PayBillLogsRepository extends BaseRepository
{
    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
}
