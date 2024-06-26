<?php

use App\Http\Controllers\API\Orders\VoucherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('v1')->group(function () {
    $prefix = "permmissions";
    Route::prefix($prefix)->as($prefix . '/')->group(function () use ($prefix) {
        $controller = ucfirst($prefix) . "Controller@";
        Route::get('/',  $controller . 'index')->name('index');
    });
    $prefix = "general";
    Route::prefix($prefix)->as($prefix . '/')->group(function () {
        $prefix = 'generate';
        Route::prefix($prefix)->namespace('General')->as($prefix . '/')->group(function () use ($prefix) {
            $controller = ucfirst($prefix) . "Controller@";
            Route::get('/signatureWithMerchantNo',  $controller . 'signatureWithMerchantNo')->name('signatureWithMerchantNo');
        });
        $prefix = 'channels';
        Route::prefix($prefix)->namespace('General')->as($prefix . '/')->group(function () use ($prefix) {
            $controller = ucfirst($prefix) . "Controller@";
            Route::get('/',  $controller . 'index')->name('index');
        });
    });

    Route::middleware('check.tokenKey')->group(function () {
        $prefix = "merchant";
        Route::prefix($prefix)->as($prefix . '/')->group(function () {
            $prefix = 'info';
            Route::prefix($prefix)->namespace('Merchant')->as($prefix . '/')->group(function () use ($prefix) {
                $controller = ucfirst($prefix) . "Controller@";
                Route::get('/',  $controller . 'index')->name('index');
                Route::get('/generateApiKey',  $controller . 'generateApiKey')->name('generateApiKey');
                Route::get('/getBalance',  $controller . 'getBalance')->name('getBalance');
                Route::get('/generateSignature',  $controller . 'generateSignature')->name('generateSignature');
                Route::get('/channel',  $controller . 'channel')->name('channel');
            });
        });
       
    });
    $prefix = 'wallet';
    Route::prefix($prefix)->namespace('Merchant')->as($prefix . '/')->group(function () use ($prefix) {
        $controller = ucfirst($prefix) . "Controller@";
        Route::get('/',  $controller . 'index')->name('index');
        Route::get('/assignToMerchant',  $controller . 'assignToMerchant')->name('assignToMerchant');
    });
    $prefix = "orders";
    Route::prefix($prefix)->as($prefix . '/')->group(function () {
        $prefix = 'payment';
        Route::prefix($prefix)->namespace('Orders')->as($prefix . '/')->group(function () use ($prefix) {
            $controller = ucfirst($prefix) . "Controller@";
            Route::post('/',  $controller . 'index')->name('index');
            Route::post('/callback',  $controller . 'callback')->name('callback');
            Route::post('/settingFee',  $controller . 'settingFee')->name('settingFee');
            Route::post('/withdraw',  $controller . 'withdraw')->name('withdraw');
            Route::post('/withdraw/update',  $controller . 'withdrawUpdate')->name('withdrawUpdate');
            Route::get('/{merchant_no}/{transaction_id}',  $controller . 'url')->name('url');
            Route::get('/getbill',  $controller . 'getBill')->name('getBill');
            Route::post('/paybill',  $controller . 'payBill')->name('payBill');
            Route::get('/getpubliser',  $controller . 'getPubliser')->name('getpubliser');
        });
        $prefix = 'bank';
        Route::prefix($prefix)->namespace('Orders')->as($prefix . '/')->group(function () use ($prefix) {
            $controller = ucfirst($prefix) . "Controller@";
            Route::get('/',  $controller . 'index')->name('index');
            Route::get('/details',  $controller . 'show')->name('show-details');
        });
        $prefix = 'transaction';
        Route::prefix($prefix)->namespace('Orders')->as($prefix . '/')->group(function () use ($prefix) {
            $controller = ucfirst($prefix) . "Controller@";
            Route::get('/deposit',  $controller . 'deposit')->name('deposit');
            Route::get('/all-deposit',  $controller . 'getAllDeposit')->name('all-deposit');
            Route::get('/all-withdraw',  $controller . 'getAllWithdraw')->name('all-withdraw');
            Route::get('/withdraw',  $controller . 'withdraw')->name('withdraw');
        });
    });
    $prefix = "test";
    Route::prefix($prefix)->as($prefix . '/')->group(function () {
        $prefix = 'deposit';
        Route::prefix($prefix)->namespace('Test')->as($prefix . '/')->group(function () use ($prefix) {
            $controller = ucfirst($prefix) . "Controller@";
            Route::get('/',  $controller . 'index')->name('index');
         
        });
    });
    Route::get('/get-list-voucher',[VoucherController::class,'getVoucherFromDB']);
    Route::post('/create-voucher',[VoucherController::class,'createVoucher']);
});
