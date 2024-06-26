<?php
namespace App\Http\Controllers\Api\Merchant;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\StringHelper;
use App\Http\Requests\GetApiKeyRequest;
use App\Models\Balances;
use App\Models\Merchants;
use App\Repositories\BalanceRepository;
use App\Repositories\MerchantRepository;
use App\Repositories\WalletRepository as MainRepository;
use Illuminate\Support\Str;
class WalletController  extends Controller
{
    protected $routeName = "api/merchant/wallet/";
    protected $merchantPrefix = "pipay";
    protected $mainRepository;
    protected $balanceRepository;
    protected $merchantRepository;
    public function __construct(MainRepository $mainRepository, MerchantRepository $merchantRepository)
    {
        $this->mainRepository = $mainRepository;
   
        $this->merchantRepository = $merchantRepository;
    }
    public function index(Request $request)
    {
       
       
    }
    public function assignToMerchant(Request $request) {
        $params = $request->all();
        $tokenKey = $params['token_key'] ?? "";
        if(!$tokenKey) {
            return show_error('Chưa nhập Token Key',$params);
        }
        $merchant = $this->merchantRepository->findByKey('token_key',$tokenKey);
        if(!$merchant) {
            return show_error('Merchant không tồn tại',$params);
        }
        $merchantId = $merchant->id ?? "";
        $idsAssign = $this->mainRepository->assignWalletTypesToMerchant($merchantId);
        return $idsAssign;
    }
}
