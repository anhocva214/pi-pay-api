<?php

namespace App\Http\Controllers\Api\General;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\StringHelper;
use App\Repositories\ChannelRepository;
use App\Repositories\MerchantRepository as MainRepository;
use Illuminate\Support\Str;

class GenerateController  extends Controller
{
    protected $routeName = "api/merchant/general/";
    protected $merchantPrefix = "pipay";
    protected $mainRepository;
    protected $balanceRepository;
    protected $merchantRepository;
    protected $channelRepository;
    public function __construct(MainRepository $mainRepository, ChannelRepository $channelRepository)
    {
        $this->mainRepository = $mainRepository;
        $this->channelRepository = $channelRepository;
    }
    public function signatureWithMerchantNo(Request $request)
    {
        $params = $request->all();
        $merchantNo = $params['merchant_no'] ?? "";
        $merchant = $this->mainRepository->findByKey('merchant_no', $merchantNo);
        if (!$merchant) {
            return show_error('Không tìm thấy merchant',$params);
        }
        
        $apiKey = $merchant->api_key ?? "";
        if(!$apiKey) {
            return show_error('Không tìm thấy apikey',$params);
        }
        return generate_signature($params,$apiKey);
    }
}
