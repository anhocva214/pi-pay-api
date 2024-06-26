<?php

namespace App\Repositories;

use App\Models\WalletTypes as MainModel;

class WalletTypeRepository extends BaseRepository
{
    protected $columns = [
        'id',
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
    public function listItemsIsAdd() {
        return $this->model->where('is_add',1)->get();
    }
    
}
