<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Bạn không có quyền truy cập trang này.'];
    header("Location: ../login.php");
    exit();
}

// Cập nhật trạng thái đơn hàng nếu có POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    
    // Cập nhật trạng thái đơn hàng
    if (isset($_POST['new_status'])) {
        $new_status = $_POST['new_status'];
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $conn->prepare("UPDATE orders SET order_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
            $stmt->execute();
            $_SESSION['message'] = ['type' => 'success', 'text' => "Cập nhật trạng thái đơn #$order_id thành công."];
            $stmt->close();
        }
    }
    
    // Cập nhật trạng thái thanh toán
    if (isset($_POST['payment_status'])) {
        $payment_status = $_POST['payment_status'];
        $valid_payment = ['pending', 'paid', 'failed'];
        if (in_array($payment_status, $valid_payment)) {
            $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("si", $payment_status, $order_id);
            $stmt->execute();
            $_SESSION['message'] = ['type' => 'success', 'text' => "Cập nhật thanh toán đơn #$order_id thành công."];
            $stmt->close();
        }
    }
    
    header("Location: manage_orders.php");
    exit();
}

// Lấy danh sách đơn hàng
$result_orders = $conn->query("SELECT id, customer_name, total_amount, order_status, payment_method, payment_status, created_at FROM orders ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng</title>
</head>

<body style="font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; background: #f8f9fa;">

    <header style="background-color: #343a40; color: #fff; padding: 20px;">
        <div style="width: 90%; max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between;">
            <h1 style="margin: 0;"><a href="../index.php" style="color: white; text-decoration: none;">LIZ-WAF</a>
            </h1>
            <nav>
                <a href="manage_products.php" style="color: white; margin-left: 20px;">Sản phẩm</a>
                <a href="manage_users.php" style="color: white; margin-left: 20px;">Người dùng</a>
                <a href="manage_orders.php" style="color: white; margin-left: 20px; font-weight: bold;">Đơn hàng</a>
                <a href="../logout.php" style="color: white; margin-left: 20px;">Đăng xuất</a>
            </nav>
        </div>
    </header>

    <main style="padding: 40px 0; width: 90%; max-width: 1200px; margin: 0 auto;">

        <h2 style="text-align: center;">Quản lý đơn hàng</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div
                style="margin-bottom: 20px; padding: 10px 15px; border-radius: 5px; background-color: <?= $_SESSION['message']['type'] === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?= $_SESSION['message']['type'] === 'success' ? '#155724' : '#721c24'; ?>;">
                <?= $_SESSION['message']['text']; ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if ($result_orders->num_rows > 0): ?>
            <table
                style="width: 100%; border-collapse: collapse; background-color: white; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
                <thead style="background-color: #f1f1f1;">
                    <tr>
                        <th style="padding: 12px; border: 1px solid #ddd;">Mã ĐH</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Khách hàng</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Tổng tiền</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Trạng thái</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Phương thức</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Thanh toán</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Ngày đặt</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $result_orders->fetch_assoc()): ?>
                        <tr style="text-align: center;">
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= $order['id'] ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($order['customer_name']) ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd; color: green; font-weight: bold;">
                                <?= number_format($order['total_amount'], 0, ',', '.') ?>₫</td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <form method="post" action="manage_orders.php" style="margin: 0;">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="new_status" onchange="this.form.submit()" style="padding: 5px;">
                                        <option value="pending" <?= $order['order_status'] == 'pending' ? 'selected' : '' ?>>Chờ xử
                                            lý</option>
                                        <option value="processing" <?= $order['order_status'] == 'processing' ? 'selected' : '' ?>>
                                            Đang xử lý</option>
                                        <option value="shipped" <?= $order['order_status'] == 'shipped' ? 'selected' : '' ?>>Đã vận
                                            chuyển</option>
                                        <option value="delivered" <?= $order['order_status'] == 'delivered' ? 'selected' : '' ?>>Đã
                                            giao</option>
                                        <option value="cancelled" <?= $order['order_status'] == 'cancelled' ? 'selected' : '' ?>>Đã
                                            hủy</option>
                                    </select>
                                </form>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                                <?= $order['payment_method'] == 'bank' ? 'Chuyển khoản' : 'COD' ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                                <form method="post" action="manage_orders.php" style="margin: 0;">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="payment_status" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 13px;
                                        <?php if($order['payment_status'] == 'paid'): ?>background: #28a745; color: white;
                                        <?php elseif($order['payment_status'] == 'failed'): ?>background: #dc3545; color: white;
                                        <?php else: ?>background: #ffc107; color: #333;<?php endif; ?>">
                                        <option value="pending" <?= $order['payment_status'] == 'pending' ? 'selected' : '' ?>>Chờ TT</option>
                                        <option value="paid" <?= $order['payment_status'] == 'paid' ? 'selected' : '' ?>>Đã TT</option>
                                        <option value="failed" <?= $order['payment_status'] == 'failed' ? 'selected' : '' ?>>Thất bại</option>
                                    </select>
                                </form>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <a href="view_order_detail.php?id=<?= $order['id'] ?>"
                                    style="background-color: #17a2b8; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none;">Xem
                                    chi tiết</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; margin-top: 30px;">Không có đơn hàng nào.</p>
        <?php endif; ?>
    </main>

    <footer style="background-color: #343a40; color: white; text-align: center; padding: 20px 0;">
        <p style="margin: 0;">© <?= date('Y') ?> LIZ-WAF</p>
    </footer>

</body>

</html>

<?php $conn->close(); ?>