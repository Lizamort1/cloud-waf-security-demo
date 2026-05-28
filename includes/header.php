<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIZ-WAF - Cửa hàng điện thoại</title>
    <link rel="icon" type="image/x-icon" href="LOGO.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body
    style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f8f9fa; color: #343a40; line-height: 1.6;">
    <header style="background-color: #343a40; color: #fff; padding: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div class="container" style="width: 90%; max-width: 1200px; margin: 0 auto; padding: 20px 0;">
            <h1 style="float: left; margin: 0; font-size: 1.8em;"><a href="index.php"
                    style="color: #fff; text-decoration: none; font-weight: bold;">LIZ-WAF</a></h1>
            <nav style="float: right;">
                <ul style="margin: 0; padding: 0; list-style: none;">
                    <li style="display: inline-block; margin-left: 20px;"><a href="index.php"
                            style="color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background-color 0.3s ease;">Trang
                            chủ</a></li>
                    <li style="display: inline-block; margin-left: 20px;"><a href="product.php"
                            style="color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background-color 0.3s ease;">Sản
                            phẩm</a></li>
                    <li style="display: inline-block; margin-left: 20px;"><a href="cart.php"
                            style="color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background-color 0.3s ease;">Giỏ
                            hàng</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li style="display: inline-block; margin-left: 20px;"><a href="#"
                                style="color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 5px;">Chào,
                                <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></a></li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li style="display: inline-block; margin-left: 20px;"><a href="admin/index.php"
                                    style="color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background-color 0.3s ease;">Admin
                                    Panel</a></li>
                        <?php endif; ?>
                        <li style="display: inline-block; margin-left: 20px;"><a href="logout.php"
                                style="color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background-color 0.3s ease;">Đăng
                                xuất</a></li>
                    <?php else: ?>
                        <li style="display: inline-block; margin-left: 20px;"><a href="login.php"
                                style="color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background-color 0.3s ease;">Đăng
                                nhập</a></li>
                        <li style="display: inline-block; margin-left: 20px;"><a href="register.php"
                                style="color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background-color 0.3s ease;">Đăng
                                ký</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div style="clear: both;"></div>
        </div>
    </header>
    <main style="padding: 30px 0; min-height: 600px;">
        <div class="container" style="width: 90%; max-width: 1200px; margin: 0 auto; padding: 20px 0;">
            <?php display_message(); // Hiển thị thông báo chung ?>