<?php

namespace App\Http\Controllers\Api\Orders;


use App\Helpers\ApiHelper;
use App\Helpers\SettingHelper;
use App\Models\BalanceLogs;
use App\Models\WithdrawTransactions;
use App\Repositories\MerchantChannelRepository;
use App\Repositories\PayBillLogsRepository;
use Illuminate\Support\Facades\Http;
use App\Repositories\WalletRepository;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\StringHelper;
use App\Models\Channels;
use App\Models\MerchantChannels;
use App\Models\Balances;
use App\Models\ResultLogs;
use App\Models\Wallets;
use App\Models\WalletTypes;
use App\Repositories\BalanceLogsRepository;
use App\Repositories\BalanceRepository;
use App\Repositories\BankRepository;
use App\Repositories\ChannelRepository;
use App\Repositories\DepositTransactionRepository;
use App\Repositories\CallBackLogsRepository;
use App\Repositories\CustomRepository;
use App\Repositories\CustomRepositoryInterface;
use App\Repositories\MerchantRepository as MainRepository;
use App\Repositories\WalletTypeRepository;
use App\Repositories\WithdrawTransactionRepository;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $routeName = "api/orders/payment/";
    protected $merchantPrefix = "pipay";
    protected $mainRepository;
    protected $balanceRepository;
    protected $merchantRepository;
    protected $channelRepository;
    protected $depositTransactionRepository;
    protected $callBackLogsRepository;
    protected $url;
    protected $merchantNo;
    protected $apiKey;
    protected $customRepository;
    protected $bankRepository;
    protected $walletTypeRepository;
    protected $walletRepository;
    protected $merchantChannelRepository;
    protected $withdrawTransactionRepository;
    protected $balanceLogsRepository;
    protected $payBillLogsRepository;
    public function __construct(
        MainRepository $mainRepository,
        WithdrawTransactionRepository $withdrawTransactionRepository,
        MerchantChannelRepository $merchantChannelRepository,
        WalletRepository $walletRepository,
        BalanceRepository $balanceRepository,
        ChannelRepository $channelRepository,
        DepositTransactionRepository $depositTransactionRepository,
        BankRepository $bankRepository,
        CallBackLogsRepository $callBackLogsRepository,
        WalletTypeRepository $walletTypeRepository,
        BalanceLogsRepository $balanceLogsRepository,
        CustomRepositoryInterface $CustomRepository,
        PayBillLogsRepository $payBillLogsRepository
    ) {
        $this->mainRepository = $mainRepository;
        $this->channelRepository = $channelRepository;
        $this->depositTransactionRepository = $depositTransactionRepository;
        $this->walletRepository = $walletRepository;
        $this->balanceLogsRepository = $balanceLogsRepository;
        $this->bankRepository = $bankRepository;
        $this->balanceRepository = $balanceRepository;
        $this->callBackLogsRepository = $callBackLogsRepository;
        $this->walletTypeRepository = $walletTypeRepository;
        $this->customRepository = $CustomRepository;
        $this->merchantChannelRepository = $merchantChannelRepository;
        $this->withdrawTransactionRepository = $withdrawTransactionRepository;
        $this->payBillLogsRepository = $payBillLogsRepository;
        $this->url = ApiHelper::getConfigByKey('url');
        $this->merchantNo = ApiHelper::getConfigByKey('merchant_no');
        $this->apiKey = ApiHelper::getConfigByKey('api_key');
    }
    public function index(Request $request)
    {
        $params = $request->all();
        $params['api_name'] = "orders/payment";
        $partnerReference = $params['partnerReference'] ?? [];
        $merchant = $partnerReference['merchant'] ?? "";
        $merchantNo = $merchant['merchant_no'] ?? "";
        if (!$merchantNo) {
            return show_error('require_merchant_no', $params);
        }

        $checkMerchant = $this->mainRepository->findByKey('merchant_no', $merchantNo);
        if (!$checkMerchant) {
            return show_error('merchant_not_found', $params);
        }
        $merchantIsBan = $checkMerchant->is_ban ?? 0;
        if ($merchantIsBan == 1) {
            return show_error('merchant_is_ban', $params);
        }
        $apiKey = $checkMerchant->api_key ?? "";
        if (!$apiKey) {
            return show_error('apikey_not_found', $params);
        }
        $order = $partnerReference['order'] ?? "";
        $orderId = $order['id'] ?? "";
        if (!$orderId) {
            return show_error('require_order_id', $params);
        }
        $params['order_id'] = $orderId;
        $transaction = $params['transaction'] ?? "";
        $amount = $transaction['amount'] ?? "";
        $bankCode = $transaction['bankCode'] ?? "";
        if (!$amount) {
            return show_error('require_order_amount', $params);
        }
        if ($amount <= 0) {
            return show_error('value_invalid', $params);
        }
        if (!is_int($amount)) {
            return show_error('order_amount_not_valid', $params);
        }
        $channel = $transaction['channel'] ?? "";
        if (!$channel) {
            return show_error('require_chanel', $params);
        }
        $checkChannel = $this->channelRepository->checkChannelOnline($channel);
        if (!$checkChannel) {
            return show_error('channel_not_valid', $params);
        }
        $bankChannels = ['bank_qr', 'bank_transfer', 'upacp_pc'];
        if (in_array($channel, $bankChannels)) {
            $checkBankCode = $this->bankRepository->checkBankCodeIsActive($bankCode, $channel);
            if ($checkBankCode == 0) {
                return show_error('bank_code_not_valid', $params);
            }
        }

        $signature = $transaction['signature'] ?? "";
        if (!$signature) {
            return show_error('require_signature', $params);
        }
        if (
            !verify_signature([
                'merchant_no' => $merchantNo,
                'order_id' => $orderId,
                'amount' => $amount,
                'channel' => $channel,
            ], $apiKey, $signature)
        ) {
            return show_error('signature_not_valid', $params);
        }
        $checkDepositTransaction = $this->depositTransactionRepository->findByKey('order_id', $orderId);
        if ($checkDepositTransaction) {
            return show_error('order_id_exist', $params);
        }
        $notificationConfig = $partnerReference['notificationConfig'] ?? [];
        $transactionId = StringHelper::generateUid();
        $urlVnpayPayment = null;
        $expiredAt = null;

        $urlPayment = route($this->routeName . "url", ['transaction_id' => $transactionId, 'merchant_no' => $merchantNo]);
        $depositTransactionStatus = get_setting_value('deposit_default_status');
        $depositTransactionData = [
            'transaction_id' => $transactionId,
            'status' => $depositTransactionStatus,
            'is_maintenance' => get_setting_value('is_maintenance'),
            'is_error' => 0,
            'channel' => $channel,
            'merchant_no' => $merchantNo,
            'order_id' => $orderId,
            'bank_code' => $bankCode,
            'amount' => $amount,
            'signature' => $signature,
            'order_description' => $order['description'] ?? "",
            'url_merchant_notify' => $notificationConfig['notifyUrl'] ?? 'https://devtool.vn/',
            'url_merchant_redirect' => $notificationConfig['redirectUrl'] ?? null,
            'url_payment' => $urlPayment,
            'url_vnpay_payment' => $urlVnpayPayment,
            'params_vnpay_data' => null,
            'response_vnpay_data' => null,
            'expired_at' => $expiredAt,
            'params_merchant_data' => json_encode($params),
        ];
        $depositTransactionInfo = $this->depositTransactionRepository->add($depositTransactionData);
        $sign = md5($this->merchantNo . "|" . $orderId . "|" . $amount . "|" . $channel . "|" . $this->apiKey);
        $arrdata = [
            "merchant_no" => $this->merchantNo,
            "order_no" => $orderId,
            "amount" => $amount,
            "channel" => $channel,
            "bank_code" => $bankCode,
            "notify_url" => 'https://pi-pay.pifinance.info/api/v1/orders/payment/callback',
            "sign" => $sign,

        ];

        $response = null;
        $urlVnpayPayment = null;
        // try {
        //     $client = new \GuzzleHttp\Client(['headers' => ['Content-Type' => 'application/json']]);
        //     $body = json_encode($arrdata);

        //     $response = $client->request('POST', $this->url . 'v1/createOrder', ['body' => $body]);
        //     $response = json_decode($response->getBody(), true);
        //     if (!$response['code'] == 0 && !$response['error'] == 0) {
        //         return show_error('call_api_error', $params);
        //     } else {
        //         $urlVnpayPayment = $response['data'] ?? '#';
        //         $depositTransactionInfoId = $depositTransactionInfo->id ?? "";
        //         $this->depositTransactionRepository->update($depositTransactionInfoId, ['url_vnpay_payment' => $urlVnpayPayment]);
        //     }
        // } catch (\Exception $e) {
        //     $this->depositTransactionRepository->update($depositTransactionInfo->id, ['status' => 'canceled','is_cancel'=>1]);
        //     return show_error('call_api_error', $params);
        // }


        return show_success([
            'transaction' => [
                'transaction_id' => $transactionId,
                'status' => $depositTransactionStatus,
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
        ], $params);
    }
    public function url(Request $request)
    {
        $transactionId = $request->transaction_id;
        $item = $this->depositTransactionRepository->findByKey('transaction_id', $transactionId);
        if (!$item) {
            return "Giao dịch không tồn tại!";
        }
        $status = $item['status'] ?? "";
        $urlMerchantRedirect = $item['url_merchant_redirect'] ?? "";
        if ($status == 'completed') {
            return redirect($urlMerchantRedirect);
        }
        $urlVnpayPayment = $item['url_vnpay_payment'] ?? "";

        return view('api.pages.payment.process', ['urlVnpayPayment' => $urlVnpayPayment]);
        return '<iframe src="https://vnpayapi.yldyd.xyz/api/v1/pay/6e8b7c53e50aba88581861f315d7e8fd4a9ffe4b0ab0db69bb14283b2dbe40bf" style="width:100%;height:100%;border:none;"></iframe>';
        return "Link thanh toán coming soon";
    }
    public function callback(Request $request)
    {
        $params = $request->all();
        $this->callBackLogsRepository->add(['payload' => json_encode($params)]);
        $amount = $params['amount'];
        $slugWithoutSuffix = process_slug($params['channel']);
        $orderTransaction = $this->depositTransactionRepository->findByKey('order_id', $params['order_no']);
        $walletType = $this->walletTypeRepository->findByKey('slug', $slugWithoutSuffix);

        if (!$walletType) {
            $walletType = WalletTypes::where('is_default', 1)->first();
        }

        $merchantNo = $params['merchant_no'];
        $channelSlug = $params['channel'];
        $channel = $this->channelRepository->findByKey('slug', $channelSlug);
        if (!$channel) {
            return show_error('channel_not_valid', $merchantNo);
        }
        $merchant = $this->mainRepository->findByKey('merchant_no', $merchantNo);
        if (!$merchant) {
            return show_error('merchant_not_found', $merchantNo);
        }
        $merchantId = $merchant->id;
        $channelId = $channel->id;
        if ($orderTransaction->status === 'success') {
            return show_error('order_handled', $orderTransaction);
        }
        $merchantChannel = MerchantChannels::where('merchant_id', $merchantId)
            ->where('channel_id', $channelId)
            ->first();
        $checkDepositTransaction = $this->depositTransactionRepository->findByKey('order_id', $params['order_no']);
        if (!$checkDepositTransaction) {
            return show_error('order_id_exist', $params);
        }
        if ($merchantChannel) {
            $feeDeposit = $merchantChannel->fee_deposit;
        } else {
            $feeDeposit = $channel->fee_deposit;
        }
        $amount = $amount - ($amount * ($feeDeposit / 100));

        if ($params['result_code'] === 'success') {
            $wallet = $this->customRepository->updateWallet($merchantId, $walletType->id, $amount);
            $balance = $this->customRepository->updateBalance($merchantId, $walletType->slug, $wallet->balance);
            $resultLog = new BalanceLogs();
            $resultLog->merchant_id = $merchantId;
            $resultLog->wallet_type_id = $walletType->id;
            $resultLog->channel = $slugWithoutSuffix;
            $resultLog->order_id = $params['order_no'];
            $resultLog->type_change = 'deposit';
            $resultLog->old_balance = $wallet->balance - $amount;
            $resultLog->new_balance = $wallet->balance;
            $resultLog->amount = $amount;
            $resultLog->save();
            $depositTransactionStatus = $params['result_code'];
            $orderTransaction->update(['status' => $params['result_code']]);
            $url = $orderTransaction->url_merchant_notify;
            $arrdata = [
                'merchant_id' => $merchantId,
                'order_id' => $params['order_no'],
                'transaction_id' => $orderTransaction->transaction_id,
                'status' => $depositTransactionStatus,
                'channel' => $slugWithoutSuffix,
                'amount' => $amount
            ];
            if (!$url) {
                return show_error('url_callback_not_found', $params);
            }
            ApiHelper::callApi($url, $arrdata, 'POST');
            return show_success([
                'transaction' => [
                    'transaction_id' => $orderTransaction->transaction_id,
                    'status' => $depositTransactionStatus,
                    'old_amount' => $resultLog->old_balance,
                    'amount' => $amount,
                    'new_amount' => $resultLog->new_balance,
                    'order_description' => $orderTransaction->order_description,
                ],
            ], $params);
        }
        return show_success([
            'transaction' => [
                'transaction_id' => $orderTransaction->transaction_id,
                'status' => $params['result_code'],
                'amount' => $amount,
                'order_description' => $orderTransaction->order_description,
            ],
        ], $params);
    }
    public function settingFee(Request $request)
    {

        $params = $request->params;
        $body = $request->body;
        $channelSlug = $body['channel'] ?? "";
        ;
        $tokenKey = $params['token_key'] ?? "";
        $channel = $this->channelRepository->findByKey('slug', $channelSlug);
        if (!$channel) {
            return show_error('channel_not_valid', $body);
        }
        if (!$body['fee_withdraw'] || !$body['fee_deposit'] || !$body['channel'] || !$params['token_key']) {

            return show_error('require_valid', $body);
        }
        if ($body['fee_withdraw'] > $channel->fee_withdraw || $body['fee_deposit'] > $channel->fee_deposit) {
            $arrdata = [
                'fee_withdraw_default' => $channel->fee_withdraw,
                'fee_deposit_default' => $channel->fee_deposit,
            ];
            return show_error('max_default', $arrdata);
        }
        $merchant = $this->mainRepository->findByKey('token_key', $tokenKey);
        if (!$merchant) {
            return show_error('merchant_not_found', $params);
        }
        $apiKey = $merchant->api_key ?? "";
        if (!$apiKey) {
            return show_error('apikey_not_found', $params);
        }
        $sinature = $params['signature'] ?? '';
        if (!verify_signature($params, $apiKey, $sinature)) {
            return show_error('signature_not_valid', $params);
        }
        $merchantChannel = $this->merchantChannelRepository->checkExistsWithSlug($merchant->id, $channel->slug);
        if ($merchantChannel) {
            $merchantChannel->update(['fee_withdraw' => $body['fee_withdraw']]);
            $merchantChannel->update(['fee_deposit' => $body['fee_deposit']]);
            $merchantChannel->update(['slug' => $body['channel']]);
            return show_success($merchantChannel);
        }
        $arrData = [
            'merchant_id' => $merchant->id,
            'channel_id' => $channel->id,
            'fee_withdraw' => $body['fee_withdraw'],
            'fee_deposit' => $body['fee_deposit'],
            'slug' => $body['channel'],
            'name' => $channel->name
        ];
        $newMerchantChannel = $this->merchantChannelRepository->add($arrData);
        return show_success($newMerchantChannel);
    }
    public function withdraw(Request $request)
    {
        $params = $request->all();
        $merchant = $this->mainRepository->findByKey('merchant_no', $params['merchant_no']);
        if ($params['amount'] <= 0) {
            return show_error('value_invalid', $params);
        }
        if (!$params['amount'] || !$params['merchant_no'] || !$params['order_id'] || !$params['channel'] || !$params['wallet_type_id']) {

            return show_error('require_valid', $params);
        }
        if (!$merchant) {
            return show_error('merchant_not_found', $params);
        }
        $verify_signature_value = [
            'merchant_no' => $params['merchant_no'],
            'order_id' => $params['order_id'],
            'amount' => $params['amount'],
            'channel' => $params['channel'],
        ];
        $sinature = $params['signature'] ?? '';
        if (!verify_signature($verify_signature_value, $merchant->api_key, $sinature)) {
            return show_error('signature_not_valid', $verify_signature_value);
        }
        $merchantId = $this->mainRepository->findByKey('merchant_no', $params['merchant_no'])->id;

        $merchantChannel = $this->merchantChannelRepository->checkExistsWithSlug($merchant->id, $params['channel']);
        if (!$merchantChannel) {
            $merchantChannel = $this->channelRepository->findByKey('slug', $params['channel']);
        }
        $channel = process_slug($params['channel']);
        $wallet = $this->walletRepository->checkExists($merchantId, $params['wallet_type_id']);
        $balances = $this->balanceRepository->findByKey('merchant_id', $merchantId);
        if ($wallet->balance < get_setting_value('minimum_balance') || $balances->$channel < get_setting_value('minimum_balance')) {
            return show_error('not_enough_minimun_balance');
        }
        if ($wallet->balance < $params['amount'] || $balances->$channel < $params['amount']) {
            return show_error('not_enough_balance');
        }
        $channel = $this->channelRepository->findByKey('slug', $params['channel']);
        $arrayData = [
            'transaction_id' => StringHelper::generateUid(),
            'merchant_id' => $params['merchant_no'],
            'channel_slug' => $params['channel'],
            'channel_id' => $channel->id,
            'amount' => $params['amount'],
            'note' => $params['note'],
            'fee' => $merchantChannel->fee_withdraw,
            'order_id' => $params['order_id'],
            'wallet_type_id' => $params['wallet_type_id'],
        ];

        $withdrawTrans = $this->withdrawTransactionRepository->add($arrayData);
        $withdrawTransactionResult = $this->withdrawTransactionRepository->findByKey('transaction_id', $withdrawTrans->transaction_id);
        return show_success($withdrawTransactionResult);
    }
    public function withdrawUpdate(Request $request)
    {
        $params = $request->all();
        $transaction = $params['transaction'] ?? '';
        $status = $params['status'] ?? '';
        if (!$transaction || !$status) {
            return show_error('require_valid', $params);
        }
        if ($status !== 'accepted' && $status !== 'rejected') {
            // Trả về thông báo 'status not found'
            return show_error('status_not_found', $params);
        }
        $withdrawTrans = $this->withdrawTransactionRepository->findByKey('transaction_id', $transaction);
        if (!$withdrawTrans) {
            return show_error('transaction_not_found', $params);
        }
        $merchantId = $this->mainRepository->findByKey('merchant_no', $withdrawTrans->merchant_id)->id;
        if ($status === 'rejected') {
            $withdrawTrans->status = $status;
            if (!$params['reject_reason']) {
                return show_error('require_valid', $params);
            }
            $withdrawTrans->reject_reason = $params['reject_reason'];
            $withdrawTrans->save();
        }
        if ($status === 'accepted') {
            if ($withdrawTrans->status === 'accepted') {
                return show_error('transaction_handled', $params);
            }
            ;
            $amount = $withdrawTrans->amount - ($withdrawTrans->amount * ($withdrawTrans->fee / 100));
            $wallet = $this->walletRepository->checkExists($merchantId, $withdrawTrans->wallet_type_id);
            $oldBalance = $wallet->balance;
            $newBalance = $wallet->balance - $amount;
            $channel = process_slug($withdrawTrans->channel_slug);
            $balances = $this->balanceRepository->findByKey('merchant_id', $merchantId);
            if ($wallet->balance < $amount || $balances->$channel < $amount) {
                return show_error('not_enough_balance');
            }

            $wallet->balance = $newBalance;
            $wallet->save();

            $balances->$channel = $newBalance;
            $balances->save();
            $arrAdd = [
                'old_balance' => $oldBalance,
                'channel' => $channel,
                'new_balance' => $newBalance,
                'order_id' => $withdrawTrans->order_id,
                'type_change' => 'withdraw',
                'amount' => $amount,
                'wallet_type_id' => $withdrawTrans->wallet_type_id,
                'merchant_id' => $merchantId,
            ];
            $balanceLogs = $this->balanceLogsRepository->add($arrAdd);
            $withdrawTrans->update([
                'status' => $status,
                'note' => $params['note'] ?? null,
                'reject_reason' => '',
            ]);
        }
        return show_success($withdrawTrans, $params);
    }
    public function getBill(Request $request)
    {

        $customer_code = $request->input('customer_code', '');
        $service_code = $request->input('service_code', '');
        $publisher = $request->input('publisher', '');
        if (empty($customer_code) || empty($service_code) || empty($publisher)) {
            return response()->json([
                'status' => '400',
                'error' => 'Required customer code, service code, and publisher',
            ], 200);
        }
        $data = [
            'mc_request_id' => 'BILL-1563261' . StringHelper::generateNumber(),
            'customer_code' => $customer_code,
            'publisher' => $publisher,
            'service_code' => $service_code,
        ];

        $jsonString = json_encode($data, JSON_UNESCAPED_SLASHES);
        $MC_ENCRYPT_KEY = env('MC_ENCRYPT_KEY');
        $MC_CODE = env('MC_CODE');
        $MC_CHECKSUM_KEY = env('MC_CHECKSUM_KEY');
        $encryptedData = ApiHelper::Encrypt($jsonString, $MC_ENCRYPT_KEY);
        $md5str = $MC_CODE . $encryptedData . $MC_CHECKSUM_KEY;
        $checksum = md5($md5str);
        $fnc = 'querybill';
        $params = [
            'fnc' => $fnc,
            'merchantcode' => $MC_CODE,
            'data' => $encryptedData,
            'checksum' => $checksum,
        ];
        $client = new Client(['headers' => ['Content-Type' => 'application/x-www-form-urlencoded']]);
        $url = env('URL_PAYBILL_WATER_ELECTRIC') . $fnc;
        $response = $client->request('POST', $url, [
            'form_params' => $params,
            'headers' => [
                'Authorization' => 'Basic bWNhcGk6bWVyY2hhbnRhcGl0ZXN0aW5n',
            ]
        ]);
        $statusCode = $response->getStatusCode();
        $responseData = json_decode($response->getBody(), true);
        $formattedData = ApiHelper::formatResponseGetBill($responseData);
        return response()->json($formattedData, $statusCode);
    }
    public function payBill(Request $request)
{
    $billNumber = $request->input('bill_number', '');
    $amount = (float) $request->input('amount');
    $period = $request->input('period', '');
    $customer_code = $request->input('customer_code', '');
    $publisher = $request->input('publisher', '');
    $service_code = $request->input('service_code', '');
    $api_key = $request->input('api_key', '');
    $signature = $request->input('signature', '');

    // Tìm merchant theo api_key
    $merchant = $this->mainRepository->findByKey('api_key', $api_key);
    if (!$merchant) {
        return response()->json(['status' => '400', 'error' => 'merchant_not_found'], 200);
    }

    // Kiểm tra các tham số đầu vào
    if (empty($billNumber) || empty($amount) || empty($period) || empty($customer_code) || empty($publisher) || empty($service_code)) {
        return response()->json(['status' => '400', 'error' => 'Required parameters are missing'], 200);
    }

    // Xác minh chữ ký
    $verify_signature_value = [
        'merchant_no' => $merchant->merchant_no,
        'order_id' => $billNumber,
        'amount' => $amount,
    ];
    if (!verify_signature($verify_signature_value, $api_key, $signature)) {
        return response()->json(['status' => '400', 'error' => 'signature_not_valid'], 200);
    }

    // Tạo dữ liệu yêu cầu
    $fnc = 'paybill';
    $request_id = 'BILL-1563261' . StringHelper::generateNumber();
    $bill_payment = [
        "billNumber" => $billNumber,
        "period" => $period,
        "amount" => $amount,
        "otherInfo" => "",
    ];
    if ($service_code === "BILL_ELECTRIC") {
        $bill_payment["billType"] = "TD";
    }

    $data = [
        'mc_request_id' => $request_id,
        'service_code' => $service_code,
        'publisher' => $publisher,
        'customer_code' => $customer_code,
        "bill_payment" => [$bill_payment],
    ];

    $jsonString = json_encode($data, JSON_UNESCAPED_SLASHES);
    $MC_ENCRYPT_KEY = env('MC_ENCRYPT_KEY');
    $MC_CHECKSUM_KEY = env('MC_CHECKSUM_KEY');
    $MC_CODE = env('MC_CODE');
    $encrypt = ApiHelper::Encrypt($jsonString, $MC_ENCRYPT_KEY);
    $md5str = $MC_CODE . $encrypt . $MC_CHECKSUM_KEY;
    $checksum = md5($md5str);
    $params = [
        'fnc' => $fnc,
        'merchantcode' => $MC_CODE,
        'data' => $encrypt,
        'checksum' => $checksum,
    ];

    $client = new Client(['headers' => ['Content-Type' => 'application/x-www-form-urlencoded']]);
    $url = env('URL_PAYBILL_WATER_ELECTRIC') . $fnc;

    $response = $client->request('POST', $url, [
        'form_params' => $params,
        'headers' => [
            'Authorization' => 'Basic bWNhcGk6bWVyY2hhbnRhcGl0ZXN0aW5n',
        ]
    ]);
    $statusCode = $response->getStatusCode();
    $responseData = json_decode($response->getBody(), true);

    // Lưu log giao dịch
    $logData = [
        'merchant_id' => $merchant->id,
        'bill_number' => $billNumber,
        'amount' => $amount,
        'type_bill' => $service_code === "BILL_ELECTRIC" ? "TD" : null,
        'signature' => generate_signature($verify_signature_value, $api_key),
        'customer_code' => $customer_code,
        'transaction_id' => StringHelper::generateUid()
    ];
    $this->payBillLogsRepository->add($logData);

    // Kiểm tra error_code
    if ($responseData['error_code'] === "00") {
        $wallets = $this->walletRepository->getDataByKey('merchant_id', $merchant->id);
        $selectedWallet = null;
        foreach ($wallets as $wallet) {
            if ($wallet['wallet_type_id'] == 5 && floatval($wallet['balance']) > $amount) {
                $selectedWallet = $wallet;
                break;
            }
        }

        if (!$selectedWallet) {
            foreach ($wallets as $wallet) {
                if (floatval($wallet['balance']) > $amount) {
                    $selectedWallet = $wallet;
                }
            }
        }
        if (!$selectedWallet) {
            return show_error('not_enough_balance');
        }
        $channel = WalletTypes::where('id', $selectedWallet->wallet_type_id)->first();
        $walletExist = $this->walletRepository->checkExists($merchant->id, $selectedWallet->wallet_type_id);
        $oldBalance = $walletExist->balance;
        $newBalance = $oldBalance - $amount;
        $walletExist->balance = $newBalance;
        $walletExist->save();
        $arrAdd = [
            'old_balance' => $oldBalance,
            'channel' => $channel->slug,
            'new_balance' => $newBalance,
            'order_id' => $billNumber,
            'type_change' => $service_code === "BILL_ELECTRIC" ? " pay-bill-TD" : 'pay-bill-TN',
            'amount' => $amount,
            'wallet_type_id' => $selectedWallet->wallet_type_id,
            'merchant_id' => $merchant->id,
        ];
        $this->balanceLogsRepository->add($arrAdd);
        return show_success($responseData);
    } else {
        return show_error('Somethings wrong. Please Try again',$responseData['error_message']);
    }
}

    public function getPubliser(Request $request)
    {
        $service_code = $request->input('service_code', '');
        $dataElectric = (object) [
            "EVN" => "Điện lực Hà Nội",
            "EVNHCM" => "Điện lực Hồ Chí Minh",
            "EVNNPC" => "Điện lực Miền Bắc",
            "EVNCPC" => "Điện lực Miền Trung",
            "EVNSPC" => "Điện lực Miền Nam"
        ];

        $dataWatter = (object) [
            "HCMTA" => "Cty nước Trung An – TP.HCM",
            "HCMCLO" => "Cty nước Chợ Lớn – TP.HCM",
            "HCMNT" => "Cty nước Nông thôn – TP.HCM",
            "NBE" => "Cty nước Nhà Bè – TP.HCM",
            "DNI" => "Cty nước Đồng Nai",
            "HCMBT" => "Cty nước Bến Thành – TP.HCM",
            "HCMGD" => "Cty nước Gia Định – TP.HCM",
            "HUE" => "Cty nước Huế",
            "HCMUT" => "Cty nước Phú Hòa Tân – TP.HCM",
            "HCMTH" => "Cty nước Tân Hòa – TP.HCM",
            "HCMTD" => "Cty nước Thủ Đức – TP.HCM"
        ];
        if ($service_code === 'BILL_ELECTRIC') {
            return response()->json([
                'status' => '200',
                'message' => 'Success',
                'data' => $dataElectric,
            ], 200);
        }
        return response()->json([
            'status' => '200',
            'message' => 'Success',
            'data' => $dataWatter,
        ], 200);
    }
}
