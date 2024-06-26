<?php

namespace App\Http\Controllers\Api\Orders;

use App\Helpers\ApiHelper;
use App\Helpers\SettingHelper;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\StringHelper;
use App\Repositories\ChannelRepository;
use App\Repositories\DepositTransactionRepository;
use App\Repositories\MerchantRepository as MainRepository;
use Illuminate\Support\Str;

class PaymentController  extends Controller
{
    protected $routeName = "api/orders/payment/";
    protected $merchantPrefix = "pipay";
    protected $mainRepository;
    protected $balanceRepository;
    protected $merchantRepository;
    protected $channelRepository;
    protected $depositTransactionRepository;
    public function __construct(MainRepository $mainRepository, ChannelRepository $channelRepository, DepositTransactionRepository $depositTransactionRepository)
    {
        $this->mainRepository = $mainRepository;
        $this->channelRepository = $channelRepository;
        $this->depositTransactionRepository = $depositTransactionRepository;
    }
    public function index(Request $request)
    {
        $params = $request->all();
        $partnerReference = $params['partnerReference'] ?? [];
        $merchant = $partnerReference['merchant'] ?? "";
        $merchantNo = $merchant['merchant_no'] ?? "";
        if (!$merchantNo) {
            return  show_error('Vui lòng nhập Merchant no', $params);
        }
        $checkMerchant = $this->mainRepository->findByKey('merchant_no', $merchantNo);
        if (!$checkMerchant) {
            return  show_error('Merchant không tồn tại', $params);
        }
        $merchantIsBan = $checkMerchant->is_ban ?? 0;
        if ($merchantIsBan == 1) {
            return  show_error('Merchant đã bị khóa', $params);
        }
        $apiKey = $checkMerchant->api_key ?? "";
        if (!$apiKey) {
            return  show_error('Api Key Không tồn tại', $params);
        }
        $order = $partnerReference['order'] ?? "";
        $orderId = $order['id'] ?? "";
        if (!$orderId) {
            return  show_error('Vui lòng nhập Order ID', $params);
        }
        $transaction = $params['transaction'] ?? "";
        $amount = $transaction['amount'] ?? "";
        if (!$amount) {
            return  show_error('Vui lòng nhập Số tiền', $params);
        }
        if (!is_int($amount)) {
            return  show_error('Số tiền không hợp lệ', $params);
        }
        $channel = $transaction['channel'] ?? "";
        if (!$channel) {
            return  show_error('Chưa chọn cổng thanh toán', $params);
        }
        $checkChannel = $this->channelRepository->checkChannelOnline($channel);
        if (!$checkChannel) {
            return  show_error('Cổng thanh toán không hợp lệ', $params);
        }
        $signature = $transaction['signature'] ?? "";
        if (!$signature) {
            return  show_error('Vui lòng nhập chữ ký giao dịch', $params);
        }
        if (!verify_signature([
            'merchant_no' => $merchantNo,
            'order_id' => $orderId,
            'amount' => $amount,
            'channel' => $channel,
        ], $apiKey, $signature)) {
            return  show_error('Chữ ký không hợp lệ', $params);
        }
        $notificationConfig = $partnerReference['notificationConfig'] ?? [];
        $transactionId =  StringHelper::generateUid();
        $urlVnpayPayment = null;
        $expiredAt = null;
        $bankCode = $transaction['bankCode'] ?? "";
        $urlPayment =  route($this->routeName . "url", ['transaction_id' => $transactionId, 'merchant_no' => $merchantNo]);
        $depositTransactionData = [
            'transaction_id' => $transactionId,
            'status' =>  get_setting_value('deposit_default_status'),
            'is_maintenance' => get_setting_value('is_maintenance'),
            'is_error' => 0,
            'channel' => $channel,
            'merchant_no' => $merchantNo,
            'order_id' => $orderId,
            'bank_code' => $bankCode,
            'amount' => $amount,
            'signature' => $signature,
            'order_description' => $order['description'] ?? "",
            'url_merchant_notify' => $notificationConfig['notifyUrl'] ?? null,
            'url_merchant_redirect' =>  $notificationConfig['redirectUrl'] ?? null,
            'url_payment' => $urlPayment,
            'url_vnpay_payment' => $urlVnpayPayment,
            'params_vnpay_data' => null,
            'response_vnpay_data' => null,
            'expired_at' => $expiredAt,
            'params_merchant_data' =>json_encode( $params),
        ];
        $depositTransactionInfo = $this->depositTransactionRepository->add($depositTransactionData);
      

        return show_success([
            'transaction' => [
                'transaction_id' => $transactionId,
                'status' =>  get_setting_value('deposit_default_status'),
                'channel' => $channel,
                'bank_code' => $bankCode,
                'merchant_no' => $merchantNo,
                'amount' => $amount,
                'order_description' => $order['description'] ?? "",
            ],
            'info' => [
                'merchant_no' => $merchantNo,
                'channel' => $channel,
            ],
            'payment' => [
                'url' => $urlPayment,
            ],
            'depositTransactionId' => $depositTransactionInfo->id ?? "",
        ], $params);
    }
}
