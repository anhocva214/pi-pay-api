<?php

namespace App\Http\Controllers\Api\Orders;

use App\Repositories\MerchantRepository;
use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\WalletTypes;
use App\Repositories\BalanceLogsRepository;
use App\Repositories\ResultLogsRepository;
use App\Repositories\VoucherMerchantRepository;
use App\Repositories\WalletRepository;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    protected $merchantRepository;
    protected $resultLogRepository;
    protected $walletRepository;
    protected $balanceLogsRepository;
    protected $vouchermerchantRepository;
    public function __construct(
        MerchantRepository $merchantRepository,
        ResultLogsRepository $resultLogRepository,
        WalletRepository $walletRepository,
        BalanceLogsRepository $balanceLogsRepository,
        VoucherMerchantRepository $vouchermerchantRepository
    ) {
        $this->merchantRepository = $merchantRepository;
        $this->resultLogRepository = $resultLogRepository;
        $this->walletRepository = $walletRepository;
        $this->balanceLogsRepository = $balanceLogsRepository;
        $this->vouchermerchantRepository = $vouchermerchantRepository;
    }
    public function getProducts(Request $request)
    {
        $params = $request->all();


        $page = $params['page'] ?? '';
        $pageSize = $params['pageSize'] ?? '';
        $minPrice = $params['minPrice'] ?? 1;
        $maxPrice = $params['maxPrice'] ?? 10000000;
        $token_gotit = env('TOKEN_AUTHORIZE_GOTIT');
        $client = new Client();
        $url = env('URL_GOTIT_LIST_VOUCHER');
        $params = [
            'query' => [
                'categoryId' => 10,
                'page' => $page,
                'pageSize' => $pageSize,
                'isExcludeStoreListInfo' => false,
                'storeListPage' => 1,
                'storeListPageSize' => 2
            ],
            'headers' => [
                'X-GI-Authorization' => $token_gotit,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'orderBy' => 'asc',
                'pagination' => [
                    'pageSize' => 10,
                    'page' => 1,
                    'pageTotal' => 1
                ],
                'storeListPagination' => [
                    'page' => 1,
                    'pageSize' => 5
                ]
            ]
        ];

        $response = $client->get($url, $params);

        if ($response->getStatusCode() == 200) {

            $responseData = json_decode($response->getBody()->getContents(), true);
            if ($responseData['error'] !== '') {
                $error = $responseData['error'];
                return show_error('error_not_found');
            }
            $filteredProducts = [];
            // foreach ($responseData['data'] as $productList) {
            //     foreach ($productList['productList'] as $product) {
            //         if ($product['productId'] == 1408) {
            //             $filteredProducts[] = $product;
            //         }
            //     }
            // }
            foreach ($responseData['data'] as $productList) {
                foreach ($productList['productList'] as $product) {
                    if ($product['productId'] == 1408) {
                        foreach ($product['prices'] as $price) {
                            // Prepare data for insertion or update
                            $voucherData = [
                                'productId' => $product['productId'],
                                'productNm' => $product['productNm'],
                                'productDesc' => $product['productDesc'],
                                'productShortDesc' => $product['productShortDesc'],
                                'priceId' => $price['priceId'],
                                'priceValue' => $price['priceValue'],
                            ];

                            // Check if the product already exists in the database by productId and priceId
                            $existingVoucher = Voucher::where('productId', $product['productId'])
                                ->where('priceId', $price['priceId'])
                                ->first();

                            if ($existingVoucher) {
                                // Update existing record
                                Voucher::where('productId', $product['productId'])
                                    ->where('priceId', $price['priceId'])
                                    ->update($voucherData);
                            } else {
                                // Insert new record
                                Voucher::insert($voucherData);
                            }
                        }
                    }
                }
            }
            return show_success($filteredProducts);
        }
        return response()->json([
            'error' => 'Unexpected HTTP status: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase()
        ], $response->getStatusCode());
    }
    public function getVoucherFromDB(Request $request)
    {
    
        $page = $request->query('page') ?? '';
        $pageSize = $request->query('pageSize') ?? '';
        $minPrice = $request->query('minPrice', 0); // Giá trị mặc định là 0 nếu không được cung cấp
        $maxPrice = $request->query('maxPrice', 10000000); // Giá trị mặc định là 10,000,000 nếu không được cung cấp
        if (!$page || !$pageSize) {
            return show_error('require_valid', $request->all());
        }
        $this->getProducts($request);
    
        $vouchers = Voucher::where('priceValue', '>=', $minPrice)
            ->where('priceValue', '<=', $maxPrice)
            ->orderBy('productId')
            ->orderBy('priceId')
            ->paginate($pageSize, ['*'], 'page', $page);

        return show_success($vouchers);
    }
    public function createVoucher(Request $request)
    {
        $params = $request->all();

        $productId = $params['productId'] ?? '';
        $productPriceId = $params['productPriceId'] ?? '';
        $expiryDate = $params['expiryDate'] ?? '';
        $orderName = $params['orderName'] ?? '';
        $phone = $params['phone'] ?? '';
        $transactionRefId = '000578_' . Str::uuid();
        $token_gotit = env('TOKEN_AUTHORIZE_GOTIT');
        $api_key = $params['api_key'] ?? '';
        $signature = $params['signature'] ?? '';
        if (!$api_key || !$productId || !$productPriceId || !$expiryDate || !$orderName || !$phone || !$signature) {
            return show_error('require_valid', $params);
        }
        $merchant_no = $this->merchantRepository->findByKey('api_key', $api_key);
        if (!$merchant_no) {
            return show_error('merchant_not_found', $api_key);
        }
        $voucher=Voucher::where('productId',$productId)->where('priceId',$productPriceId)->first();
        $value=$voucher->priceValue;
        $verify_signature_value = [
            'merchant_no' => $merchant_no->merchant_no,
            'token_key' => $merchant_no->token_key,
            'amount' =>  $productPriceId,
        ];
        if (!verify_signature($verify_signature_value, $merchant_no->api_key, $signature)) {
            return show_error('signature_not_valid', $verify_signature_value);
        }
        $wallets = $this->walletRepository->getDataByKey('merchant_id', $merchant_no->id);
        $selectedWallet = null;
        foreach ($wallets as $wallet) {
            if ($wallet['wallet_type_id'] == 5 && floatval($wallet['balance']) > $value) {
                $selectedWallet = $wallet;
                break;
            }
        }

        if (!$selectedWallet) {
            foreach ($wallets as $wallet) {
                if (floatval($wallet['balance']) > $value) {
                    $selectedWallet = $wallet;
                }
            }
        }
        if (!$selectedWallet) {
            return show_error('not_enough_balance');
        }
        $body = json_encode([
            "productId" => $productId,
            "productPriceId" => $productPriceId,
            "quantity" => 1,
            "expiryDate" => $expiryDate,
            "orderName" => $orderName,
            "transactionRefId" => $transactionRefId,
            "isConvertToCoverLink" => 0,
            "use_otp" => 0,
            "otp_type" => 1,
            "password" => "88888",
            "receiver_name" => "Client",
            "phone" => $phone
        ]);
        $client = new Client();
        $url = env('URL_GOTIT_CREATE_VOUCHER');

        $response = $client->post($url . '/v', [
            'headers' => [
                'X-GI-Authorization' => $token_gotit,
                'Content-Type' => 'application/json',
            ],
            'body' => $body
        ]);

        $responseBody = $response->getBody()->getContents();
        $responseData = json_decode($responseBody, true);

        if ($responseData['error'] !== '') {
            $error = $responseData['error'];
            return show_error('call_api_error');
        }
        $walletExist = $this->walletRepository->checkExists($merchant_no->id, $selectedWallet->wallet_type_id);
        $oldBalance = $walletExist->balance;
        $newBalance = $oldBalance - ($value);
        $walletExist->balance = $newBalance;
        $walletExist->save();
        $channel = WalletTypes::where('id', $selectedWallet->wallet_type_id)->first();
        $arrAdd = [
            'old_balance' => $oldBalance,
            'channel' => $channel->slug,
            'new_balance' => $newBalance,
            'order_id' => $transactionRefId,
            'type_change' => 'create-voucher',
            'amount' => $value,
            'wallet_type_id' => $selectedWallet->wallet_type_id,
            'merchant_id' => $merchant_no->id,
        ];
        $this->balanceLogsRepository->add($arrAdd);
        // Dữ liệu để luu vào bảng voucher merchant log
        $orderName = $responseData['data'][0]['orderName'] ?? null;
        $voucherCoverLink = $responseData['data'][0]['vouchers'][0]['voucherCoverLink'] ??'';
        $voucherCoverLinkCode = $responseData['data'][0]['vouchers'][0]['voucherCoverLinkCode'] ??'';
       
        if( $voucherCoverLink ==''){
            $voucherCoverLink= $responseData['data'][0]['vouchers'][0]['voucherLink'];
        }
         // Lấy mã code voucher
         $linkVoucher=$voucherCoverLink ;
         $path = parse_url($linkVoucher, PHP_URL_PATH);
         $lastCodeVoucher = basename($path);
         //end Lấy mã code voucher

        if($voucherCoverLinkCode==''){
            $voucherCoverLinkCode =  $lastCodeVoucher;
        }
        $voucherSerial = $responseData['data'][0]['vouchers'][0]['voucherSerial'] ??'';
        //end
        $voucherLog = [
            'voucherName' => $orderName,
            'transactionId' => $transactionRefId,
            'voucher_link' => $voucherCoverLink,
            'voucher_code' => $voucherCoverLinkCode,
            'voucher_serial' => $voucherSerial,
            'amount' => $value,
            'signature' => $signature,
            'expiryDate' => $expiryDate,
            'merchant_no' => $merchant_no->merchant_no,
        ];
        $this->vouchermerchantRepository->add($voucherLog);
        return $responseData;
    }
}
