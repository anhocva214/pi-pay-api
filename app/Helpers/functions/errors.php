<?php
use App\Helpers\StringHelper;
function show_error($errorSlug, $params = [])
{
    $addLog = add_error_log($errorSlug, $params);
    return [
        'status' => 400,
        'msg' => $addLog['error_msg'] ?? null,
        'error_id' => $addLog['error_id'] ?? null,
        'data' => [],
        'params' => $params,
    ];
}
function add_error_log($errorSlug, $params)
{
    // Kiểm tra xem các repository có tồn tại không
    $errorRepository = app(\App\Repositories\ErrorRepository::class);
    $errorLogRepository = app(\App\Repositories\ErrorLogRepository::class);
    if (!$errorRepository || !$errorLogRepository) {
        // Xử lý khi không thể truy cập vào repository
        return [
            'error_id' => null,
            'error_msg' => 'Cannot access error repositories.',
        ];
    }
    $apiName = $params['api_name'] ?? null;
    $orderId = $params['order_id'] ?? null;
    // Lấy số lượng request trong phút cuối cùng
    $countRequestsInLastMinute = $errorLogRepository->countRequestsInLastMinute($orderId);
    // Kiểm tra xem số lượng request có null không
    if ($countRequestsInLastMinute === null) {
        // Xử lý khi không thể lấy được số lượng request trong phút cuối cùng
        return [
            'error_id' => null,
            'error_msg' => 'Error counting requests in the last minute.',
        ];
    }
    // Kiểm tra xem số lượng request có vượt quá giới hạn không
    $numberRequestMax = get_setting_value('number_request_max');
    if ($countRequestsInLastMinute >= $numberRequestMax) {
        $errorSlug = 'number_request_max';
    }
    // Tìm lỗi dựa trên slug, hoặc lấy lỗi mặc định nếu không tìm thấy
    $error = $errorRepository->findByKey('slug', $errorSlug);
    if (!$error) {
        $error = $errorRepository->findByKey('slug', 'error_not_found');
    }
    $errorId = $error->id ?? null;
    $errorMsg = $error->msg ?? null;
    // Kiểm tra xem có thể thêm log mới không
    if ($countRequestsInLastMinute < $numberRequestMax) {
        // Thêm log mới
        $errorLogRepository->add([
            'log_id' => StringHelper::generateUid(),
            'api_name' => $apiName,
            'error_id' => $errorId,
            'msg' => $errorMsg,
            'order_id' => $orderId,
            'data' => json_encode($params),
        ]);
    }
    // Trả về thông tin lỗi và số lượng request trong phút cuối cùng
    return [
        'error_id' => $errorId,
        'error_msg' => $errorMsg,
    ];
}
