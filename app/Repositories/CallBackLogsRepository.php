<?php

namespace App\Repositories;

use App\Models\CallBackLogs as MainModel;

class CallBackLogsRepository extends BaseRepository
{
    protected $columns = [
        'id',
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
}
