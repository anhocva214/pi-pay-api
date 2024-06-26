<?php

namespace App\Repositories;

use App\Models\BalanceLogs as MainModel;

class BalanceLogsRepository extends BaseRepository
{
    protected $columns = [
        'id',
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
}
