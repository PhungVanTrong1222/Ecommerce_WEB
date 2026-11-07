# TechShop - Nền Tảng Thương Mại Điện Tử Thiết Bị Công Nghệ (V1)

Hệ thống thương mại điện tử chuyên nghiệp cung cấp điện thoại, máy tính bảng, laptop và phụ kiện công nghệ cao cấp. Dự án được xây dựng theo kiến trúc **Modular Monolith** kết hợp **Layered Architecture (Controller -> Service -> Repository)** trên nền tảng **Laravel 11/12**, cơ sở dữ liệu **Microsoft SQL Server 2022**, và môi trường container hóa **Docker**.

---

## 🏗️ 1. Kiến Trúc Hệ Thống (Architecture Overview)

```
┌────────────────────────────────────────────────────────┐
│                   MPA Frontend (Blade)                 │
│      Vanilla JS ES6+ Client Services + Bootstrap 5     │
└───────────────────────────┬────────────────────────────┘
                            │ REST API (JSON) + Cookie XSRF
┌───────────────────────────▼────────────────────────────┐
│                    API Layer / Routing                 │
│         CheckPermission & EnsureUserActive Middleware  │
└───────────────────────────┬────────────────────────────┘
                            │
┌───────────────────────────▼────────────────────────────┐
│                 Modular Monolith (Domain)              │
│   ├── Identity (Sanctum SPA Session, RBAC 16 quyền)    │
│   ├── Catalog  (Sản phẩm, danh mục, biến thể, kho)     │
│   ├── Cart     (Giỏ hàng Guest/Auth, đồng bộ tự động)  │
│   ├── Order    (Đặt hàng, mã đơn, hủy đơn, tồn kho)   │
│   └── Payment  (Thanh toán VNPay sandbox, COD)         │
└───────────────────────────┬────────────────────────────┘
                            │ Controller -> Service -> Repository
┌───────────────────────────▼────────────────────────────┐
│              Database Access Layer (DB::table)         │
│          SQL Server 2022 (16 bảng chuẩn hóa)           │
└────────────────────────────────────────────────────────┘
```

### Điểm nổi bật về thiết kế:
1. **Modular Monolith**: Mỗi domain nghiệp vụ là một module độc lập trong thư mục `app/Modules/`, bao gồm Controllers, Services, Repositories, và Form Requests riêng biệt.
2. **Layered Architecture**: Tuân thủ nghiêm ngặt mô hình Controller -> Service -> Repository. Mọi truy vấn cơ sở dữ liệu được thực hiện qua **Laravel Query Builder (`DB::table`)** trong Repository, bảo đảm tính minh bạch, tốc độ tối ưu và khả năng tương thích cao với SQL Server.
3. **Frontend MPA + REST API**: Giao diện người dùng sử dụng Blade Views render khung sườn, toàn bộ dữ liệu động được nạp và tương tác qua 41 REST APIs bằng Vanilla JS hiện đại, loại bỏ sự phụ thuộc vào các build tool nặng nề.

---

## 📊 2. Cấu Trúc Cơ Sở Dữ Liệu (16 Bảng SQL Server)

1. **Phân quyền & Tài khoản (Identity)**:
   - `roles`: Danh mục vai trò (`admin`, `customer`).
   - `permissions`: 16 quyền hạn hệ thống theo chuẩn `module.action`.
   - `role_permissions`: Bảng liên kết n-n giữa vai trò và quyền hạn.
   - `users`: Thông tin người dùng, mật khẩu hash, trạng thái (`Active`, `Suspended`).
2. **Danh mục & Sản phẩm (Catalog)**:
   - `categories`: Danh mục sản phẩm đa cấp kèm slug.
   - `products`: Sản phẩm chính kèm mô tả, bảo hành, trạng thái (`Active`, `Draft`, `Archived`).
   - `product_variants`: Biến thể SKU, màu sắc, dung lượng, giá bán, giá niêm yết.
   - `product_images`: Hình ảnh sản phẩm (ảnh chính, ảnh gallery).
   - `product_specs`: Thông số kỹ thuật chi tiết dạng Key-Value hoặc JSON.
3. **Kho hàng (Inventory)**:
   - `inventory`: Số lượng tồn kho và ngưỡng cảnh báo tối thiểu theo từng biến thể.
   - `inventory_transactions`: Lịch sử nhập, xuất, giữ chỗ và hoàn kho.
4. **Giỏ hàng (Cart)**:
   - `carts`: Giỏ hàng phiên làm việc (Guest qua `session_id`, User qua `user_id`).
   - `cart_items`: Các mặt hàng biến thể trong giỏ.
