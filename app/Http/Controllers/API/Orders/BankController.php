<?php

namespace App\Http\Controllers\API\Orders;

use App\Repositories\BankRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\MerchantRepository;

class BankController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $mainRepository;
    protected $bankRepository;

    public function __construct(MerchantRepository $mainRepository, BankRepository $bankRepository)
    {
        $this->mainRepository = $mainRepository;
        $this->bankRepository = $bankRepository;
    }
    public function index(Request $request)
    {
        $params = $request->all();
        $token_key = $params['token_key'] ?? " ";
        if (!$token_key) {
            return show_error('require_token');
        }
        $merchant = $this->mainRepository->findByKey('token_key', $token_key);
        if (!$merchant) {
            return show_error('merchant_not_found');
        }
        $banks = $this->bankRepository->all();
        return show_success($banks, $params);
    }
    public function show(Request $request)
    {
        $params = $request->all();
        $token_key = $params['token_key'] ?? " ";
        $bank_code=$params['bank_code']?? " ";
        if (! $token_key) {
            return show_error('require_token', $params);
        }
        if (!$bank_code) {
            return show_error('require_valid', $params);
        }
        $merchant = $this->mainRepository->findByKey('token_key',  $token_key);
        if (!$merchant) {
            return show_error('merchant_not_found', $params);
        }
        $banks = $this->bankRepository->findByKey('bank_code', $bank_code);
        if (!$banks) {
            return show_error('bankcode_not_valid', $params);
        }
        return  show_success($banks, $params);;
    }

}
