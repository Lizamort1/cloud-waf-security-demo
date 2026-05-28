<?php
// =====================================================
// CẤU HÌNH KẾT NỐI DATABASE - TECHSTORE
// =====================================================

session_start();

// =====================================================
// CẤU HÌNH DATABASE
// =====================================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');        // Địa chỉ MySQL server
define('DB_USER', getenv('DB_USER') ?: 'demo_user');        // Tên đăng nhập MySQL
define('DB_PASS', getenv('DB_PASS') ?: 'demo_password');    // Mật khẩu MySQL
define('DB_NAME', getenv('DB_NAME') ?: 'demo_shop');        // Tên database

// =====================================================
// CẤU HÌNH WEBSITE
// =====================================================
define('SITE_NAME', 'TechStore');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/');
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// =====================================================
// TẠO KẾT NỐI DATABASE
// =====================================================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Thiết lập charset UTF-8 để hỗ trợ tiếng Việt
$conn->set_charset("utf8mb4");

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}

// =====================================================
// HÀM TRỢ GIÚP
// =====================================================

/**
 * Định dạng số tiền theo VNĐ
 * @param float $amount Số tiền
 * @return string Số tiền đã định dạng
 */
function formatPrice($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

/**
 * Lấy tổng số sản phẩm trong giỏ hàng
 * @return int Số lượng sản phẩm
 */
function getCartCount() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

/**
 * Lấy tổng tiền giỏ hàng
 * @return float Tổng tiền
 */
function getCartTotal() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Kiểm tra người dùng đã đăng nhập chưa
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

/**
 * Kiểm tra người dùng có phải admin không
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Chuyển hướng với thông báo
 * @param string $url URL đích
 * @param string $message Thông báo
 * @param string $type Loại thông báo (success, error, warning)
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = ['text' => $message, 'type' => $type];
    header("Location: $url");
    exit();
}

/**
 * Hiển thị thông báo flash (nếu có)
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        $bgColor = $msg['type'] === 'success' ? '#28a745' :
                   ($msg['type'] === 'error' ? '#dc3545' : '#ffc107');
        $textColor = $msg['type'] === 'warning' ? '#000' : '#fff';

        echo '<div style="background-color: ' . $bgColor . '; color: ' . $textColor . ';
              padding: 12px 20px; text-align: center; margin-bottom: 15px; border-radius: 5px;">
              ' . htmlspecialchars($msg['text']) . '</div>';

        unset($_SESSION['flash_message']);
    }
}

// =====================================================
// HƯỚNG DẪN CÀI ĐẶT
// =====================================================
// 1. Tạo database "demo_shop" trong phpMyAdmin
// 2. Import file db/demo_shop.sql (cấu trúc ban đầu)
// 3. Import file db/migration.sql (mở rộng e-commerce)
// 4. Cấu hình DB_HOST, DB_USER, DB_PASS, DB_NAME ở trên
// 5. Truy cập website qua http://localhost/path/to/src/
// =====================================================
?>
