<?php

namespace Tests\Feature\Cart;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartTest extends TestCase
{
    protected ?User $customer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withCredentials();

        $this->customer = User::where('email', 'customer@example.com')->first();
        if (! $this->customer) {
            $customerRoleId = DB::table('roles')->where('code', 'customer')->value('id');
            if (! $customerRoleId) {
                $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
                $customerRoleId = DB::table('roles')->insertGetId([
                    'code' => 'customer',
                    'name' => 'Khách hàng',
                    'created_at' => $now,
                ]);
            }

            $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
            $userId = DB::table('users')->insertGetId([
                'fullname' => 'Nguyễn Văn A',
                'email' => 'customer@example.com',
                'password_hash' => '$2y$12$e/a/e.8qJ3d1hN1yv0.0qO7b6/2a6.9yQk/qY6.rXv8eO6Wl1kK/q', // customer123
                'role_id' => $customerRoleId,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->customer = User::find($userId);
        }

        if ($this->customer) {
            $userCartIds = DB::table('carts')->where('user_id', $this->customer->id)->pluck('id');
            if ($userCartIds->isNotEmpty()) {
                DB::table('cart_items')->whereIn('cart_id', $userCartIds)->delete();
                DB::table('carts')->whereIn('id', $userCartIds)->delete();
            }
        }
    }

    /**
     * Helper tạo category, product, variant và inventory để test.
     */
    protected function prepareProductVariant(int $initialStock = 10, string $status = 'Active', float $price = 100000, ?float $salePrice = null): object
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        $categoryId = DB::table('categories')->where('slug', 'phu-kien-test')->value('id');
        if (! $categoryId) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => 'Phụ kiện Test',
                'slug' => 'phu-kien-test',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Product Test ' . uniqid(),
            'slug' => 'product-test-' . uniqid(),
            'status' => 'Active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'variant_name' => 'Bản tiêu chuẩn',
            'price' => $price,
            'sale_price' => $salePrice,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('inventory')->updateOrInsert(
            ['variant_id' => $variantId],
            [
                'quantity' => $initialStock,
                'updated_at' => $now,
            ]
        );

        return (object) [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'stock' => $initialStock,
            'price' => $price,
            'sale_price' => $salePrice,
        ];
    }

