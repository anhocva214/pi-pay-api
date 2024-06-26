<?php

namespace App\Repositories;

use App\Models\Settings as MainModel;

class SettingRepository extends BaseRepository
{
    protected $columns = [
        'id',
        'setting_key',
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
}
