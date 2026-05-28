<?php
session_start();
require_once '../includes/db.php';

// Chỉ cho phép admin thực hiện
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Kiểm tra và lấy ID người dùng
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$id = intval($_GET['id']);

// Không cho phép admin tự xóa chính mình
if ($_SESSION['user_id'] == $id) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Bạn không thể xóa chính tài khoản của mình!'];
    header("Location: manage_users.php");
    exit();
}

// Xóa người dùng
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['message'] = ['type' => 'success', 'text' => 'Người dùng đã được xóa thành công.'];
} else {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Lỗi khi xóa người dùng: ' . $stmt->error];
}

$stmt->close();
$conn->close();

header("Location: manage_users.php");
exit();
