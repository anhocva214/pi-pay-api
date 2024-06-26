<?php

namespace App\Http\Controllers\API\Orders;

use App\Models\WithdrawTransactions;
use App\Repositories\MerchantRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DepositTransactions;
use App\Repositories\BankRepository;
use App\Repositories\DepositTransactionRepository;
use App\Repositories\WithdrawTransactionRepository;

class TransactionController extends Controller
{
    protected $mainRepository;
    protected $bankRepository;
    protected $withdrawtransactionRepository;
    protected $deposittracsactionRepository;

    public function __construct(MerchantRepository $mainRepository, BankRepository $bankRepository, DepositTransactionRepository $deposittracsactionRepository, WithdrawTransactionRepository $withdrawtransactionRepository)
    {
        $this->mainRepository = $mainRepository;
        $this->bankRepository = $bankRepository;
        $this->withdrawtransactionRepository = $withdrawtransactionRepository;
        $this->deposittracsactionRepository = $deposittracsactionRepository;
    }
    public function deposit(Request $request)
    {
        $params = $request->all();
        $token_key = $params['token_key'] ?? " ";
        $transaction_id = $params['transaction_id'] ?? " ";
        if (!$token_key || !$transaction_id) {
            return show_error('require_valid', $params);
        }

        $merchant = $this->mainRepository->findByKey('token_key', $token_key);
        $verify_signature_value = [
            'merchant_no' => $merchant->merchant_no,
            'transaction_id' => $transaction_id,
        ];
        $sinature = $params['signature'] ?? '';
        if (!verify_signature($verify_signature_value, $merchant->api_key, $sinature)) {
            return show_error('signature_not_valid', $verify_signature_value);
        }
        $deposit = $this->deposittracsactionRepository->findByKey('transaction_id', $transaction_id);
        if(!$deposit){
            return show_error('transaction_not_found');
        }
        $arrayData = [
            'transaction_id' => $deposit->transaction_id,
            'amount' => $deposit->amount,
            'status' => $deposit->status,
            'channel' => $deposit->channel,
            'order_id' => $deposit->order_id,
            "order_description" => $deposit->order_description,
            'url_merchant_notify' => $deposit->url_merchant_notify,
            'url_merchant_redirect' => $deposit->url_merchant_redirect,
            'created_at' => $deposit->created_at,
        ];
        return show_success($arrayData, $params);
    }
    public function getAllDeposit(Request $request){
        $data=$this->deposittracsactionRepository->all();
        return show_success($data);
    }
    public function getAllWithdraw(Request $request){
        $data=$this->withdrawtransactionRepository->all();
        return show_success($data);
    }
    public function withdraw(Request $request)
    {
        $params = $request->all();
        $token_key = $params['token_key'] ?? " ";
        $transaction_id = $params['transaction_id'] ?? " ";
        if (!$token_key || !$transaction_id) {
            return show_error('require_valid', $params);
        }

        $merchant = $this->mainRepository->findByKey('token_key', $token_key);
        $verify_signature_value = [
            'merchant_no' => $merchant->merchant_no,
            'transaction_id' => $transaction_id,
        ];
        $sinature = $params['signature'] ?? '';
        if (!verify_signature($verify_signature_value, $merchant->api_key, $sinature)) {
            return show_error('signature_not_valid', $verify_signature_value);
        }
        $withdraw = $this->withdrawtransactionRepository->findByKey('transaction_id', $transaction_id);
        if(!$withdraw){
            return show_error('transaction_not_found');
        }
        $arrayData = [
            'transaction_id' => $withdraw->transaction_id,
            'amount' => $withdraw->amount,
            'status' => $withdraw->status,
            'fee' => $withdraw->amount * ($withdraw->fee / 100),
            'channel' => $withdraw->channel_slug,
            'order_id' => $withdraw->order_id,
            'created_at' => $withdraw->created_at,

        ];
        if ($withdraw->status === 'rejected') {
            $arrayData['reason_reject'] = $withdraw->reject_reason;
        }
        return show_success($arrayData, $params);
    }
}
