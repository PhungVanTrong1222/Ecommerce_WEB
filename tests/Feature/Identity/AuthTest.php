<?php

namespace Tests\Feature\Identity;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $curlScript = __DIR__ . '/../../test_sanctum_curl.sh';
        if (file_exists($curlScript)) {
            @unlink($curlScript);
        }

        static::cleanupAllTestData();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        static::cleanupAllTestData();
        parent::tearDownAfterClass();
    }

    /**
     * Dọn dẹp dữ liệu test của instance
     */
    protected function cleanupTestData(): void
    {
        static::cleanupAllTestData();
    }

    /**
     * Dọn dẹp sạch sẽ các user và dữ liệu rác sinh ra trong test
     */
    public static function cleanupAllTestData(): void
    {
        $testUsers = DB::table('users')
            ->where('email', 'LIKE', 'phpunit_tester_%')
            ->orWhere('email', 'LIKE', 'tester_%@example.com')
            ->orWhere('email', 'LIKE', 'attack_%@example.com')
            ->pluck('id');

        if ($testUsers->isNotEmpty()) {
            $userCartIds = DB::table('carts')->whereIn('user_id', $testUsers)->pluck('id');
            if ($userCartIds->isNotEmpty()) {
                DB::table('cart_items')->whereIn('cart_id', $userCartIds)->delete();
                DB::table('carts')->whereIn('id', $userCartIds)->delete();
            }

            $userOrderIds = DB::table('orders')->whereIn('user_id', $testUsers)->pluck('id');
            if ($userOrderIds->isNotEmpty()) {
                $paymentIds = DB::table('payments')->whereIn('order_id', $userOrderIds)->pluck('id');
                if ($paymentIds->isNotEmpty()) {
                    DB::table('payment_attempts')->whereIn('payment_id', $paymentIds)->delete();
                    DB::table('payments')->whereIn('id', $paymentIds)->delete();
                }
                DB::table('order_items')->whereIn('order_id', $userOrderIds)->delete();
                DB::table('orders')->whereIn('id', $userOrderIds)->delete();
            }

            DB::table('users')->whereIn('id', $testUsers)->delete();
        }
    }
    /**
     * Test lấy CSRF cookie từ Sanctum.
     */
    public function test_get_csrf_cookie(): void
    {
        $response = $this->get('/sanctum/csrf-cookie');
        $response->assertNoContent();
    }

    /**
     * Test đăng nhập thất bại khi sai mật khẩu.
     */
    public function test_login_invalid_credentials(): void
    {
        $response = $this->withHeaders([
            'Referer' => 'http://localhost:8080',
            'Origin' => 'http://localhost:8080',
        ])->postJson('/api/auth/login', [
            'email' => 'admin@techshop.vn',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                ],
            ]);
    }

    /**
     * Test đăng nhập thành công với tài khoản Admin.
     */
    public function test_login_admin_success(): void
    {
        $response = $this->withHeaders([
            'Referer' => 'http://localhost:8080',
            'Origin' => 'http://localhost:8080',
        ])->postJson('/api/auth/login', [
            'email' => 'admin@techshop.vn',
            'password' => 'admin123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'admin@techshop.vn',
                    'role' => 'admin',
                ],
            ]);
    }

    /**
     * Test me endpoint khi chưa đăng nhập trả về 401.
     */
    public function test_me_unauthenticated(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                ],
            ]);
    }

    /**
     * Test đăng ký tài khoản mới luôn có role customer.
     */
    public function test_register_creates_customer_user(): void
    {
        $email = 'phpunit_tester_' . time() . '@example.com';

        $response = $this->withHeaders([
            'Referer' => 'http://localhost:8080',
            'Origin' => 'http://localhost:8080',
        ])->postJson('/api/auth/register', [
            'fullname' => 'PHPUnit User',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => 2, // Thử leo quyền
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => $email,
                    'role' => 'customer',
                    'permissions' => [],
                ],
            ]);

        // Cleanup user test
        DB::table('users')->where('email', $email)->delete();
    }
}
