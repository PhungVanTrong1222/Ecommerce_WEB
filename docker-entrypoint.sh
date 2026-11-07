#!/bin/bash
set -e

# Tự động kích hoạt OPcache nếu chưa có cấu hình
if [ ! -f /usr/local/etc/php/conf.d/opcache-recommended.ini ]; then
    echo "[Entrypoint] Đang bật và cấu hình OPcache..."
    docker-php-ext-enable opcache > /dev/null 2>&1 || true
    cat << 'EOF' > /usr/local/etc/php/conf.d/opcache-recommended.ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
realpath_cache_size=4096K
realpath_cache_ttl=600
EOF
    echo "[Entrypoint] OPcache đã được cấu hình thành công!"
fi

# Khởi tạo Laravel nếu artisan chưa tồn tại
if [ ! -f /var/www/html/artisan ]; then
    echo "[Entrypoint] File artisan chưa tồn tại. Đang khởi tạo Laravel..."
    composer config -g policy.advisories.block false
    rm -rf /tmp/laravel_init
    mkdir -p /tmp/laravel_init
    composer create-project --prefer-dist laravel/laravel /tmp/laravel_init --no-interaction --no-security-blocking
    
    # Sao chép các tệp vào thư mục làm việc, không ghi đè các file docker đã có
    cp -rn /tmp/laravel_init/. /var/www/html/
    rm -rf /tmp/laravel_init
    
    # Cấu hình .env
    if [ -f /var/www/html/.env ]; then
        echo "[Entrypoint] Cấu hình .env cho SQL Server..."
        sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlsrv/' /var/www/html/.env
        sed -i 's/^DB_HOST=.*/DB_HOST=sqlserver/' /var/www/html/.env
        sed -i 's/^DB_PORT=.*/DB_PORT=1433/' /var/www/html/.env
        sed -i 's/^DB_DATABASE=.*/DB_DATABASE=ecommerce/' /var/www/html/.env
        sed -i 's/^DB_USERNAME=.*/DB_USERNAME=sa/' /var/www/html/.env
        sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=Ecommerce@2026!/' /var/www/html/.env
        
        # Thêm cấu hình TrustServerCertificate và Encrypt cho SQL Server ODBC 18 nếu chưa có
        grep -q "^DB_ENCRYPT=" /var/www/html/.env && sed -i 's/^DB_ENCRYPT=.*/DB_ENCRYPT=false/' /var/www/html/.env || echo "DB_ENCRYPT=false" >> /var/www/html/.env
        grep -q "^DB_TRUST_SERVER_CERTIFICATE=" /var/www/html/.env && sed -i 's/^DB_TRUST_SERVER_CERTIFICATE=.*/DB_TRUST_SERVER_CERTIFICATE=true/' /var/www/html/.env || echo "DB_TRUST_SERVER_CERTIFICATE=true" >> /var/www/html/.env
        
        # Cấu hình Timezone và Session driver
        grep -q "^APP_TIMEZONE=" /var/www/html/.env && sed -i 's/^APP_TIMEZONE=.*/APP_TIMEZONE=Asia\/Ho_Chi_Minh/' /var/www/html/.env || echo "APP_TIMEZONE=Asia/Ho_Chi_Minh" >> /var/www/html/.env
        sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' /var/www/html/.env

        # Cập nhật cả .env.example
        cp /var/www/html/.env /var/www/html/.env.example
        sed -i 's/^APP_KEY=.*/APP_KEY=/' /var/www/html/.env.example
    fi
    echo "[Entrypoint] Khởi tạo Laravel hoàn tất!"
fi

# Nếu chạy artisan serve, chờ SQL Server và tạo DB ecommerce trước khi serve
if [ "$1" = "php" ] && [ "$2" = "artisan" ] && [ "$3" = "serve" ]; then
    echo "[Entrypoint] Đang kiểm tra kết nối SQL Server..."
    for i in {1..30}; do
        if /opt/mssql-tools18/bin/sqlcmd -S sqlserver -U sa -P 'Ecommerce@2026!' -C -Q "SELECT 1" > /dev/null 2>&1; then
            echo "[Entrypoint] SQL Server đã sẵn sàng!"
            /opt/mssql-tools18/bin/sqlcmd -S sqlserver -U sa -P 'Ecommerce@2026!' -C -Q "IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = 'ecommerce') CREATE DATABASE ecommerce;" > /dev/null 2>&1
            echo "[Entrypoint] Database 'ecommerce' đã được đảm bảo tồn tại."
            break
        fi
        sleep 2
    done
fi

exec "$@"
