<?php
/**
 * DDoS Target Endpoint - LIZ-WAF Demo
 * 
 * Endpoint này THỰC SỰ được bảo vệ bởi WAF.
 * Khi nhận nhiều requests, WAF sẽ:
 * 1. Đếm số requests từ IP
 * 2. Khi vượt rate limit -> Log vào database
 * 3. Chặn IP và trả về 403
 */

// Bật WAF bảo vệ
require_once 'config.php';
require_once 'waf/LizWAF.php';

// Khởi tạo WAF
LizWAF::init($conn);

// Chạy bảo vệ WAF - ĐÂY LÀ BƯỚC QUAN TRỌNG
// Nếu IP vượt rate limit, sẽ bị chặn tại đây
LizWAF::protect();

// Nếu đến được đây, nghĩa là request được cho phép
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Trả về thông tin request (để client biết request thành công)
echo json_encode([
    'status' => 'ok',
    'message' => 'Request allowed',
    'timestamp' => time(),
    'request_id' => uniqid('req_'),
    'your_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);
?>
