<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Repositories\MerchantChannelRepository;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\StringHelper;
use App\Http\Requests\GetApiKeyRequest;
use App\Models\Balances;
use App\Models\Channels;
use App\Models\MerchantChannels;
use App\Models\Merchants;
use App\Repositories\BalanceRepository;
use App\Repositories\ChannelRepository;
use App\Repositories\MerchantRepository as MainRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Str;

class InfoController extends Controller
{
    protected $routeName = "api/merchant/info/";
    protected $merchantPrefix = "pipay";
    protected $mainRepository;
    protected $balanceRepository;
    protected $walletRepository;
    protected $merchantChannelRepository;
    protected $channelRepository;
    public function __construct(
        MainRepository $mainRepository,
        MerchantChannelRepository $merchantChannelRepository,
        BalanceRepository $balanceRepository,
        WalletRepository $walletRepository,
        ChannelRepository $channelRepository
    ) {
        $this->mainRepository = $mainRepository;
        $this->balanceRepository = $balanceRepository;
        $this->walletRepository = $walletRepository;
        $this->merchantChannelRepository = $merchantChannelRepository;
        $this->channelRepository=$channelRepository;
    }
    public function index(Request $request)
    {
        $params = $request->all();
        $tokenKey = $params['token_key'] ?? "";
        $merchant = $this->mainRepository->findByKey('token_key', $tokenKey);
        if (!$merchant) {
            return show_error('merchant_not_found', $params);
        }
        $apiKey = $merchant->api_key ?? "";
        if (!$apiKey) {
            return show_error('apikey_not_found', $params);
        }
        #_Tạo chữ ký
        // sign = token_key +"|"+key
        $sinature = $params['signature'] ?? '';
        if (!verify_signature($params, $apiKey, $sinature)) {
            return show_error('signature_not_valid', $params);
        }
        return show_success($merchant, $params);
    }
    public function generateApiKey(Request $request)
    {
        $params = $request->all();
        $merchantName = $params['merchant_name'] ?? "";
        if (!$merchantName) {
            return show_error('require_merchant_name', $params);
        }
        $tokenKey = $params['token_key'] ?? "";
        $merchant = $this->mainRepository->findByKey('token_key', $tokenKey);
        $errors = [];
        if ($merchant) {
            return show_error('token_key_exist', $params);
        }
        $ipWhitelist = $params['ip_whitelist'] ?? null;
        $params['ip_whitelist'] = $ipWhitelist;
        $insertMerchant = $this->handleAddItem($params);
        return show_success([
            'merchant_no' => $insertMerchant['merchant_no'] ?? "",
            'api_key' => $insertMerchant['api_key'] ?? "",
            'ip_whitelist' => $ipWhitelist,
            'balance_info' => $insertMerchant['balance_info'] ?? [],
        ], $params);
    }
    public function getBalance(Request $request)
    {
        $params = $request->all();
        $tokenKey = $params['token_key'] ?? "";
        $merchant = $this->mainRepository->findByKey('token_key', $tokenKey);
        if (!$merchant) {
            return show_error('merchant_not_found', $params);
        }
        $apiKey = $merchant->api_key ?? "";
        if (!$apiKey) {
            return show_error('apikey_not_found', $params);
        }
        #_Tạo chữ ký
        // sign = token_key +"|"+key
        $sinature = $params['signature'] ?? '';
        if (!verify_signature($params, $apiKey, $sinature)) {
            return show_error('signature_not_valid', $params);
        }

        $wallets = $merchant->wallets()->select('wallet_name', 'balance')->get();
        if ($wallets->isEmpty()) {
            return show_error('balance_not_valid', $params);
        }
        return show_success([
            'balance_info' => $wallets,
            'merchant_no' => $merchant->merchant_no ?? "",
            'merchant_name' => $merchant->name ?? "",
        ], $params);
    }
    public function handleAddItem($params)
    {
        #_Add merchant
        $apiKey = StringHelper::generateApiKey();
        $merchantNo = StringHelper::generateMerchantNo();
        $merchant = $this->mainRepository->add([
            'name' => $params['merchant_name'] ?? null,
            'token_key' => $params['token_key'] ?? null,
            'ip_whitelist' => $params['ip_whitelist'] ?? null,
            'api_key' => $apiKey,
            'merchant_no' => $merchantNo,
        ]);
        $merchantId = $merchant->id ?? "";
        #_Add balance
        $balance = null;
        $balanceId = null;
        $balanceInfo = null;
        if ($merchantId) {
            $merchantInfo = $this->mainRepository->findById($merchantId);
            $this->walletRepository->assignWalletTypesToMerchant($merchantId);
            $balanceInfo = $merchantInfo->wallets()->select('wallet_name', 'balance')->get();
        }
        return [
            'merchant_id' => $merchantId,
            'balance_info' => $balanceInfo,
            'api_key' => $apiKey,
            'merchant_no' => $merchantNo,
        ];
    }
    public function generateSignature(Request $request)
    {
        $params = $request->all();
        $tokenKey = $params['token_key'] ?? "";
        $merchant = $this->mainRepository->findByKey('token_key', $tokenKey);
        if (!$merchant) {
            return show_error('Không tìm thấy merchant', $params);
        }

        $apiKey = $merchant->api_key ?? "";
        if (!$apiKey) {
            return show_error('Không tìm thấy apikey', $params);
        }
        return generate_signature($params, $apiKey);
    }
    public function channel(Request $request)
    {
        $params = $request->all();
        $token_key = $params['token_key'] ?? " ";
        $merchantInfo = $this->mainRepository->findByKey('token_key', $token_key);
        if (!$merchantInfo) {
            return show_error('merchant_not_found', $params);
        }
        $merchantChannels =$this->merchantChannelRepository->getDataByKey('merchant_id', $merchantInfo->id);

        if ($merchantChannels) {
            $existingChannelIds = $merchantChannels->pluck('channel_id')->toArray();
            $channels =$this->channelRepository->checkKeyNotInArray('id',$existingChannelIds); 
            $combinedObjects = [];
            foreach ($merchantChannels as $merchantChannel) {
                $combinedObjects[] = $merchantChannel;
            }
            foreach ($channels as $channel) {
                $combinedObjects[] = $channel;
            }

            return show_success($combinedObjects, $params);
        }
        return show_success($merchantChannels, $params);
    }
}
