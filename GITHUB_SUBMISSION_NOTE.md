# Note For Instructor Review

Thua thay,

Sau phan thuyet trinh, nhom em xin phep gui lai ma nguon demo de thay xem ro hon muc tieu cua du an.

Demo nay tap trung vao phan **an ninh an toan trong dien toan dam may**, cu the la mo phong mot lop **Web Application Firewall (WAF)** bao ve ung dung web trien khai tren moi truong hosting/cloud.

Trong mo hinh nay:

```text
Nguoi dung / ke tan cong -> Internet -> WAF -> Ung dung web PHP -> Co so du lieu
```

WAF kiem tra request truoc khi ung dung xu ly, ghi log va chan cac dang tan cong pho bien:

- SQL Injection
- XSS
- Command Injection
- Path Traversal
- DDoS/rate limiting

Y nghia voi de tai:

- Ung dung web dong vai tro dich vu duoc trien khai tren Internet/cloud.
- WAF dong vai tro lop bao ve ung dung, tuong tu AWS WAF, Azure WAF hoac Google Cloud Armor.
- Dashboard the hien giam sat, cau hinh bao ve va log su co, phu hop voi noi dung an ninh trong dien toan dam may.

Nhom em thua nhan demo chua phai la mot he thong cloud day du nhu AWS/Azure/GCP. Tuy nhien, demo minh hoa mot thanh phan bao mat quan trong khi van hanh ung dung tren moi truong dam may: bao ve ung dung web cong khai bang WAF.
