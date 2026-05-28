<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssss", $username, $password, $full_name, $email, $role, $status);
    $stmt->execute();

    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thêm người dùng</title>
</head>

<body style="font-family: sans-serif; background-color: #f9f9f9; padding: 40px;">
    <div
        style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px #ccc;">
        <h2 style="text-align: center;">➕ Thêm người dùng mới</h2>
        <form method="POST">
            <label>Tên đăng nhập:</label>
            <input type="text" name="username" required style="width: 100%; padding: 10px; margin: 10px 0;">

            <label>Mật khẩu:</label>
            <input type="password" name="password" required style="width: 100%; padding: 10px; margin: 10px 0;">

            <label>Họ và tên:</label>
            <input type="text" name="full_name" required style="width: 100%; padding: 10px; margin: 10px 0;">

            <label>Email:</label>
            <input type="email" name="email" required style="width: 100%; padding: 10px; margin: 10px 0;">

            <label>Quyền:</label>
            <select name="role" style="width: 100%; padding: 10px; margin: 10px 0;">
                <option value="customer">Khách hàng</option>
                <option value="admin">Quản trị</option>
            </select>

            <label>Trạng thái:</label>
            <select name="status" style="width: 100%; padding: 10px; margin: 10px 0;">
                <option value="active">Kích hoạt</option>
                <option value="inactive">Vô hiệu</option>
            </select>

            <button type="submit"
                style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px;">✅ Thêm
                người dùng</button>
            <a href="manage_users.php"
                style="margin-left: 10px; padding: 10px 20px; background: #ccc; text-decoration: none; border-radius: 5px;">⬅
                Quay lại</a>
        </form>
    </div>
</body>

</html>