<?php

namespace App\Repositories;

use App\Models\ErrorLogs as MainModel;

class ErrorLogRepository extends BaseRepository
{
    protected $columns = [
        'id',
        'log_id'
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
    public function countRequestsInLastMinute($OrderId)
    {
        $startTime = now()->subMinute();
        $endTime = now();

        return $this->model->where('order_id', $OrderId)
            ->whereBetween('created_at', [$startTime, $endTime])
            ->count();
    }
}
