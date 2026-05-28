<?php
require_once 'includes/db.php'; // Kết nối CSDL và session

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);

    // Truy vấn thông tin sản phẩm
    $stmt = $conn->prepare("SELECT id, name, price, sale_price, image_url, stock_quantity FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($product = $result->fetch_assoc()) {
        $price = $product['sale_price'] ?? $product['price'];

        if (isset($_SESSION['cart'][$product_id])) {
            // Nếu đã có trong giỏ, tăng số lượng lên 1
            if ($_SESSION['cart'][$product_id]['quantity'] < $product['stock_quantity']) {
                $_SESSION['cart'][$product_id]['quantity'] += 1;
            }
        } else {
            // Nếu chưa có, thêm mới
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $price,
                'image_url' => $product['image_url'],
                'quantity' => 1
            ];
        }

        $_SESSION['message'] = ['type' => 'success', 'text' => 'Đã thêm sản phẩm vào giỏ hàng.'];
    }

    $stmt->close();
}

// Chuyển hướng sang giỏ hàng
header("Location: cart.php");
exit();
