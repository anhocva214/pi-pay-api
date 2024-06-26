<?php
namespace App\Helpers;
use Illuminate\Support\Str;
class ErrorHelper {
    function response($message, $params = []) {
        return [
            'status' => 400,
            'msg' => $message,
            'data' => [],
            'params' => $params,
        ];
    }
}