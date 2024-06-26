<?php
function show_success($data = [],$params = []) {
    return [
        'status' => 200,
        'msg' => "Thành công!",
        'data' => $data,
        'params' => $params,
    ];
}