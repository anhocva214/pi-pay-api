<?php
namespace App\Helpers;

use App\Models\ResultLogs;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\App;

class ApiHelper
{
    public static function getConfigByKey($key = "url")
    {
        $environment = env('APP_ENV');
        $merchantNo = env("{$environment}_MERCHANT_NO");
        $apiKey = env("{$environment}_API_KEY");
        $config = [];
        if ($environment === 'DEV') {
            $config = [
                'url' => 'https://vnpayapi.yldyd.xyz/api/',
                'merchant_no' => $merchantNo,
                'api_key' => $apiKey,
                'environment' => $environment,
            ];
        } elseif ($environment === 'production') {
            $config = [
                'url' => 'https://pay.1vnpay.org/',
                'merchant_no' => $merchantNo,
                'api_key' => $apiKey,
                'environment' => $environment,
            ];
        }
        $result = isset($config[$key]) ? $config[$key] : "";
        return $result;
    }
    public static function callApi($url, $arrdata = [], $method)
    {
        try {
            $client = new Client(['headers' => ['Content-Type' => 'application/json']]);
            $body = json_encode($arrdata);
            $response = $client->request('POST', $url, ['body' => $body]);
            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);
            $resultLog = new ResultLogs();
            $resultLog->transaction_id = $arrdata['transaction_id'];
            $resultLog->METHOD = $method;
            $resultLog->request_URL = $url;
            $resultLog->net_connect = ($statusCode === 200) ? ' Success' : 'Fail';
            $resultLog->status_code = $statusCode;
            $resultLog->request = $arrdata;
            $resultLog->reponse = $responseData;
            $resultLog->save();

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $errorData = json_decode($response->getBody(), true);
                $resultLog = new ResultLogs();
                $resultLog->transaction_id = $arrdata['transaction_id'];
                $resultLog->METHOD = 'POST';
                $resultLog->request_URL = $url;
                $resultLog->net_connect = ($statusCode === 200) ? ' Success' : 'Fail';
                $resultLog->status_code = $statusCode;
                $resultLog->request = json_encode($arrdata);
                $resultLog->reponse = $e->getMessage();
                $resultLog->save();
            }
        }
    }
    public static function Encrypt($text, $passphrase)
    {
       
        $salt = openssl_random_pseudo_bytes(8);
        $salted = $dx = '';
        while (strlen($salted) < 48) {
            $dx = md5($dx . $passphrase . $salt, true);
            $salted .= $dx;
        }
        $key = substr($salted, 0, 32);
        $iv = substr($salted, 32, 16);
        return base64_encode('Salted__' . $salt . openssl_encrypt($text . '', 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv));
    }
    public static function formatResponseGetBill($data)
    {
        $billDetail = $data['data']['billDetail'][0];
        $otherInfo = json_decode($billDetail['otherInfo'], true);

        return [
            'customerCode' => $data['data']['customerInfo']['customerCode'],
            'customerName' => $data['data']['customerInfo']['customerName'],
            'customerAddress' => $data['data']['customerInfo']['customerAddress'],
            'customerOtherInfo' => $data['data']['customerInfo']['customerOtherInfo'],
            'billDetail' => [
                'billNumber' => $billDetail['billNumber'],
                'period' => $billDetail['period'],
                'amount' => $billDetail['amount'],
                'billType' => $billDetail['billType'],
                'otherInfo' => $otherInfo
            ]
        ];
    }
}