5. **Đơn hàng & Thanh toán (Order & Payment)**:
   - `orders`: Thông tin đơn hàng, mã đơn `TS-YYYYMMDD-XXXXX`, trạng thái giao dịch.
   - `order_items`: Chi tiết các sản phẩm trong đơn tại thời điểm mua.
   - `payments`: Giao dịch thanh toán COD / VNPay Sandbox.

---

## 🚀 3. Hướng Dẫn Cài Đặt & Chạy Hệ Thống

### Yêu cầu tiên quyết:
- Docker Desktop (hỗ trợ WSL2 hoặc Linux container).
- Git.

### Các bước khởi chạy với Docker:

1. **Khởi động các container**:
   ```bash
   cd ecommerce
   docker compose up -d
   ```
   Hệ thống gồm 2 container:
   - `ecommerce_sqlserver`: Microsoft SQL Server 2022 (Port `1433`).
   - `ecommerce_app`: PHP 8.3 CLI + OPcache + Worker Pool 8 luồng + Laravel (Port `8080`).

2. **Chạy Migration & Seeder (Tạo bảng và dữ liệu mẫu)**:
   ```bash
   docker exec -it ecommerce_app php artisan migrate --force
   docker exec -it ecommerce_app php artisan db:seed --force
   ```

3. **Truy cập ứng dụng**:
   - Cửa hàng (Storefront): [http://localhost:8080](http://localhost:8080)
   - Trang quản trị (Admin): [http://localhost:8080/admin](http://localhost:8080/admin)

---

## 🔑 4. Tài Khoản Trải Nghiệm Mẫu (Demo Credentials)

| Vai trò | Email đăng nhập | Mật khẩu | Quyền hạn |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@techshop.vn` | `Admin@123` | Toàn quyền quản trị hệ thống (16 permissions) |
| **Khách hàng mẫu (Customer)** | `customer@techshop.vn` | `Customer@123` | Mua sắm, quản lý giỏ hàng, xem đơn hàng cá nhân |

---

## 🌐 5. Danh Sách Tuyến Đường Web & Giao Diện (17 MPA Views)

### Giao diện Khách hàng (Customer Storefront)
- `/`: Trang chủ TechShop (Banner, Danh mục nổi bật, Sản phẩm bán chạy, Hàng mới về).
- `/products`: Danh sách sản phẩm kèm bộ lọc đa năng (danh mục, mức giá, sắp xếp, tìm kiếm từ khóa `q`).
- `/products/{slug}`: Chi tiết sản phẩm, chọn biến thể màu sắc/dung lượng, thông số kỹ thuật, thêm giỏ hàng.
- `/cart`: Giỏ hàng trực quan, kiểm tra tình trạng tồn kho, tăng giảm số lượng.
- `/checkout`: Trang đặt hàng, chọn phương thức COD hoặc Cổng VNPay Sandbox.
- `/orders/return`: Kết quả xử lý thanh toán VNPay trả về.
- `/orders/{orderNumber}`: Tra cứu trạng thái đơn hàng và lịch sử thanh toán.
- `/login`: Đăng nhập tài khoản.
- `/register`: Đăng ký tài khoản khách hàng mới.
- `/account`: Hồ sơ cá nhân, đổi mật khẩu và danh sách đơn mua.

### Giao diện Quản trị (Admin Dashboard)
- `/admin`: Bảng điều khiển thống kê tổng quan doanh thu, đơn hàng, khách hàng, sản phẩm.
- `/admin/categories`: Quản lý danh mục sản phẩm (thêm, sửa, xóa, bật/tắt).
- `/admin/products`: Quản lý danh sách sản phẩm, biến thể và thư viện hình ảnh.
- `/admin/inventory`: Theo dõi tồn kho thực tế và cập nhật số lượng nhập kho.
- `/admin/orders`: Quản lý danh sách đơn hàng, xem chi tiết và chuyển đổi trạng thái đơn.
- `/admin/payments`: Lịch sử giao dịch thanh toán COD và VNPay.
- `/admin/users`: Quản lý danh sách người dùng, vai trò và trạng thái tài khoản.

---

## ⚡ 6. Tối Ưu Hóa Hiệu Năng V1 (Performance Enhancements)
- **PHP OPcache (256MB)**: Tự động kích hoạt thông qua `docker-entrypoint.sh` và `opcache-recommended.ini`, tăng tốc độ đọc file trên Windows bind-mount lên gấp 10-15 lần.
- **PHP CLI Multi-workers**: Cấu hình `PHP_CLI_SERVER_WORKERS=8` xử lý đồng thời các request API và static assets.
- **Frontend Promise Deduplication**: `CatalogService` tự động lưu trữ và chia sẻ promise request, ngăn chặn gọi lặp API danh mục trên cùng một lượt tải trang.
- **Asynchronous Initialization**: Giao diện khởi tạo các thành phần Header, Cart Badge và Session thông qua `Promise.allSettled`.
