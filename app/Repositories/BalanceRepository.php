<?php

namespace App\Repositories;

use App\Models\Balances as MainModel;

class BalanceRepository extends BaseRepository
{
    protected $columns = [
        'id',
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
}
