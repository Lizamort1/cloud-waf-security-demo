<?php
session_start();
require_once 'config.php';
// $conn đã được khởi tạo trong config.php

// 1. CHECK QUYỀN: Chỉ cho phép admin truy cập
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("<h1>Truy cập bị từ chối! Bạn không phải là Admin.</h1><a href='index.php'>Về trang chủ</a>");
}

// 2. XỬ LÝ FORM (THÊM / SỬA / XÓA)
$msg = "";
$edit_data = null;

// Xóa
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: admin.php"); // Refresh để xóa tham số trên URL
    exit;
}

// Lấy dữ liệu để sửa
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM products WHERE id = $id");
    $edit_data = $result->fetch_assoc();
}

// Lưu dữ liệu (Thêm mới hoặc Update)
if (isset($_POST['save'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $img = $_POST['image_url'];
    
    if (!empty($_POST['id'])) {
        // UPDATE
        $id = $_POST['id'];
        $sql = "UPDATE products SET name='$name', description='$desc', price='$price', image_url='$img' WHERE id=$id";
    } else {
        // INSERT
        $sql = "INSERT INTO products (name, description, price, image_url) VALUES ('$name', '$desc', '$price', '$img')";
    }

    if ($conn->query($sql)) {
        header("Location: admin.php");
        exit;
    } else {
        $msg = "Lỗi: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang Quản Trị Admin</title>
    <link rel="icon" type="image/x-icon" href="LOGO.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        .btn { padding: 8px 15px; text-decoration: none; border-radius: 4px; color: #fff; display: inline-block; cursor: pointer; border: none; }
        .btn-back { background: #555; }
        .btn-save { background: #28a745; }
        .btn-edit { background: #ffc107; color: #000; }
        .btn-delete { background: #dc3545; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #007bff; color: white; }
        img.preview { width: 50px; height: 50px; object-fit: cover; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;}
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1><i class="fas fa-cogs"></i> Quản Lý Sản Phẩm</h1>
        <a href="index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Về trang chủ</a>
    </div>

    <?php if ($msg): ?><p style="color:red"><?php echo $msg; ?></p><?php endif; ?>

    <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #eee;">
        <h2><?php echo $edit_data ? "Sửa sản phẩm" : "Thêm sản phẩm mới"; ?></h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $edit_data['id'] ?? ''; ?>">
            
            <div class="form-group">
                <label>Tên sản phẩm:</label>
                <input type="text" name="name" required value="<?php echo $edit_data['name'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Mô tả:</label>
                <textarea name="description" rows="3"><?php echo $edit_data['description'] ?? ''; ?></textarea>
            </div>
            
            <div style="display: flex; gap: 20px;">
                <div class="form-group" style="flex:1">
                    <label>Giá (VNĐ):</label>
                    <input type="number" name="price" required value="<?php echo $edit_data['price'] ?? ''; ?>">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Link Ảnh (URL):</label>
                    <input type="text" name="image_url" value="<?php echo $edit_data['image_url'] ?? ''; ?>">
                </div>
            </div>

            <button type="submit" name="save" class="btn btn-save">
                <i class="fas fa-save"></i> <?php echo $edit_data ? "Cập nhật" : "Thêm mới"; ?>
            </button>
            <?php if($edit_data): ?>
                <a href="admin.php" class="btn btn-back">Hủy sửa</a>
            <?php endif; ?>
        </form>
    </div>

    <h2>Danh sách trong kho</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ảnh</th>
                <th>Tên sản phẩm</th>
                <th>Giá</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
            while ($row = $result->fetch_assoc()) {
                $img = !empty($row['image_url']) ? $row['image_url'] : "https://via.placeholder.com/50";
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><img src="<?php echo htmlspecialchars($img); ?>" class="preview"></td>
                    <td>
                        <b><?php echo htmlspecialchars($row['name']); ?></b><br>
                        <small><?php echo htmlspecialchars(substr($row['description'], 0, 50)); ?>...</small>
                    </td>
                    <td><?php echo number_format($row['price']); ?> ₫</td>
                    <td>
                        <a href="admin.php?edit=<?php echo $row['id']; ?>" class="btn btn-edit"><i class="fas fa-edit"></i></a>
                        <a href="admin.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Bạn chắc chắn muốn xóa?')" class="btn btn-delete"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>