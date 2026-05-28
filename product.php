<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// ============================================
// LỖ HỔNG SQL INJECTION - GIỮ NGUYÊN ĐỂ DEMO
// ============================================
if ($keyword !== '') {
    // KHÔNG sử dụng prepared statement để tạo lỗ hổng SQL Injection
    $sql = "SELECT * FROM products WHERE status = 'active' AND name LIKE '%$keyword%'";
} else {
    $sql = "SELECT * FROM products WHERE status = 'active'";
}

// Sử dụng multi_query để cho phép tấn công Stacked Queries
$conn->multi_query($sql);
$result = $conn->store_result();
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 30px; font-family: Arial, sans-serif;">
    <h2 style="text-align: center; color: #333; margin-bottom: 30px;">Danh sách sản phẩm</h2>

    <form method="get" style="display: flex; justify-content: center; margin-bottom: 30px;">
        <input type="text" name="keyword" placeholder="Tìm kiếm sản phẩm..."
            value="<?php echo htmlspecialchars($keyword); ?>"
            style="padding: 10px 15px; width: 300px; border: 1px solid #ccc; border-radius: 5px 0 0 5px; outline: none;">
        <button type="submit"
            style="padding: 10px 20px; border: none; background-color: #007bff; color: white; border-radius: 0 5px 5px 0; cursor: pointer;">
            Tìm kiếm
        </button>
    </form>

    <?php if ($result->num_rows > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
            <?php while ($product = $result->fetch_assoc()): ?>
                <div
                    style="border: 1px solid #ddd; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background-color: #fff; display: flex; flex-direction: column;">
                    <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"
                        style="width: 100%; height: 200px; object-fit: contain; background: #f8f8f8;">

                    <div style="padding: 15px; flex: 1; display: flex; flex-direction: column;">
                        <h4 style="font-size: 16px; color: #333; margin: 0 0 10px 0; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                            <?php echo htmlspecialchars($product['name']); ?></h4>

                        <div style="margin-bottom: 10px;">
                            <?php if (!empty($product['sale_price']) && $product['sale_price'] > 0): ?>
                                <p style="margin: 5px 0;">
                                    <del style="color: #999; font-size: 14px;"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</del>
                                    <span style="color: red; font-weight: bold; font-size: 16px;"><?php echo number_format($product['sale_price'], 0, ',', '.'); ?>₫</span>
                                </p>
                            <?php else: ?>
                                <p style="margin: 5px 0; font-weight: bold; font-size: 16px; color: #dc3545;">
                                    <?php echo number_format($product['price'], 0, ',', '.'); ?>₫</p>
                            <?php endif; ?>
                        </div>

                        <p style="font-size: 14px; color: #666; margin-bottom: 15px;">Tồn kho: <?php echo $product['stock_quantity']; ?></p>

                        <a href="product_detail.php?product_id=<?php echo $product['id']; ?>"
                            style="display: block; margin-top: auto; padding: 12px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; text-align: center; font-weight: bold;">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: red;">Không tìm thấy sản phẩm nào phù hợp.</p>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>