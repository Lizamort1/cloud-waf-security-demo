<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Xử lý cập nhật giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cập nhật số lượng
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $product_id => $qty) {
            $product_id = intval($product_id);
            $qty = max(0, intval($qty));

            if ($qty == 0) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                $_SESSION['cart'][$product_id]['quantity'] = $qty;
            }
        }
    }

    // Xóa sản phẩm cụ thể
    if (isset($_POST['remove_item'])) {
        $remove_id = intval($_POST['remove_item']);
        unset($_SESSION['cart'][$remove_id]);
    }

    // Tránh gửi lại form
    header("Location: cart.php");
    exit();
}
?>

<h2 style="text-align: center; color: #333;">Giỏ hàng của bạn</h2>

<?php if (!empty($_SESSION['cart'])): ?>
    <form method="POST" action="cart.php">
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="padding: 10px;">Sản phẩm</th>
                    <th style="padding: 10px;">Giá</th>
                    <th style="padding: 10px;">Số lượng</th>
                    <th style="padding: 10px;">Tổng</th>
                    <th style="padding: 10px;">Xóa</th>
                </tr>
            </thead>
            <tbody>
                <?php $total = 0;
                foreach ($_SESSION['cart'] as $id => $item):
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                    ?>
                    <tr>
                        <td style="padding: 10px;"><?php echo htmlspecialchars($item['name']); ?></td>
                        <td style="padding: 10px;"><?php echo number_format($item['price'], 0, ',', '.') ?>đ</td>
                        <td style="padding: 10px;">
                            <input type="number" name="quantity[<?php echo $id ?>]" value="<?php echo $item['quantity'] ?>"
                                min="0" style="width: 60px; padding: 6px; text-align: center;">
                        </td>
                        <td style="padding: 10px;"><?php echo number_format($subtotal, 0, ',', '.') ?>đ</td>
                        <td style="padding: 10px;">
                            <button type="submit" name="remove_item" value="<?php echo $id ?>"
                                style="background-color: red; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">X</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold; padding: 10px;">Tổng cộng:</td>
                    <td style="padding: 10px; font-weight: bold;"><?php echo number_format($total, 0, ',', '.') ?>đ</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" name="update_cart"
                style="background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-size: 15px; cursor: pointer;">Cập
                nhật giỏ hàng</button>
            <a href="checkout.php"
                style="margin-left: 10px; background-color: #28a745; color: white; padding: 10px 20px; border-radius: 6px; font-size: 15px; text-decoration: none;">Tiến
                hành thanh toán</a>
        </div>
    </form>

<?php else: ?>
    <p style="text-align: center; color: #777;">Giỏ hàng của bạn đang trống. <a href="index.php"
            style="color: #007bff;">Tiếp tục mua sắm</a>.</p>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
$conn->close();
?>