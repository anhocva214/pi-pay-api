<?php

namespace App\Repositories;

use App\Models\Errors as MainModel;

class ErrorRepository extends BaseRepository
{
    protected $columns = [
        'id',
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
}
