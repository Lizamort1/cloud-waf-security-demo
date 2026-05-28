<?php
require_once '../includes/db.php'; // Điều chỉnh đường dẫn để nhúng file kết nối CSDL và khởi tạo session
require_once '../includes/header.php'; // Điều chỉnh đường dẫn để nhúng header

// Kiểm tra quyền admin
// Nếu người dùng chưa đăng nhập hoặc vai trò không phải là 'admin', chuyển hướng về trang đăng nhập
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Bạn không có quyền truy cập trang này. Vui lòng đăng nhập với tài khoản quản trị.'];
    header("Location: ../login.php"); // Chuyển hướng về trang đăng nhập
    exit(); // Dừng thực thi script
}

// Khởi tạo biến để lưu trữ thông báo lỗi hoặc thành công
$message = '';
$error = '';

// Lấy danh sách danh mục và thương hiệu từ CSDL để điền vào các dropdown trong form
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$brands = $conn->query("SELECT id, name FROM brands ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Xử lý khi form được gửi đi (phương thức POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form và loại bỏ khoảng trắng thừa
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0); // Đảm bảo là số nguyên
    $brand_id = intval($_POST['brand_id'] ?? 0);     // Đảm bảo là số nguyên
    $short_description = trim($_POST['short_description'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);         // Đảm bảo là số thập phân
    // Xử lý giá khuyến mãi: nếu rỗng hoặc không có, gán NULL vào CSDL
    $sale_price = isset($_POST['sale_price']) && $_POST['sale_price'] !== '' ? floatval($_POST['sale_price']) : NULL;
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0); // Đảm bảo là số nguyên
    $status = $_POST['status'] ?? 'active'; // Mặc định là 'active'

    // Tự động tạo slug nếu người dùng không nhập
    if (empty($slug)) {
        // Chuyển tên sản phẩm thành slug: loại bỏ ký tự đặc biệt, thay bằng dấu gạch ngang, chuyển về chữ thường
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    }

    // Biến để kiểm tra toàn bộ form có hợp lệ hay không
    $form_valid = true;

    // Xử lý upload ảnh sản phẩm
    $image_url = ''; // Biến để lưu đường dẫn ảnh sẽ được chèn vào CSDL
    // Kiểm tra xem có file ảnh nào được tải lên không và không có lỗi upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        // Định nghĩa thư mục đích để lưu ảnh.
        // Từ admin/add_product.php, để vào thư mục uploads/ (ngang hàng với admin/), ta cần lùi 1 cấp: ../uploads/
        $target_dir = "../uploads/";

        // Tạo tên file duy nhất để tránh trùng lặp khi nhiều người upload ảnh cùng tên
        // uniqid() tạo một ID duy nhất dựa trên thời gian hiện tại
        // basename() lấy tên file gốc từ đường dẫn đầy đủ của file được upload
        $image_name = uniqid() . '-' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name; // Đường dẫn đầy đủ của file ảnh trên máy chủ

        // Lấy phần mở rộng của file (ví dụ: jpg, png)
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Kiểm tra xem file tải lên có thực sự là một hình ảnh hợp lệ không
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) { // Nếu getimagesize trả về false, đó không phải là ảnh
            // Kiểm tra kích thước file (ví dụ: giới hạn 5MB)
            if ($_FILES["image"]["size"] > 5000000) {
                $_SESSION['message'] = ['type' => 'error', 'text' => "Lỗi: Kích thước file ảnh quá lớn (tối đa 5MB)."];
                $form_valid = false;
            }
            // Kiểm tra định dạng file ảnh được cho phép
            elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
                $_SESSION['message'] = ['type' => 'error', 'text' => "Lỗi: Chỉ cho phép định dạng JPG, JPEG, PNG & GIF."];
                $form_valid = false;
            } else {
                // Di chuyển file từ thư mục tạm thời của PHP sang thư mục đích trên máy chủ
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // Nếu thành công, lưu đường dẫn tương đối của ảnh vào biến $image_url
                    // Đường dẫn này là tương đối từ thư mục gốc của dự án (jira-webbdt/)
                    $image_url = 'uploads/' . $image_name;
                    // Bạn có thể thêm thông báo thành công cho việc tải ảnh lên nếu muốn
                    // $_SESSION['message'] = ['type' => 'success', 'text' => "Ảnh đã được tải lên thành công."];
                } else {
                    $_SESSION['message'] = ['type' => 'error', 'text' => "Lỗi: Có vấn đề khi tải ảnh lên máy chủ. Vui lòng kiểm tra quyền thư mục 'uploads'."];
                    $form_valid = false;
                }
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Lỗi: File bạn tải lên không phải là ảnh hợp lệ."];
            $form_valid = false;
        }
    }

    // Validate dữ liệu form chính (sau khi xử lý upload ảnh)
    if (empty($name) || $price <= 0 || $stock_quantity < 0 || empty($slug)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => "Vui lòng điền đầy đủ các thông tin bắt buộc và đảm bảo giá/số lượng hợp lệ."];
        $form_valid = false;
    }

    // Nếu form vẫn hợp lệ sau các kiểm tra ban đầu và upload ảnh
    if ($form_valid) {
        // Kiểm tra xem slug đã tồn tại trong CSDL chưa để tránh trùng lặp URL
        $stmt_check_slug = $conn->prepare("SELECT id FROM products WHERE slug = ?");
        $stmt_check_slug->bind_param("s", $slug);
        $stmt_check_slug->execute();
        $result_check_slug = $stmt_check_slug->get_result();
        if ($result_check_slug->num_rows > 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Lỗi: Slug sản phẩm đã tồn tại. Vui lòng chọn tên sản phẩm khác hoặc sửa slug."];
            $form_valid = false;
        }
        $stmt_check_slug->close();
    }

    // Nếu mọi thứ đều hợp lệ, tiến hành chèn dữ liệu vào cơ sở dữ liệu
    if ($form_valid) {
        // Sử dụng Prepared Statement để ngăn chặn SQL Injection
        $stmt = $conn->prepare("INSERT INTO products (name, slug, category_id, brand_id, short_description, description, price, sale_price, stock_quantity, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        // 'ssiissddiss' là chuỗi định dạng kiểu dữ liệu cho các tham số (string, string, integer, integer, string, string, double, double, integer, string, string)
        $stmt->bind_param("ssiissddiss", $name, $slug, $category_id, $brand_id, $short_description, $description, $price, $sale_price, $stock_quantity, $image_url, $status);

        // Thực thi câu lệnh
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => "Sản phẩm **" . htmlspecialchars($name) . "** đã được thêm thành công!"];
            // Xóa dữ liệu POST để làm trống form sau khi thêm thành công, tránh việc gửi lại form khi refresh
            $_POST = array();
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Lỗi khi thêm sản phẩm vào CSDL: " . $stmt->error];
        }
        $stmt->close(); // Đóng statement
    }

    // Luôn chuyển hướng sau khi xử lý POST để ngăn chặn lỗi gửi lại form (resubmission) khi refresh
    header("Location: add_product.php");
    exit(); // Dừng script sau khi chuyển hướng
}
?>

