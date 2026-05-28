<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra xem có phải admin không
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng điều khiển Admin</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 40px;">

    <div style="max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
        <h1 style="color: #2c3e50; font-size: 28px; text-align: center;">🎛️ Bảng điều khiển Admin</h1>
        <p style="text-align: center; color: #555;">Xin chào, <strong><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></strong></p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-top: 30px;">
            <a href="manage_users.php" style="display: block; padding: 20px; background: #3498db; color: white; text-decoration: none; text-align: center; border-radius: 8px; font-weight: bold;">👤 Quản lý người dùng</a>

            <a href="manage_products.php" style="display: block; padding: 20px; background: #2ecc71; color: white; text-decoration: none; text-align: center; border-radius: 8px; font-weight: bold;">📱 Quản lý sản phẩm</a>

            <a href="../logout.php" style="display: block; padding: 20px; background: #e74c3c; color: white; text-decoration: none; text-align: center; border-radius: 8px; font-weight: bold;">🚪 Đăng xuất</a>
        </div>
    </div>

</body>
</html>
