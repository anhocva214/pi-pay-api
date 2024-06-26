<?php

namespace App\Repositories;

use App\Models\Channels as MainModel;

class ChannelRepository extends BaseRepository
{
    protected $columns = [
        'id',
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
    public function checkChannelOnline($slug) {
        return $this->model->where('slug',$slug)->where('is_online',1)->first();
    }
    public function getListItemsIsOnline() {
        return $this->model->where('is_online',1)->get();
    }
}
