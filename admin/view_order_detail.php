<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Lấy thông tin đơn hàng
$sql_order = "SELECT * FROM orders WHERE id = ?";
$stmt = $conn->prepare($sql_order);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result_order = $stmt->get_result();
$order = $result_order->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "<p style='color: red; text-align: center;'>Không tìm thấy đơn hàng.</p>";
    require_once '../includes/footer.php';
    exit();
}

// Lấy danh sách sản phẩm trong đơn hàng
$sql_items = "SELECT oi.*, p.name AS product_name, p.image_url 
              FROM order_items oi
              JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
?>

<div style="padding: 30px 0;">
    <div style="max-width: 900px; margin: 0 auto;">
        <h2 style="text-align: center; margin-bottom: 30px;">🧾 Chi tiết đơn hàng #<?php echo $order_id; ?></h2>

        <div style="background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
            <p><strong>Địa chỉ giao hàng:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
            <p><strong>Trạng thái:</strong> <?php echo ucfirst($order['order_status']); ?></p>
            <p><strong>Thanh toán:</strong>
                <?php echo htmlspecialchars($order['payment_method']) . " (" . $order['payment_status'] . ")"; ?></p>
            <p><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
            <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_amount'], 0, ',', '.') . '₫'; ?></p>
        </div>

        <table style="width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 0 5px rgba(0,0,0,0.1);">
            <thead style="background-color: #f0f0f0;">
                <tr>
                    <th style="padding: 10px; border: 1px solid #ddd;">Sản phẩm</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Ảnh</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Giá</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Số lượng</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_items && $result_items->num_rows > 0): ?>
                    <?php while ($item = $result_items->fetch_assoc()): ?>
                        <tr style="text-align: center;">
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?php echo htmlspecialchars($item['product_name']); ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <img src="../<?php echo $item['image_url']; ?>" alt="Ảnh" width="60"
                                    style="border-radius: 4px;">
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?php echo number_format($item['price_at_order'], 0, ',', '.'); ?>₫
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $item['quantity']; ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?php echo number_format($item['price_at_order'] * $item['quantity'], 0, ',', '.'); ?>₫
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding: 15px; text-align: center;">Không có sản phẩm trong đơn hàng.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once '../includes/footer.php';
$conn->close();
?>