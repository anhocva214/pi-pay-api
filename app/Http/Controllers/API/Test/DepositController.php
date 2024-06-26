<?php
namespace App\Http\Controllers\Api\Test;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\StringHelper;
use App\Repositories\ChannelRepository as MainRepository;
use Illuminate\Support\Str;
class DepositController  extends Controller
{
    protected $routeName = "api/test/deposit/";
    protected $merchantPrefix = "pipay";
    protected $mainRepository;
    protected $balanceRepository;
    protected $merchantRepository;
    public function __construct(MainRepository $mainRepository)
    {
        $this->mainRepository = $mainRepository;
    }
    public function index(Request $request)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-auto-transfer.chidetest.com/api/v1/swap',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"token_address_from":"0x55d398326f99059ff775485246999027b3197955","token_address_to":"0x3e007b3cc775c4bd1600693aad7fac0685353272","amount":100,"key":95317}
        ',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        echo $response;
    }
}
