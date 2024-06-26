<?php

namespace App\Repositories;

use App\Models\Merchants as MainModel;

class MerchantRepository extends BaseRepository
{
    protected $columns = [
        'id',
        'name',
        'phone',
        'email',
        'cccd',
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
}
