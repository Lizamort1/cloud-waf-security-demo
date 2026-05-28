<?php
require_once 'includes/db.php'; // Nhúng file kết nối CSDL và khởi tạo session
require_once 'includes/header.php'; // Nhúng header

// ============================================
// LỖ HỔNG SQL INJECTION - GIỮ NGUYÊN ĐỂ DEMO
// ============================================
// Mặc định hiển thị tất cả sản phẩm
$sql = "SELECT id, name, slug, price, sale_price, image_url FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 8";

// ============================================
// LỖ HỔNG PATH TRAVERSAL - DEMO
// Nếu search bắt đầu bằng "file:" thì đọc file thay vì tìm kiếm
// Ví dụ: file:includes/db.php hoặc file:../config.php
// ============================================
$file_content = '';
if (isset($_GET['search']) && strpos($_GET['search'], 'file:') === 0) {
    $file_path = substr($_GET['search'], 5); // Bỏ "file:" prefix
    if (file_exists($file_path)) {
        $file_content = file_get_contents($file_path);
    } else {
        $file_content = "File không tồn tại: " . $file_path;
    }
}

// Nếu có tìm kiếm - ĐÂY LÀ LỖ HỔNG SQL INJECTION
if (isset($_GET['search']) && !empty($_GET['search']) && strpos($_GET['search'], 'file:') !== 0) {
    $search = $_GET['search'];
    // KHÔNG sử dụng prepared statement để tạo lỗ hổng SQL Injection
    // Sử dụng multi_query để cho phép tấn công Stacked Queries
    $sql = "SELECT id, name, slug, price, sale_price, image_url FROM products WHERE name LIKE '%$search%'";
}
?>

<!-- THANH TÌM KIẾM - CHỨA LỖ HỔNG SQL INJECTION -->
<div style="background: linear-gradient(135deg, #667eea, #764ba2); padding: 40px 20px; text-align: center; margin-bottom: 30px; border-radius: 10px;">
    <h1 style="color: white; margin-bottom: 20px; font-size: 2em;">Tìm kiếm sản phẩm yêu thích</h1>
    <form method="GET" style="display: flex; justify-content: center; gap: 10px; max-width: 600px; margin: 0 auto;">
        <input type="text" name="search" placeholder="Nhập tên sản phẩm..." 
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
               style="flex: 1; padding: 15px 20px; border: none; border-radius: 25px; font-size: 1em; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <button type="submit" style="padding: 15px 30px; background-color: #ff6b6b; color: white; border: none; border-radius: 25px; cursor: pointer; font-size: 1em; transition: background-color 0.3s;">
            <i class="fas fa-search"></i> Tìm
        </button>
    </form>
    <?php if (isset($_GET['search'])): ?>
        <p style="color: white; margin-top: 15px;">
            <!-- LỖ HỔNG XSS DÙNG ĐỂ DEMO (ĐÃ LOẠI BỎ htmlspecialchars) -->
            Kết quả tìm kiếm cho: "<strong><?php echo $_GET['search']; ?></strong>"
        </p>
    <?php endif; ?>
    
    <?php if ($file_content): ?>
        <!-- HIỂN THỊ NỘI DUNG FILE (PATH TRAVERSAL) -->
        <div style="background: #2c3e50; color: #2ecc71; padding: 15px; border-radius: 10px; margin-top: 20px; text-align: left; max-height: 300px; overflow: auto;">
            <pre style="margin: 0; font-size: 12px; white-space: pre-wrap;"><?php echo htmlspecialchars($file_content); ?></pre>
        </div>
    <?php endif; ?>
</div>

<h2 style="color: #343a40; text-align: center; margin-bottom: 30px; font-weight: 600;">
    <?php echo isset($_GET['search']) ? 'Kết quả tìm kiếm' : 'Sản phẩm nổi bật'; ?>
</h2>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px;">
    <?php
    // Sử dụng multi_query để cho phép tấn công Stacked Queries
    if ($conn->multi_query($sql)) {
        if ($result = $conn->store_result()) {
            if ($result->num_rows > 0) {
                // Duyệt qua từng sản phẩm và hiển thị
                while ($row = $result->fetch_assoc()) {
                    ?>
                    <div
                        style="background-color: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.05); transition: transform 0.2s ease, box-shadow 0.2s ease;">
                        <a href="product.php?slug=<?php echo htmlspecialchars($row['slug'] ?? ''); ?>"
                            style="text-decoration: none; color: inherit;">
                            <img src="<?php echo htmlspecialchars($row['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($row['name']); ?>"
                                style="max-width: 100%; height: 220px; object-fit: contain; margin-bottom: 15px;">
                            <h3
                                style="font-size: 1.3em; margin: 10px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: center; color: #343a40;">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </h3>
                        </a>
                        <p style="font-size: 1.2em; color: #dc3545; font-weight: bold; margin-bottom: 15px;">
                            <?php if (isset($row['sale_price']) && $row['sale_price'] !== NULL && $row['sale_price'] < $row['price']): ?>
                                <span
                                    style="text-decoration: line-through; color: #6c757d; font-size: 0.9em; margin-right: 8px;"><?php echo number_format($row['price'], 0, ',', '.'); ?>đ</span>
                                <?php echo number_format($row['sale_price'], 0, ',', '.'); ?>đ
                            <?php else: ?>
                                <?php echo number_format($row['price'], 0, ',', '.'); ?>đ
                            <?php endif; ?>
                        </p>
                        <button onclick="window.location.href='add_to_cart.php?product_id=<?php echo $row['id']; ?>'"
                            style="background-color: #007bff; color: #fff; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-size: 1em; transition: background-color 0.3s ease, transform 0.2s ease;">Thêm
                            vào giỏ</button>
                    </div>
                    <?php
                }
            } else {
                echo "<p style=\"text-align: center; color: #6c757d; grid-column: 1/-1;\">Không tìm thấy sản phẩm nào phù hợp.</p>";
            }
            $result->free();
        }
        // Xử lý các query phía sau (nếu bị tấn công Stacked Queries)
        while ($conn->more_results() && $conn->next_result()) { }
    } else {
        echo "<p style=\"text-align: center; color: #dc3545; grid-column: 1/-1;\">Lỗi truy vấn cơ sở dữ liệu.</p>";
    }
    ?>
</div>

<?php
require_once 'includes/footer.php'; // Nhúng footer
$conn->close(); // Đóng kết nối CSDL
?>