<?php

namespace Tests\Feature\Identity;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthTest extends TestCase
{
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
