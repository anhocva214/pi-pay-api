<?php

namespace App\Repositories;

use App\Models\WithdrawTransactions as MainModel;

class WithdrawTransactionRepository extends BaseRepository
{
    protected $columns = [
        'id',
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
}
