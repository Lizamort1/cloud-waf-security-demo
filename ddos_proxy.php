<?php
/**
 * DDoS Demo Proxy - LIZ-WAF
 * Gửi requests và trả về kết quả thực tế
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Lấy parameters
$targetUrl = $_POST['target_url'] ?? '';
$requestIndex = $_POST['request_index'] ?? 0;

if (empty($targetUrl)) {
    echo json_encode(['error' => 'Missing target URL']);
    exit;
}

// Thêm timestamp để tránh cache
$urlWithParams = $targetUrl . (strpos($targetUrl, '?') !== false ? '&' : '?') . 'ddos_test=' . time() . '_' . $requestIndex;

// Gửi request
$startTime = microtime(true);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $urlWithParams,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'DDoS-Demo-Bot/1.0 (Educational Purpose)',
    CURLOPT_HTTPHEADER => [
        'Accept: text/html',
        'Accept-Language: vi-VN,vi;q=0.9',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000);

// Trả về kết quả
$result = [
    'status_code' => $httpCode,
    'duration_ms' => $duration,
    'is_blocked' => ($httpCode === 403),
    'is_success' => ($httpCode >= 200 && $httpCode < 300),
    'error' => $error ?: null,
    'request_index' => $requestIndex,
];

// Detect WAF block từ content
if ($httpCode === 403 || strpos($response, 'LIZ-WAF') !== false || strpos($response, 'ACCESS DENIED') !== false) {
    $result['is_blocked'] = true;
    $result['block_reason'] = 'WAF Protection';
}

echo json_encode($result);
?>
