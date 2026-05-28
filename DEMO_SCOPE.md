# Phạm Vi Chứng Minh Của Demo

Tài liệu này giải thích ngắn gọn phạm vi kỹ thuật của dự án để người đánh giá hiểu đúng vai trò của demo trong chủ đề điện toán đám mây và an ninh an toàn.

## Mục tiêu

Dự án tập trung vào phần **an ninh an toàn trong điện toán đám mây**, cụ thể là mô phỏng một lớp **Web Application Firewall (WAF)** bảo vệ ứng dụng web được triển khai trên môi trường Internet/cloud.

Demo không xây dựng một nền tảng cloud hoàn chỉnh như AWS, Azure hoặc Google Cloud. Thay vào đó, demo minh họa một thành phần bảo mật phổ biến trong các hệ thống cloud-facing application: lớp WAF đứng trước ứng dụng web.

## Mô hình

```text
Người dùng / kẻ tấn công -> Internet -> WAF -> Ứng dụng web PHP -> Cơ sở dữ liệu
```

Trong mô hình này:

- Ứng dụng web đóng vai trò dịch vụ được triển khai công khai qua Internet.
- WAF kiểm tra request trước khi request đi vào ứng dụng.
- Request hợp lệ được cho phép xử lý bình thường.
- Request độc hại bị ghi log, chặn hoặc đưa IP vào danh sách block.
- Dashboard hỗ trợ theo dõi log, thống kê và cấu hình cơ chế bảo vệ.

## Các nhóm tấn công được minh họa

| Nhóm tấn công | Ví dụ payload | Ý nghĩa |
| --- | --- | --- |
| SQL Injection | `rrrrrrrrrr%'; UPDATE products SET price = 10, sale_price = 10 WHERE id = 1; #` | Cố gắng chèn lệnh SQL để thay đổi dữ liệu sản phẩm. |
| XSS | `<script>alert('Bạn đã bị hack!')</script>` | Cố gắng chèn JavaScript để trình duyệt thực thi mã độc. |
| Path Traversal / Local File Inclusion | `file:includes/db.php` | Cố gắng truy cập file cấu hình nhạy cảm của ứng dụng. |
| Command Injection | `; whoami` | Cố gắng nối thêm lệnh hệ điều hành vào input. |
| DDoS / Rate Limiting | Nhiều request liên tục tới `ddos_target.php` | Mô phỏng lưu lượng cao để kiểm tra giới hạn request. |

## Liên hệ với cloud security

Trong các hệ thống cloud thực tế, WAF thường được dùng để bảo vệ ứng dụng web, API gateway hoặc dịch vụ public endpoint. Các dịch vụ tương đương có thể kể đến:

- AWS WAF
- Azure Web Application Firewall
- Google Cloud Armor

Vì vậy, giá trị của demo nằm ở việc chứng minh cách một ứng dụng web triển khai trên Internet/cloud có thể được bảo vệ ở tầng ứng dụng thông qua kiểm tra payload, rate limiting, ghi log, chặn IP và giám sát qua dashboard.
