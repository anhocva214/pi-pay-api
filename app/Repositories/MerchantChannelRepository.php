<?php

namespace App\Repositories;

use App\Models\MerchantChannels as MainModel;

class MerchantChannelRepository extends BaseRepository
{
    protected $columns = [
        'id',
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
    public function checkExistsWithSlug($merchantId, $slug)
    {
        $item = $this->model->where('merchant_id', $merchantId)->where('slug', $slug)->first();
        return $item;
    }
    
}
