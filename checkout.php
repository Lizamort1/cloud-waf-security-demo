<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Nếu giỏ hàng trống, chuyển về trang chủ
if (empty($_SESSION['cart'])) {
    echo '<p style="text-align:center; color:red;">Giỏ hàng đang trống. <a href="index.php" style="color:#007bff;">Mua hàng ngay</a></p>';
    require_once 'includes/footer.php';
    exit();
}

// Xử lý thanh toán khi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $phone = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'];

    // Kiểm tra dữ liệu
    $errors = [];
    if (empty($name)) $errors[] = "Vui lòng nhập họ tên.";
    if (empty($phone)) $errors[] = "Vui lòng nhập số điện thoại.";
    if (empty($address)) $errors[] = "Vui lòng nhập địa chỉ.";
    if (!in_array($payment_method, ['cod', 'bank'])) $errors[] = "Phương thức thanh toán không hợp lệ.";

    if (empty($errors)) {
        // Tính tổng đơn hàng
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Tạo đơn hàng
        $stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, customer_phone, shipping_address, total_amount, payment_method, order_status, payment_status) VALUES (NULL, ?, ?, ?, ?, ?, 'pending', 'pending')");
        $stmt->bind_param("sssss", $name, $phone, $address, $total, $payment_method);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Lưu chi tiết đơn hàng
        $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $stmtItem->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
            $stmtItem->execute();
        }
        $stmtItem->close();

        // Xoá giỏ hàng
        unset($_SESSION['cart']);
        
        // Hiển thị thông báo thành công
        echo '<div style="text-align:center; padding: 30px;">';
        echo '<p style="color:green; font-weight:bold; font-size: 24px;">Đặt hàng thành công!</p>';
        echo '<p style="font-size: 18px;">Mã đơn hàng: <strong>#' . $order_id . '</strong></p>';
        echo '<p style="font-size: 18px;">Tổng tiền: <strong style="color: red;">' . number_format($total, 0, ',', '.') . 'đ</strong></p>';
        
        // Nếu chọn chuyển khoản thì hiện mã QR
        if ($payment_method === 'bank') {
            echo '<div style="margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #ff6b6b, #ee5a24); border-radius: 15px; display: inline-block;">';
            echo '<h3 style="color: white; margin-bottom: 15px;">Quét mã QR để thanh toán</h3>';
            echo '<div style="background: white; padding: 15px; border-radius: 10px;">';
            echo '<img src="uploads/qr_payment.jpg" alt="QR thanh toán" style="max-width: 300px; border-radius: 8px;">';
            echo '</div>';
            echo '<p style="color: white; margin-top: 15px; font-size: 14px;">Nội dung chuyển khoản: <strong>DH' . $order_id . '</strong></p>';
            echo '</div>';
        } else {
            echo '<p style="margin-top: 20px;">Thanh toán khi nhận hàng (COD)</p>';
        }
        
        echo '<p style="margin-top: 30px;"><a href="index.php" style="background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px;">← Tiếp tục mua sắm</a></p>';
        echo '</div>';
        
        require_once 'includes/footer.php';
        exit();
    } else {
        echo '<div style="color:red; text-align:center;">' . implode('<br>', $errors) . '</div>';
    }
}
?>

<h2 style="text-align:center; color:#333;">Xác nhận đơn hàng</h2>

<!-- Danh sách sản phẩm -->
<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
    <thead>
        <tr style="background-color: #f2f2f2;">
            <th style="padding: 10px;">Sản phẩm</th>
            <th style="padding: 10px;">Giá</th>
            <th style="padding: 10px;">SL</th>
            <th style="padding: 10px;">Tổng</th>
        </tr>
    </thead>
    <tbody>
        <?php $total = 0; foreach ($_SESSION['cart'] as $item): 
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
        ?>
        <tr>
            <td style="padding: 10px;"><?php echo htmlspecialchars($item['name']); ?></td>
            <td style="padding: 10px;"><?php echo number_format($item['price'], 0, ',', '.') ?>đ</td>
            <td style="padding: 10px;"><?php echo $item['quantity'] ?></td>
            <td style="padding: 10px;"><?php echo number_format($subtotal, 0, ',', '.') ?>đ</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right; padding:10px; font-weight:bold;">Tổng cộng:</td>
            <td style="padding:10px; font-weight:bold;"><?php echo number_format($total, 0, ',', '.') ?>đ</td>
        </tr>
    </tfoot>
</table>

<!-- Form thông tin khách hàng -->
<form method="POST" style="margin-top: 30px; max-width: 600px; margin-left: auto; margin-right: auto;">
    <div style="margin-bottom: 15px;">
        <label for="full_name" style="display: block; margin-bottom: 5px;">Họ và tên:</label>
        <input type="text" name="full_name" id="full_name" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="phone_number" style="display: block; margin-bottom: 5px;">Số điện thoại:</label>
        <input type="text" name="phone_number" id="phone_number" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="address" style="display: block; margin-bottom: 5px;">Địa chỉ nhận hàng:</label>
        <textarea name="address" id="address" required rows="3" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;"></textarea>
    </div>
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px;">Phương thức thanh toán:</label>
        <label><input type="radio" name="payment_method" value="cod" checked> Thanh toán khi nhận hàng (COD)</label><br>
        <label><input type="radio" name="payment_method" value="bank"> Chuyển khoản ngân hàng</label>
    </div>
    <div style="text-align: right;">
        <button type="submit" style="background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 6px; font-size: 16px; cursor: pointer;">Xác nhận đặt hàng</button>
    </div>
</form>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
