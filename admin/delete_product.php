<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /jira-webdt/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $product_id = intval($_POST['id']);

    // Lấy đường dẫn ảnh để xóa file nếu có
    $stmt_img = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt_img->bind_param("i", $product_id);
    $stmt_img->execute();
    $stmt_img->bind_result($image_url);
    $stmt_img->fetch();
    $stmt_img->close();

    // Xóa ảnh nếu tồn tại
    if (!empty($image_url)) {
        $file_path = '../' . $image_url;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Xóa sản phẩm
    $stmt_del = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt_del->bind_param("i", $product_id);
    if ($stmt_del->execute()) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Đã xóa sản phẩm thành công.'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Không thể xóa sản phẩm.'];
    }
    $stmt_del->close();
}

header("Location: manage_products.php");
exit();
