<?php

namespace App\Repositories;

use App\Models\Banks as MainModel;

class BankRepository extends BaseRepository
{
    protected $columns = [
        'id',
    ];

    public function __construct(MainModel $model)
    {
        parent::__construct($model);
    }
    public function checkBankCodeIsActive($bankcode,$channel) {
        $bankChannels = ['bank_qr','bank_transfer','upacp_pc'];
        $result = 1;
        $bank = null;
        $bank = $this->model->where('bank_code',$bankcode)->where($channel,1)->first();
        if(!$bank) {
            $result = 0;
        }
        return $result;
    }
}
