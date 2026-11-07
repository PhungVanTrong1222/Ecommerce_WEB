#!/bin/bash
set -e

COOKIE_JAR="/tmp/cookies.txt"
rm -f "$COOKIE_JAR"

API_BASE="http://localhost:8000"
REFERER="http://localhost:8080"

echo "=== STEP 1: GET /sanctum/csrf-cookie ==="
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -c "$COOKIE_JAR" "$API_BASE/sanctum/csrf-cookie")
echo "Status: $HTTP_CODE"

RAW_TOKEN=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | tail -n 1 | awk '{print $NF}')
XSRF_TOKEN=$(php -r "echo urldecode('$RAW_TOKEN');")
echo "Extracted XSRF-TOKEN: ${XSRF_TOKEN:0:20}..."

echo ""
echo "=== STEP 2: Register user mới ==="
NEW_EMAIL="tester_$(date +%s)@example.com"
REG_RESP=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/register" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d "{\"fullname\":\"Test User\",\"email\":\"$NEW_EMAIL\",\"password\":\"password123\",\"password_confirmation\":\"password123\",\"phone\":\"0912345678\"}")
echo "$REG_RESP"

# Refresh XSRF-TOKEN if updated in cookie jar
RAW_TOKEN=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | tail -n 1 | awk '{print $NF}')
XSRF_TOKEN=$(php -r "echo urldecode('$RAW_TOKEN');")

echo ""
echo "=== STEP 3: GET /api/auth/me (should be customer) ==="
ME_RESP=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X GET "$API_BASE/api/auth/me" \
  -H "Accept: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN")
echo "$ME_RESP"

echo ""
echo "=== STEP 4: Logout -> /me -> 401 ==="
LOGOUT_RESP=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/logout" \
  -H "Accept: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN")
echo "Logout: $LOGOUT_RESP"

RAW_TOKEN=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | tail -n 1 | awk '{print $NF}')
XSRF_TOKEN=$(php -r "echo urldecode('$RAW_TOKEN');")

ME_AFTER_LOGOUT=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X GET "$API_BASE/api/auth/me" \
  -H "Accept: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN")
echo "Me after logout: $ME_AFTER_LOGOUT"

echo ""
echo "=== STEP 5: Login sai password -> 401 INVALID_CREDENTIALS ==="
LOGIN_FAIL_RESP=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d "{\"email\":\"admin@techshop.vn\",\"password\":\"wrongpassword\"}")
echo "$LOGIN_FAIL_RESP"

echo ""
echo "=== STEP 6: Login admin@techshop.vn / admin123 -> /me có 8 permissions ==="
LOGIN_ADMIN_RESP=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d "{\"email\":\"admin@techshop.vn\",\"password\":\"admin123\"}")
echo "Login admin: $LOGIN_ADMIN_RESP"

RAW_TOKEN=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | tail -n 1 | awk '{print $NF}')
XSRF_TOKEN=$(php -r "echo urldecode('$RAW_TOKEN');")

ADMIN_ME_RESP=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X GET "$API_BASE/api/auth/me" \
  -H "Accept: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN")
echo "Admin me: $ADMIN_ME_RESP"

echo ""
echo "=== STEP 7: Register với payload có role_id: 2 -> user vẫn là customer ==="
rm -f "$COOKIE_JAR"
curl -s -o /dev/null -c "$COOKIE_JAR" "$API_BASE/sanctum/csrf-cookie"
RAW_TOKEN=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | tail -n 1 | awk '{print $NF}')
XSRF_TOKEN=$(php -r "echo urldecode('$RAW_TOKEN');")

ATTACK_EMAIL="attack_$(date +%s)@example.com"
ATTACK_RESP=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/register" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d "{\"fullname\":\"Hacker\",\"email\":\"$ATTACK_EMAIL\",\"password\":\"password123\",\"password_confirmation\":\"password123\",\"role_id\":2}")
echo "Attack register: $ATTACK_RESP"

