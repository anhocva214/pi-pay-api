<?php

namespace App\Repositories;

use App\Models\DepositTransactions as MainModel;

class DepositTransactionRepository extends BaseRepository
{
    protected $columns = [
        'id',
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
}
