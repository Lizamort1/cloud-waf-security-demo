<?php
require_once '../includes/db.php';     // Nhúng file kết nối CSDL và khởi tạo session (session_start() có trong db.php)
require_once '../includes/header.php'; // Nhúng header của trang (đường dẫn ../ vì admin/ nằm sâu hơn 1 cấp)

// Kiểm tra quyền admin
// Điều này rất quan trọng để bảo vệ các trang quản trị.
// Nếu người dùng chưa đăng nhập (không có 'user_id' trong session)
// HOẶC vai trò của họ ('role') không phải là 'admin',
// thì họ sẽ bị chuyển hướng về trang đăng nhập.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Đặt một thông báo lỗi vào session để hiển thị trên trang đăng nhập
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Bạn không có quyền truy cập trang quản trị. Vui lòng đăng nhập với tài khoản quản trị.'];
    header("Location: ../login.php"); // Chuyển hướng về trang đăng nhập (lùi 1 cấp để đến login.php)
    exit(); // Dừng thực thi script ngay lập tức sau khi chuyển hướng
}

?>

<h2>Trang Quản Trị - Dashboard</h2>

<?php
// Không cần display_message() trực tiếp ở đây vì nó đã được gọi trong header.php
// (Code này được thiết kế để hiển thị thông báo ngay sau thẻ <div class="container"> trong main)
?>

<p>Chào mừng Admin, <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></strong>!
</p>
<div class="admin-nav" style="margin-top: 30px;">
    <h3>Các chức năng quản lý:</h3>
    <ul style="list-style: none; padding: 0; text-align: left;">
        <li style="margin-bottom: 10px;">
            <a href="add_product.php"
                style="display: block; padding: 10px 15px; background-color: #f2f2f2; border-left: 5px solid #007bff; text-decoration: none; color: #343a40; transition: background-color 0.2s ease, border-color 0.2s ease;">Thêm
                Sản Phẩm Mới</a>
        </li>
        <li style="margin-bottom: 10px;">
            <a href="manage_products.php"
                style="display: block; padding: 10px 15px; background-color: #f2f2f2; border-left: 5px solid #007bff; text-decoration: none; color: #343a40; transition: background-color 0.2s ease, border-color 0.2s ease;">Quản
                Lý Sản Phẩm </a>
        </li>
        <li style="margin-bottom: 10px;">
            <a href="manage_orders.php"
                style="display: block; padding: 10px 15px; background-color: #f2f2f2; border-left: 5px solid #007bff; text-decoration: none; color: #343a40; transition: background-color 0.2s ease, border-color 0.2s ease;">Quản
                Lý Đơn Hàng</a>
        </li>
        <li style="margin-bottom: 10px;">
            <a href="manage_users.php"
                style="display: block; padding: 10px 15px; background-color: #f2f2f2; border-left: 5px solid #007bff; text-decoration: none; color: #343a40; transition: background-color 0.2s ease, border-color 0.2s ease;">Quản
                Lý Người Dùng </a>
        </li>
        <li style="margin-bottom: 10px;">
            <a href="waf_dashboard.php"
                style="display: block; padding: 10px 15px; background-color: #fff3cd; border-left: 5px solid #ffc107; text-decoration: none; color: #343a40; transition: background-color 0.2s ease, border-color 0.2s ease;">Cấu Hình WAF (Web Application Firewall)</a>
        </li>
        <li style="margin-bottom: 10px;">
            <a href="../logout.php"
                style="display: block; padding: 10px 15px; background-color: #f2f2f2; border-left: 5px solid #dc3545; text-decoration: none; color: #343a40; transition: background-color 0.2s ease, border-color 0.2s ease;">Đăng
                Xuất</a>
        </li>
    </ul>
</div>

<?php
require_once '../includes/footer.php'; // Nhúng footer của trang
$conn->close(); // Đóng kết nối CSDL
?>