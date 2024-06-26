<?php

namespace App\Repositories;

use App\Models\ResultLogs as MainModel;

class ResultLogsRepository extends BaseRepository
{
    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
}