    /**
     * Test Guest truy cập Cart rỗng không tạo dòng DB rác.
     */
    public function test_guest_get_cart_when_empty_returns_empty_dto_without_creating_cart_in_db(): void
    {
        $beforeCount = DB::table('carts')->count();

        $response = $this->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => null,
                    'items' => [],
                    'total_quantity' => 0,
                    'subtotal' => 0,
                    'has_unavailable_items' => false,
                ],
            ]);

        $afterCount = DB::table('carts')->count();
        $this->assertEquals($beforeCount, $afterCount);
    }

    /**
     * Test Guest thêm sản phẩm tạo cart và gắn cookie guest_cart_token.
     */
    public function test_guest_add_item_creates_cart_and_sets_cookie(): void
    {
        $item = $this->prepareProductVariant(20, 'Active', 200000, 180000);

        $response = $this->postJson('/api/cart/items', [
            'variant_id' => $item->variant_id,
            'quantity' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_quantity' => 2,
                    'subtotal' => 360000,
                    'has_unavailable_items' => false,
                ],
            ]);

        $this->assertCount(1, $response->json('data.items'));
        $this->assertEquals((string) $item->variant_id, $response->json('data.items.0.variant_id'));
        $this->assertEquals(2, $response->json('data.items.0.quantity'));
        $this->assertEquals(180000, $response->json('data.items.0.effective_price'));
        $this->assertTrue($response->json('data.items.0.is_available'));

        $response->assertCookie('guest_cart_token', null, false);
        $cartId = $response->json('data.id');
        $this->assertNotNull($cartId);

        // Kiểm tra trong DB
        $cart = DB::table('carts')->where('id', $cartId)->first();
        $this->assertNotNull($cart);
        $this->assertEquals('Active', $cart->status);
        $this->assertNull($cart->user_id);
        $this->assertNotNull($cart->guest_token_hash);
    }

    /**
     * Test Guest thêm tiếp cùng sản phẩm thì cộng dồn số lượng.
     */
    public function test_guest_add_same_item_increments_quantity(): void
    {
        $item = $this->prepareProductVariant(20, 'Active', 100000);

        // Lần 1
        $res1 = $this->postJson('/api/cart/items', [
            'variant_id' => $item->variant_id,
            'quantity' => 2,
        ]);
        $res1->assertStatus(200);
        $cookieVal = $res1->getCookie('guest_cart_token', false)->getValue();

        // Lần 2 với cookie
        $res2 = $this->withUnencryptedCookie('guest_cart_token', $cookieVal)
            ->postJson('/api/cart/items', [
                'variant_id' => $item->variant_id,
                'quantity' => 3,
            ]);

        $res2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_quantity' => 5,
                    'subtotal' => 500000,
                ],
            ]);
    }

    /**
     * Test thêm sản phẩm vượt tồn kho trả về 409 INSUFFICIENT_STOCK.
     */
    public function test_add_item_insufficient_stock_returns_409(): void
    {
        $item = $this->prepareProductVariant(3);

        $response = $this->postJson('/api/cart/items', [
            'variant_id' => $item->variant_id,
            'quantity' => 5,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INSUFFICIENT_STOCK',
                ],
            ]);
    }

    /**
     * Test thêm variant Inactive trả về 422 VARIANT_INACTIVE.
     */
    public function test_add_item_inactive_variant_returns_422(): void
    {
        $item = $this->prepareProductVariant(10, 'Inactive');

        $response = $this->postJson('/api/cart/items', [
            'variant_id' => $item->variant_id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VARIANT_INACTIVE',
                ],
            ]);
    }

    /**
     * Test thêm variant không tồn tại trả về 404 RESOURCE_NOT_FOUND.
     */
    public function test_add_item_non_existing_variant_returns_404(): void
    {
        $response = $this->postJson('/api/cart/items', [
            'variant_id' => 9999999,
            'quantity' => 1,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                ],
            ]);
    }

    /**
     * Test cập nhật số lượng item trong cart.
     */
    public function test_update_cart_item_quantity(): void
    {
        $item = $this->prepareProductVariant(10, 'Active', 50000);

        $res = $this->postJson('/api/cart/items', [
            'variant_id' => $item->variant_id,
            'quantity' => 2,
        ]);
        $cookieVal = $res->getCookie('guest_cart_token', false)->getValue();
        $itemId = $res->json('data.items.0.id');

        // Cập nhật lên 4
        $updateRes = $this->withUnencryptedCookie('guest_cart_token', $cookieVal)
            ->patchJson('/api/cart/items/' . $itemId, [
                'quantity' => 4,
            ]);

        $updateRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_quantity' => 4,
                    'subtotal' => 200000,
                ],
            ]);

        // Cập nhật quá tồn kho -> 409
        $failRes = $this->withUnencryptedCookie('guest_cart_token', $cookieVal)
            ->patchJson('/api/cart/items/' . $itemId, [
                'quantity' => 15,
            ]);
        $failRes->assertStatus(409);
    }

    /**
     * Test xóa item khỏi giỏ hàng.
     */
    public function test_remove_cart_item(): void
    {
        $item = $this->prepareProductVariant(10);

        $res = $this->postJson('/api/cart/items', [
            'variant_id' => $item->variant_id,
            'quantity' => 1,
        ]);
        $cookieVal = $res->getCookie('guest_cart_token', false)->getValue();
        $itemId = $res->json('data.items.0.id');

        $deleteRes = $this->withUnencryptedCookie('guest_cart_token', $cookieVal)
            ->deleteJson('/api/cart/items/' . $itemId);

        $deleteRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'items' => [],
                    'total_quantity' => 0,
                    'subtotal' => 0,
                ],
            ]);
    }

    /**
     * Test xóa sạch giỏ hàng (clear cart).
     */
    public function test_clear_cart(): void
    {
        $item1 = $this->prepareProductVariant(10);
        $item2 = $this->prepareProductVariant(10);

        $res1 = $this->postJson('/api/cart/items', [
            'variant_id' => $item1->variant_id,
            'quantity' => 2,
        ]);
        $cookieVal = $res1->getCookie('guest_cart_token', false)->getValue();

        $this->withUnencryptedCookie('guest_cart_token', $cookieVal)
            ->postJson('/api/cart/items', [
                'variant_id' => $item2->variant_id,
                'quantity' => 3,
            ]);

        $clearRes = $this->withUnencryptedCookie('guest_cart_token', $cookieVal)
            ->deleteJson('/api/cart');

        $clearRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'items' => [],
                    'total_quantity' => 0,
                    'subtotal' => 0,
                ],
            ]);
    }

    /**
     * Test hợp nhất Guest Cart khi Customer chưa có Active Cart.
     */
    public function test_merge_guest_cart_when_user_has_no_active_cart(): void
    {
        // Xóa cart cũ của customer nếu có
        $oldCart = DB::table('carts')->where('user_id', $this->customer->id)->where('status', 'Active')->first();
        if ($oldCart) {
            DB::table('cart_items')->where('cart_id', $oldCart->id)->delete();
            DB::table('carts')->where('id', $oldCart->id)->delete();
        }

        $item = $this->prepareProductVariant(10, 'Active', 150000);

        // Guest thêm item vào giỏ
        $guestRes = $this->postJson('/api/cart/items', [
            'variant_id' => $item->variant_id,
            'quantity' => 3,
        ]);
        $cookieVal = $guestRes->getCookie('guest_cart_token', false)->getValue();

        // Customer đăng nhập kèm cookie guest_cart_token
        $loginRes = $this->withUnencryptedCookie('guest_cart_token', $cookieVal)
            ->withHeaders([
                'Referer' => 'http://localhost:8080',
                'Origin' => 'http://localhost:8080',
            ])->postJson('/api/auth/login', [
                'email' => 'customer@example.com',
                'password' => 'customer123',
            ]);
        $loginRes->assertStatus(200);

        // Kiểm tra cart của customer bây giờ có 3 item đó
        $userCartRes = $this->actingAs($this->customer, 'web')
            ->getJson('/api/cart');

        $userCartRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_quantity' => 3,
                    'subtotal' => 450000,
                ],
            ]);
    }

    /**
     * Test hợp nhất Guest Cart khi Customer đã có Active Cart.
     */
    public function test_merge_guest_cart_when_user_already_has_active_cart(): void
    {
        $item1 = $this->prepareProductVariant(10, 'Active', 100000);
        $item2 = $this->prepareProductVariant(10, 'Active', 200000);

        // 1. Guest tạo giỏ trước với variant 1 (qty 3) và variant 2 (qty 1)
        $guestRes1 = $this->postJson('/api/cart/items', [
            'variant_id' => $item1->variant_id,
            'quantity' => 3,
        ]);
        $cookieVal = $guestRes1->getCookie('guest_cart_token', false)->getValue();

        $this->withUnencryptedCookie('guest_cart_token', $cookieVal)
            ->postJson('/api/cart/items', [
                'variant_id' => $item2->variant_id,
                'quantity' => 1,
            ]);

        // 2. Customer tạo active cart với variant 1 (qty 2)
        $this->actingAs($this->customer, 'web')
            ->postJson('/api/cart/items', [
                'variant_id' => $item1->variant_id,
                'quantity' => 2,
            ]);

        // 3. Clear auth guard session trước khi gọi API login kèm guest cookie
        Auth::forgetGuards();

        $loginRes = $this->withUnencryptedCookie('guest_cart_token', $cookieVal)
            ->withHeaders([
                'Referer' => 'http://localhost:8080',
                'Origin' => 'http://localhost:8080',
            ])->postJson('/api/auth/login', [
                'email' => 'customer@example.com',
                'password' => 'customer123',
            ]);
        $loginRes->assertStatus(200);

        // 4. Cart customer có variant 1 = 5, variant 2 = 1. Total qty = 6
        $userCartRes = $this->actingAs($this->customer, 'web')
            ->getJson('/api/cart');

        $userCartRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_quantity' => 6,
                    'subtotal' => 700000, // 5 * 100000 + 1 * 200000
                ],
            ]);
    }

    /**
     * Test khi sản phẩm trong cart hết hàng hoặc inactive, is_available = false và unavailable_reason đúng.
     */
    public function test_cart_marks_unavailable_items_correctly(): void
    {
        $item = $this->prepareProductVariant(5, 'Active', 100000);

        $res = $this->postJson('/api/cart/items', [
            'variant_id' => $item->variant_id,
            'quantity' => 2,
        ]);
        $cookieVal = $res->getCookie('guest_cart_token', false)->getValue();

        // Giả lập tồn kho giảm về 0 (hết hàng)
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        DB::table('inventory')->where('variant_id', $item->variant_id)->update([
            'quantity' => 0,
            'updated_at' => $now,
        ]);

        $cartRes = $this->withUnencryptedCookie('guest_cart_token', $cookieVal)
            ->getJson('/api/cart');

        $cartRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_quantity' => 2,
                    'subtotal' => 0,
                    'has_unavailable_items' => true,
                    'items' => [
                        [
                            'is_available' => false,
                            'unavailable_reason' => 'OUT_OF_STOCK',
                        ],
                    ],
                ],
            ]);
    }
}
