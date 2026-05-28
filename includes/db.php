<?php
// Bắt đầu session cho toàn bộ website. Rất quan trọng cho giỏ hàng, đăng nhập.
// Luôn đặt ở đầu các tệp PHP cần sử dụng session.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cấu hình kết nối cơ sở dữ liệu - InfinityFree Hosting
define('DB_SERVER', getenv('DB_HOST') ?: 'localhost');      // Hostname MySQL
define('DB_USERNAME', getenv('DB_USER') ?: 'demo_user');    // Username MySQL
define('DB_PASSWORD', getenv('DB_PASS') ?: 'demo_password'); // Password MySQL
define('DB_NAME', getenv('DB_NAME') ?: 'demo_shop');        // Database name

// Tạo kết nối MySQLi
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối cơ sở dữ liệu thất bại: " . $conn->connect_error);
}

// RẤT QUAN TRỌNG: Thiết lập bộ ký tự cho kết nối để hỗ trợ tiếng Việt (UTF-8)
$conn->set_charset("utf8mb4");

// ============================================================
// LIZ-WAF INTEGRATION - Web Application Firewall
// ============================================================
// Kiểm tra xem có phải trang admin WAF không (không bảo vệ trang này)
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$waf_excluded_pages = ['waf_dashboard.php'];

if (!in_array($current_page, $waf_excluded_pages)) {
    // Load WAF
    $waf_path = __DIR__ . '/../waf/LizWAF.php';
    if (file_exists($waf_path)) {
        require_once $waf_path;
        
        // Initialize and protect
        LizWAF::init($conn);
        LizWAF::protect();
    }
}
// ============================================================

// Hàm tiện ích để hiển thị thông báo
function display_message() {
    if (isset($_SESSION['message'])) {
        echo '<p class="message ' . $_SESSION['message']['type'] . '">' . $_SESSION['message']['text'] . '</p>';
        unset($_SESSION['message']); // Xóa thông báo sau khi hiển thị
    }
}
?>
