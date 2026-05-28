<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Không tìm thấy người dùng.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $full_name, $email, $role, $status, $id);
    $stmt->execute();

    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Cập nhật tài khoản</title>
</head>

<body style="font-family: sans-serif; background-color: #f9f9f9; padding: 40px;">
    <div
        style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px #ccc;">
        <h2 style="text-align: center;">✏️ Cập nhật người dùng</h2>
        <form method="POST">
            <label>Họ và tên:</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required
                style="width: 100%; padding: 10px; margin: 10px 0;">

            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                style="width: 100%; padding: 10px; margin: 10px 0;">

            <label>Quyền:</label>
            <select name="role" style="width: 100%; padding: 10px; margin: 10px 0;">
                <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Khách hàng</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Quản trị</option>
            </select>

            <label>Trạng thái:</label>
            <select name="status" style="width: 100%; padding: 10px; margin: 10px 0;">
                <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Kích hoạt</option>
                <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Vô hiệu</option>
            </select>

            <button type="submit"
                style="padding: 10px 20px; background: green; color: white; border: none; border-radius: 5px;">✅ Lưu
                thay đổi</button>
            <a href="manage_users.php"
                style="margin-left: 10px; padding: 10px 20px; background: #ccc; text-decoration: none; border-radius: 5px;">⬅
                Quay lại</a>
        </form>
    </div>
</body>

</html>