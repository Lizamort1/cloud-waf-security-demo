<?php
/**
 * WAF Initialization File
 * Include file này ở đầu db.php hoặc bất kỳ file nào cần bảo vệ
 */

// Đường dẫn tới LizWAF
require_once __DIR__ . '/LizWAF.php';

/**
 * Hàm khởi tạo WAF - gọi sau khi có database connection
 * @param mysqli $conn - Database connection
 */
function waf_init($conn) {
    LizWAF::init($conn);
}

/**
 * Hàm bảo vệ - gọi ở đầu mỗi trang cần bảo vệ
 */
function waf_protect() {
    LizWAF::protect();
}

/**
 * Kiểm tra xem WAF đã được khởi tạo chưa
 */
function waf_is_ready() {
    return class_exists('LizWAF');
}
?>
