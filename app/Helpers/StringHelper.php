<?php
namespace App\Helpers;
use Illuminate\Support\Str;
class StringHelper {
    public static function generateApiKey($length  = 30) {
        return Str::random($length);
    }
    public static function generateMerchantNo($prefix = "pipay",$length = 6)
    {
        $randomNumber = str_pad(rand(0, pow(10, $length)-1), $length, '0', STR_PAD_LEFT);
        $result = $prefix. $randomNumber;
        return $result;
    }
    public static function generateUid() {
        $uid = Str::uuid();
        $result = $uid->toString();
        return $result;
    }
    public static function generateNumber(){
        $result = '';
        for ($i = 0; $i < 6; $i++) {
            $result .= mt_rand(0, 9);
        }
        return $result;
    }
}