# Kế hoạch: Source code Cloud-Ready + Demo chi phí 0đ

## Ý tưởng chính
**Viết code 1 lần, chỉ đổi file `.env` là chạy được ở 3 môi trường.** Mỗi dịch vụ AWS đều có bản thay thế miễn phí chạy bằng Docker trên máy local, dùng cùng API/giao thức với AWS.

| Thành phần | Local / Demo (miễn phí) | AWS Production (khi có kinh phí) | Cách chuyển |
|---|---|---|---|
| Lưu ảnh | **MinIO** (tương thích S3 API) | **S3 + CloudFront** | `AWS_ENDPOINT`, `AWS_BUCKET` |
| Database | SQL Server container | **RDS SQL Server** | `DB_HOST` |
| Cache | **Redis** container | **ElastiCache Redis** | `REDIS_HOST` |
| Hàng đợi | Redis queue | **SQS** | `QUEUE_CONNECTION=sqs` |
| Gửi email | **Mailpit** (hộp thư giả lập) | **SES** | `MAIL_MAILER=ses` |
| Bí mật | file `.env` | **Secrets Manager** | ECS task definition |
| Web server | Nginx + PHP-FPM (cùng image với prod) | **ECS Fargate + ALB** | giống nhau |
| Log | stdout | **CloudWatch Logs** | giống nhau |
| Cron hết hạn đơn | container `scheduler` | **EventBridge Scheduler** | giống nhau |

## Các hạng mục sẽ làm (giao coder thực hiện)

### A. Sửa code cho cloud-native
1. Upload và hiển thị ảnh qua `Storage::disk(config)` (S3/MinIO), seeder đẩy ảnh mẫu lên bucket
2. Cache và queue chuyển sang Redis, thêm Job `SendOrderConfirmationEmail` (đặt hàng → queue → email)
3. Endpoint `GET /api/health` (kiểm tra DB + Redis) cho ALB health check
4. Log ra `stderr`, bỏ secret khỏi `docker-compose.yml`, thêm `.env.example`, `.env.aws.example`

### B. Docker
5. `docker/prod/Dockerfile`: multi-stage, Nginx + PHP-FPM + OPcache, code nằm sẵn trong image (không bind-mount)
6. `docker-compose.yml` (dev) với đủ các service: app, sqlserver, redis, minio, mailpit, queue-worker, scheduler

### C. Hạ tầng AWS dạng code (viết sẵn, **chưa chạy nên không tốn tiền**)
7. `infra/terraform/`: VPC 2 AZ, ALB, ECS Fargate + Auto Scaling, RDS, ElastiCache, S3 + CloudFront, SQS, Secrets Manager, CloudWatch alarms, ECR
8. `.github/workflows/`: `ci.yml` (chạy test mỗi lần push, **miễn phí**) + `deploy-aws.yml` (bật thủ công khi có kinh phí)

### D. Tài liệu cho môn học (`docs/`)
9. `docs/cloud/architecture.md`: sơ đồ, lý do chọn từng dịch vụ, phân tích HA / Scaling / Security
10. `docs/cloud/cost-estimate.md`: bảng chi phí cho 3 mức (Demo / Cơ bản / Production)
11. `docs/cloud/deployment-guide.md`: các bước triển khai lên AWS

## Phương án demo có link public (chọn 1)
| Phương án | Chi phí | Ghi chú |
|---|---|---|
| **1. Chạy local + Cloudflare Tunnel** | 0đ | Có link `https://xxx.trycloudflare.com` ngay. Máy phải bật trong lúc demo |
| **2. AWS Academy Learner Lab** | 0đ (trường cấp credit) | Nếu trường có lớp AWS Academy. Demo **trên AWS thật**, phù hợp nhất với môn học |
| **3. Tài khoản AWS Free Tier mới** | 0đ trong phạm vi credit | Tài khoản mới có credit. 1 EC2 `t3.small` + RDS SQL Server Express (`db.t3.micro`) + S3 |

> [!IMPORTANT]
> Khi làm demo trên AWS (phương án 2/3), cần tạo **AWS Budget alert ($1)** ngay từ đầu và xóa hết tài nguyên sau buổi demo.

## Kiểm tra sau khi làm
- `docker compose up` chạy đủ các service, đặt hàng thì thấy email trong Mailpit, ảnh được phục vụ từ MinIO
- 26 test PHPUnit vẫn pass, GitHub Actions CI chạy xanh
- `terraform validate` và `terraform plan` (không `apply`) chạy không lỗi
