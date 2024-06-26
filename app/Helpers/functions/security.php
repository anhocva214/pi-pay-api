<?php
function generate_signature(array $params, $key) {
    if(isset($params['signature'])) {
        unset($params['signature']);
    }
    $data = implode("|", $params) . "|" . $key;
    $signature = md5($data);
    return $signature;
}
function verify_signature(array $params, $key, $received_signature) {
    $generated_signature = generate_signature($params, $key);
    return $received_signature === $generated_signature;
}
