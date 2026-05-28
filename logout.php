<?php
session_start();      // Bắt đầu phiên làm việc (session). Điều này cần thiết để truy cập và thao tác với các biến session.

session_unset();      // Xóa tất cả các biến session. Điều này sẽ loại bỏ tất cả dữ liệu được lưu trữ trong $_SESSION.

session_destroy();    // Hủy hoàn toàn phiên làm việc hiện tại. Điều này xóa ID phiên khỏi trình duyệt và xóa tệp session trên máy chủ.

header("Location: index.php"); // Chuyển hướng người dùng về trang chủ (index.php) sau khi đăng xuất.
exit();               // Dừng tất cả các đoạn mã PHP tiếp theo để đảm bảo chuyển hướng được thực hiện ngay lập tức.
?>