<h2>Thêm Sản Phẩm Mới</h2>

<?php display_message(); // Hiển thị thông báo (thành công/lỗi) từ session ?>

<div class="form-container" style="max-width: 700px;">
    <form action="add_product.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Tên sản phẩm (<span style="color: red;">*</span>):</label>
            <input type="text" id="name" name="name" required
                value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="slug">Slug (URL thân thiện - để trống để tự tạo):</label>
            <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="category_id">Danh mục:</label>
            <select id="category_id" name="category_id">
                <option value="">-- Chọn danh mục --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="brand_id">Thương hiệu:</label>
            <select id="brand_id" name="brand_id">
                <option value="">-- Chọn thương hiệu --</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?php echo $brand['id']; ?>" <?php echo (isset($_POST['brand_id']) && $_POST['brand_id'] == $brand['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($brand['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="short_description">Mô tả ngắn:</label>
            <textarea id="short_description" name="short_description"
                rows="3"><?php echo htmlspecialchars($_POST['short_description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="description">Mô tả chi tiết:</label>
            <textarea id="description" name="description"
                rows="6"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="price">Giá bán (<span style="color: red;">*</span>):</label>
            <input type="number" id="price" name="price" step="0.01" min="0" required
                value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="sale_price">Giá khuyến mãi (để trống nếu không có):</label>
            <input type="number" id="sale_price" name="sale_price" step="0.01" min="0"
                value="<?php echo htmlspecialchars($_POST['sale_price'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="stock_quantity">Số lượng tồn kho (<span style="color: red;">*</span>):</label>
            <input type="number" id="stock_quantity" name="stock_quantity" min="0" required
                value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="image">Ảnh sản phẩm:</label>
            <input type="file" id="image" name="image" accept="image/*">
            <small>Kích thước tối đa 5MB. Định dạng: JPG, JPEG, PNG, GIF.</small>
        </div>
        <div class="form-group">
            <label for="status">Trạng thái:</label>
            <select id="status" name="status">
                <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Đang kinh doanh</option>
                <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Ngừng kinh doanh</option>
            </select>
        </div>
        <div class="form-group">
            <button type="submit">Thêm Sản Phẩm</button>
        </div>
    </form>
</div>

<?php
require_once '../includes/footer.php'; // Nhúng footer
$conn->close(); // Đóng kết nối CSDL
?>

<style>
    h2 {
        color: #2c3e50;
        font-size: 26px;
        margin-bottom: 20px;
    }

    .form-container {
        background: #f9f9f9;
        border: 1px solid #ddd;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        margin: 0 auto;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        font-weight: bold;
        margin-bottom: 6px;
        color: #333;
    }

    input[type="text"],
    input[type="number"],
    input[type="file"],
    select,
    textarea {
        width: 100%;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #ccc;
        box-sizing: border-box;
        font-size: 15px;
        transition: border-color 0.3s;
    }

    input:focus,
    select:focus,
    textarea:focus {
        border-color: #3498db;
        outline: none;
    }

    textarea {
        resize: vertical;
    }

    small {
        display: block;
        color: #888;
        margin-top: 5px;
    }

    button[type="submit"] {
        padding: 12px 25px;
        background-color: #2ecc71;
        color: white;
        font-weight: bold;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    button[type="submit"]:hover {
        background-color: #27ae60;
    }
</style>