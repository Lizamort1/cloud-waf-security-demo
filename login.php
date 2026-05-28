<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        $storedPassword = $user['password'];
        $passwordMatches = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);

        if ($passwordMatches) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            header("Location: " . ($user['role'] === 'admin' ? "admin/manage_products.php" : "index.php"));
            exit();
        }
    }

    $error = "Tên đăng nhập hoặc mật khẩu không đúng.";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - LIZ-WAF</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0;">

<div style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
    <div style="background-color: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 100%; max-width: 400px;">
        <h2 style="text-align: center; margin-bottom: 25px; color: #343a40;">Đăng nhập tài khoản</h2>

        <?php if ($error): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <label for="username" style="font-weight: bold;">Tên đăng nhập:</label>
            <input type="text" id="username" name="username" required
                   style="width: 100%; padding: 10px; margin: 8px 0 16px; border: 1px solid #ccc; border-radius: 5px;">

            <label for="password" style="font-weight: bold;">Mật khẩu:</label>
            <input type="password" id="password" name="password" required
                   style="width: 100%; padding: 10px; margin: 8px 0 20px; border: 1px solid #ccc; border-radius: 5px;">

            <button type="submit"
                    style="width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                Đăng nhập
            </button>
        </form>

        <p style="text-align: center; margin-top: 20px; font-size: 14px;">Chưa có tài khoản? <a href="register.php" style="color: #007bff;">Đăng ký ngay</a></p>
    </div>
</div>

</body>
</html>