echo ""
echo "=== STEP 8: Validation lỗi -> 422 VALIDATION_ERROR envelope ==="
rm -f "$COOKIE_JAR"
curl -s -o /dev/null -c "$COOKIE_JAR" "$API_BASE/sanctum/csrf-cookie"
RAW_TOKEN=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | tail -n 1 | awk '{print $NF}')
XSRF_TOKEN=$(php -r "echo urldecode('$RAW_TOKEN');")
VAL_RESP=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/register" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d "{\"email\":\"not-an-email\"}")
echo "$VAL_RESP"

echo ""
echo "=== STEP 9: Change password & Update profile ==="
# Login again as the user from step 2
LOGIN_TESTER=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d "{\"email\":\"$NEW_EMAIL\",\"password\":\"password123\"}")
echo "Login tester: $LOGIN_TESTER"

RAW_TOKEN=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | tail -n 1 | awk '{print $NF}')
XSRF_TOKEN=$(php -r "echo urldecode('$RAW_TOKEN');")

# Change password wrong current
CHG_FAIL=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/change-password" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d "{\"current_password\":\"wrongpass\",\"new_password\":\"newsecret123\",\"new_password_confirmation\":\"newsecret123\"}")
echo "Change password fail: $CHG_FAIL"

# Change password success
CHG_OK=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/change-password" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d "{\"current_password\":\"password123\",\"new_password\":\"newsecret123\",\"new_password_confirmation\":\"newsecret123\"}")
echo "Change password ok: $CHG_OK"

# Update profile
RAW_TOKEN=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | tail -n 1 | awk '{print $NF}')
XSRF_TOKEN=$(php -r "echo urldecode('$RAW_TOKEN');")
PROFILE_RESP=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X PATCH "$API_BASE/api/profile" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d "{\"fullname\":\"Test User Updated\",\"phone\":\"0988888888\",\"address\":\"123 Nguyen Hue\"}")
echo "Update profile: $PROFILE_RESP"


echo ""
echo "=== STEP 10: RBAC CheckPermission test ==="
# Access admin products with current tester user (customer role) -> expect 403 PERMISSION_DENIED
CUSTOMER_ADMIN_RESP=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X GET "$API_BASE/api/admin/products" \
  -H "Accept: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN")
echo "Customer accessing admin/products: $CUSTOMER_ADMIN_RESP"

# Logout customer first
curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/logout" \
  -H "Accept: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" > /dev/null

RAW_TOKEN=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | tail -n 1 | awk '{print $NF}')
XSRF_TOKEN=$(php -r "echo urldecode('$RAW_TOKEN');")

# Login as admin and access admin products -> expect 200
curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d "{\"email\":\"admin@techshop.vn\",\"password\":\"admin123\"}" > /dev/null

RAW_TOKEN=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | tail -n 1 | awk '{print $NF}')
XSRF_TOKEN=$(php -r "echo urldecode('$RAW_TOKEN');")

ADMIN_PROD_RESP=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X GET "$API_BASE/api/admin/products" \
  -H "Accept: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN")
echo "Admin accessing admin/products: $ADMIN_PROD_RESP"

echo ""
echo "=== STEP 11: EnsureUserActive test ==="
# Logout admin first
curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/logout" \
  -H "Accept: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" > /dev/null

RAW_TOKEN=$(grep 'XSRF-TOKEN' "$COOKIE_JAR" | tail -n 1 | awk '{print $NF}')
XSRF_TOKEN=$(php -r "echo urldecode('$RAW_TOKEN');")

# Set customer user to Suspended in DB
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\DB::table('users')->where('email', '$NEW_EMAIL')->update(['status' => 'Suspended']);
"

# Try logging in as suspended user -> expect 403 ACCOUNT_SUSPENDED
SUSPEND_LOGIN_RESP=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$API_BASE/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Referer: $REFERER" \
  -H "Origin: $REFERER" \
  -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
  -d "{\"email\":\"$NEW_EMAIL\",\"password\":\"newsecret123\"}")
echo "Suspended login: $SUSPEND_LOGIN_RESP"

# Restore status for cleanup
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\DB::table('users')->where('email', '$NEW_EMAIL')->delete();
"
