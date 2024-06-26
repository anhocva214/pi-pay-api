<?php
namespace App\Repositories;
use App\Models\Wallets as MainModel;
class WalletRepository extends BaseRepository
{
    protected $columns = [
        'id',
    ];
    protected $walletTypeRepository;
    public function __construct(MainModel $model, WalletTypeRepository $walletTypeRepository)
    {
        parent::__construct($model);
        $this->walletTypeRepository = $walletTypeRepository;
    }
    public function checkExists($merchantId, $walletTypeId)
    {
        $item = $this->model->where('merchant_id', $merchantId)->where('wallet_type_id', $walletTypeId)->first();
        return $item;
    }
    public function assignWalletTypesToMerchant($merchantId = "")
    {
        if (!$merchantId) {
            return;
        }
        $assignedItem = [];
        $result = [];
        $walletTypesToAdd  = $this->walletTypeRepository->listItemsIsAdd();
        if (!$walletTypesToAdd ->isEmpty()) {
            foreach ($walletTypesToAdd  as $walletType) {
                $walletTypeId =   $walletType->id ?? "";
                $checkWalletExits = $this->checkExists($merchantId, $walletTypeId);
                $data = [
                    'merchant_id' => $merchantId,
                    'wallet_type_id' => $walletTypeId,
                    'wallet_name' => $walletType->name ?? "",
                ];
                if (!$checkWalletExits) {
                    $assignedItem = $this->add($data);
                    $result[] = $assignedItem->id;
                }
            }
        }
        return $result;
    }
}
