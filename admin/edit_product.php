<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /jira-webdt/login.php");
    exit();
}

// Kiểm tra ID sản phẩm
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID sản phẩm không hợp lệ.');
}

$product_id = intval($_GET['id']);
$product_sql = "SELECT * FROM products WHERE id = $product_id";
$product_result = $conn->query($product_sql);

if ($product_result->num_rows !== 1) {
    die('Không tìm thấy sản phẩm.');
}

$product = $product_result->fetch_assoc();

// Xử lý cập nhật sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = floatval($_POST['price']);
    $sale_price = $_POST['sale_price'] !== '' ? floatval($_POST['sale_price']) : null;
    $stock_quantity = intval($_POST['stock_quantity']);
    $status = $_POST['status'];
    $image_url = $product['image_url'];

    // Xử lý ảnh mới (nếu có)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $filename = uniqid() . '-' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_url = 'uploads/' . $filename;
        }
    }

    // Cập nhật vào CSDL
    $stmt = $conn->prepare("UPDATE products SET name=?, price=?, sale_price=?, stock_quantity=?, status=?, image_url=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("sddissi", $name, $price, $sale_price, $stock_quantity, $status, $image_url, $product_id);
    $stmt->execute();

    $_SESSION['message'] = ['type' => 'success', 'text' => 'Cập nhật sản phẩm thành công!'];
    header("Location: manage_products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa sản phẩm</title>
</head>

<body style="font-family: Arial, sans-serif; margin: 40px; background-color: #f7f7f7;">
    <h2 style="color: #333;">✏️ Chỉnh sửa sản phẩm</h2>

    <form method="POST" enctype="multipart/form-data"
        style="background: white; padding: 20px; max-width: 600px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <label style="display: block; margin-bottom: 8px;">Tên sản phẩm:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required
            style="width: 100%; padding: 10px; margin-bottom: 15px;">

        <label style="display: block; margin-bottom: 8px;">Giá:</label>
        <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required
            style="width: 100%; padding: 10px; margin-bottom: 15px;">

        <label style="display: block; margin-bottom: 8px;">Giá khuyến mãi:</label>
        <input type="number" step="0.01" name="sale_price" value="<?php echo $product['sale_price']; ?>"
            style="width: 100%; padding: 10px; margin-bottom: 15px;">

        <label style="display: block; margin-bottom: 8px;">Tồn kho:</label>
        <input type="number" name="stock_quantity" value="<?php echo $product['stock_quantity']; ?>" required
            style="width: 100%; padding: 10px; margin-bottom: 15px;">

        <label style="display: block; margin-bottom: 8px;">Trạng thái:</label>
        <select name="status" style="width: 100%; padding: 10px; margin-bottom: 15px;">
            <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Hiển thị</option>
            <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Ẩn</option>
        </select>

        <label style="display: block; margin-bottom: 8px;">Ảnh sản phẩm hiện tại:</label>
        <?php if ($product['image_url']): ?>
            <img src="../<?php echo $product['image_url']; ?>" alt="Ảnh sản phẩm"
                style="max-width: 150px; margin-bottom: 10px;"><br>
        <?php else: ?>
            <p style="color: gray;">Chưa có ảnh.</p>
        <?php endif; ?>

        <label style="display: block; margin-bottom: 8px;">Thay ảnh mới (tùy chọn):</label>
        <input type="file" name="image" accept="image/*" style="margin-bottom: 15px;"><br>

        <button type="submit"
            style="background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px;">💾
            Lưu thay đổi</button>
        <a href="manage_products.php" style="margin-left: 15px; text-decoration: none; color: #555;">Quay lại</a>
    </form>
</body>

</html>