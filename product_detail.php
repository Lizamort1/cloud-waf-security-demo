<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_GET['product_id'])) {
    echo "<p style='color:red; text-align:center;'>Không tìm thấy sản phẩm.</p>";
    exit;
}

$product_id = (int) $_GET['product_id'];

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color:red; text-align:center;'>Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.</p>";
    exit;
}

$product = $result->fetch_assoc();
?>

<div style="max-width: 900px; margin: 40px auto; font-family: Arial, sans-serif; padding: 20px;">
    <div style="display: flex; gap: 30px;">
        <!-- Hình ảnh -->
        <div style="flex: 1;">
            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"
                style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 10px;">
        </div>

        <!-- Thông tin sản phẩm -->
        <div style="flex: 2;">
            <h2 style="font-size: 28px; margin-bottom: 10px;"><?php echo htmlspecialchars($product['name']); ?></h2>

            <?php if (!empty($product['sale_price'])): ?>
                <p style="font-size: 20px; color: red;">
                    <del style="color: #888;"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</del>
                    <strong><?php echo number_format($product['sale_price'], 0, ',', '.'); ?>₫</strong>
                </p>
            <?php else: ?>
                <p style="font-size: 20px; font-weight: bold;"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫
                </p>
            <?php endif; ?>

            <p style="font-size: 16px;">Tồn kho: <?php echo $product['stock_quantity']; ?></p>

            <?php if ($product['stock_quantity'] > 0): ?>
                <a href="add_to_cart.php?product_id=<?php echo $product['id']; ?>"
                    style="display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; border-radius: 5px; text-decoration: none; margin-top: 10px;">
                    Thêm vào giỏ hàng
                </a>
            <?php else: ?>
                <button disabled
                    style="padding: 10px 20px; background-color: #ccc; color: white; border-radius: 5px; margin-top: 10px; cursor: not-allowed;">
                    Hết hàng
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mô tả -->
    <div style="margin-top: 40px;">
        <h3 style="margin-bottom: 10px;">Mô tả sản phẩm</h3>
        <p style="line-height: 1.6; font-size: 15px; color: #444;">
            <?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>