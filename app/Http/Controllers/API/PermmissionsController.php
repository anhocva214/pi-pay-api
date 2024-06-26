<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetApiKeyRequest;
use App\Models\Balances;
use App\Models\Merchants;
use App\Repositories\MerchantRepository as MainRepository;

class PermmissionsController extends Controller
{
    protected $routeName = "api/permmissions";
    protected $mainRepository;
    public function __construct(MainRepository $mainRepository)
    {
        $this->mainRepository = $mainRepository;
    }
    public function index(Request $request)
    {
        $msg = $request->msg;
        if(!$msg) {
            $msg = "Bạn không có quyền truy cập";
        }
        return [
            'status' => 400,
            'msg' => $msg
        ];

    }
}
