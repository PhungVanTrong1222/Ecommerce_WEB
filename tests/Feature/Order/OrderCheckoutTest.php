<?php

namespace Tests\Feature\Order;

use App\Models\User;
use App\Modules\Payment\Services\VnPayService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    protected ?User $customer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::where('email', 'customer@example.com')->first();
        if (!$this->customer) {
            $customerRoleId = DB::table('roles')->where('code', 'customer')->value('id');
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
    }

    /**
     * Helper chuẩn bị variant và tồn kho
     */
    protected function prepareProductVariant(int $initialStock = 10): object
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        $categoryId = DB::table('categories')->where('slug', 'dien-thoai')->value('id');
        if (!$categoryId) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => 'Điện thoại',
                'slug' => 'dien-thoai',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Test Phone ' . uniqid(),
            'slug' => 'test-phone-' . uniqid(),
            'status' => 'Active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sku = 'TEST-SKU-' . strtoupper(uniqid());
        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => $sku,
            'variant_name' => '128GB Black',
            'price' => 10000000,
            'sale_price' => 9000000,
            'status' => 'Active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('inventory')->insert([
            'variant_id' => $variantId,
            'quantity' => $initialStock,
            'updated_at' => $now,
        ]);

        return (object) [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'sku' => $sku,
            'price' => 10000000,
            'sale_price' => 9000000,
        ];
    }

    /**
     * Helper tạo active cart và thêm item
     */
    protected function setupCartWithItem(int $variantId, int $quantity): int
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        // Tìm hoặc tạo active cart
        $cart = DB::table('carts')
            ->where('user_id', $this->customer->id)
            ->where('status', 'Active')
            ->first();

        if (!$cart) {
            $cartId = DB::table('carts')->insertGetId([
                'user_id' => $this->customer->id,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $cartId = $cart->id;
            DB::table('cart_items')->where('cart_id', $cartId)->delete();
        }

        DB::table('cart_items')->insert([
            'cart_id' => $cartId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $cartId;
    }

    /**
     * Test checkout thất bại khi giỏ hàng trống (422 EMPTY_CART)
     */
    public function test_checkout_empty_cart_returns_422_error(): void
    {
        // Xóa sạch items trong cart active của user
        $cart = DB::table('carts')
            ->where('user_id', $this->customer->id)
            ->where('status', 'Active')
            ->first();

        if ($cart) {
            DB::table('cart_items')->where('cart_id', $cart->id)->delete();
        }

        $response = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'COD',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'EMPTY_CART',
                ],
            ]);
    }

    /**
     * Test checkout COD thành công, trừ kho đúng và xóa giỏ hàng
     */
    public function test_checkout_cod_success_and_deducts_inventory(): void
    {
        $variant = $this->prepareProductVariant(10);
        $this->setupCartWithItem($variant->variant_id, 2);

        $response = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'COD',
            'note' => 'Giao trong giờ hành chính',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'Pending',
                    'payment_method' => 'COD',
                    'payment_url' => null,
                    'total_amount' => 18000000, // 9,000,000 * 2
                ],
            ]);

        $orderData = $response->json('data');
        $orderNumber = $orderData['order_number'];

        // Kiểm tra tồn kho đã bị trừ từ 10 xuống 8
        $currentStock = DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity');
        $this->assertEquals(8, $currentStock);

        // Kiểm tra đơn hàng trong DB
        $orderInDb = DB::table('orders')->where('order_number', $orderNumber)->first();
        $this->assertNotNull($orderInDb);
        $this->assertEquals('Pending', $orderInDb->status);

        // Kiểm tra payments trong DB
        $paymentInDb = DB::table('payments')->where('order_id', $orderInDb->id)->first();
        $this->assertNotNull($paymentInDb);
        $this->assertEquals('COD', $paymentInDb->method);
        $this->assertEquals('Pending', $paymentInDb->status);

        // Kiểm tra giỏ hàng đã được xóa sạch
        $cart = DB::table('carts')->where('user_id', $this->customer->id)->where('status', 'Active')->first();
        $itemsInCart = DB::table('cart_items')->where('cart_id', $cart->id)->count();
        $this->assertEquals(0, $itemsInCart);
    }

    /**
     * Test checkout VNPay thành công, sinh payment_url và payment_attempt đầu tiên
     */
    public function test_checkout_vnpay_success_creates_payment_url_and_attempt(): void
    {
        $variant = $this->prepareProductVariant(5);
        $this->setupCartWithItem($variant->variant_id, 1);

        $response = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'VNPay',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'Pending',
                    'payment_method' => 'VNPay',
                ],
            ]);

        $paymentUrl = $response->json('data.payment_url');
        $this->assertNotNull($paymentUrl);
        $this->assertStringContainsString('vnp_SecureHash', $paymentUrl);

        $orderNumber = $response->json('data.order_number');
        $orderInDb = DB::table('orders')->where('order_number', $orderNumber)->first();
        $this->assertNotNull($orderInDb->payment_expires_at);

        $paymentInDb = DB::table('payments')->where('order_id', $orderInDb->id)->first();
        $attempt = DB::table('payment_attempts')->where('payment_id', $paymentInDb->id)->first();
        $this->assertNotNull($attempt);
        $this->assertEquals(1, $attempt->attempt_number);
        $this->assertEquals("{$orderNumber}-1", $attempt->transaction_reference);
        $this->assertEquals('Pending', $attempt->status);
    }

    /**
     * Test checkout thất bại khi kho không đủ số lượng (409 INSUFFICIENT_STOCK)
     */
    public function test_checkout_insufficient_stock_returns_409(): void
    {
        $variant = $this->prepareProductVariant(1);
        $this->setupCartWithItem($variant->variant_id, 5); // Đặt 5 trong khi tồn chỉ có 1

        $response = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'COD',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INSUFFICIENT_STOCK',
                ],
            ]);

        // Đảm bảo tồn kho không bị trừ khi lỗi
        $currentStock = DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity');
        $this->assertEquals(1, $currentStock);
    }

    /**
     * Test khách hàng hủy đơn Pending thì hoàn lại tồn kho nguyên tử
     */
    public function test_cancel_pending_order_restores_inventory(): void
    {
        $variant = $this->prepareProductVariant(10);
        $this->setupCartWithItem($variant->variant_id, 3);

        $checkoutRes = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'COD',
        ]);

        $orderNumber = $checkoutRes->json('data.order_number');

        // Tồn kho hiện tại là 7
        $this->assertEquals(7, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));

        // Hủy đơn
        $cancelRes = $this->actingAs($this->customer)->postJson("/api/orders/{$orderNumber}/cancel", [
            'reason' => 'Đổi ý không mua nữa',
        ]);

        $cancelRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'order_number' => $orderNumber,
                    'status' => 'Cancelled',
                ],
            ]);

        // Tồn kho phải được hoàn lại đúng 10
        $this->assertEquals(10, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));

        // Trạng thái order và payment trong DB là Cancelled
        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $this->assertEquals('Cancelled', $order->status);

        $payment = DB::table('payments')->where('order_id', $order->id)->first();
        $this->assertEquals('Cancelled', $payment->status);
    }

    /**
     * Test VNPay IPN Idempotency: gọi lần 1 thành công, gọi lần 2 trả đã xác nhận
     */
    public function test_vnpay_ipn_idempotency(): void
    {
        $variant = $this->prepareProductVariant(5);
        $this->setupCartWithItem($variant->variant_id, 1);

        $checkoutRes = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'VNPay',
        ]);

        $orderNumber = $checkoutRes->json('data.order_number');
        $amount = (float) $checkoutRes->json('data.total_amount');
        $txnRef = "{$orderNumber}-1";
        $vnpAmount = (string) ((int) round($amount * 100));

        // Chuẩn bị params giả lập từ VNPay
        $secret = config('vnpay.hash_secret');
        $ipnParams = [
            'vnp_Amount' => $vnpAmount,
            'vnp_BankCode' => 'NCB',
            'vnp_BankTranNo' => 'VNP12345678',
            'vnp_CardType' => 'ATM',
            'vnp_OrderInfo' => "Thanh toan don hang {$orderNumber}",
            'vnp_PayDate' => Carbon::now('Asia/Ho_Chi_Minh')->format('YmdHis'),
            'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => config('vnpay.tmn_code'),
            'vnp_TransactionNo' => '14000001',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => $txnRef,
        ];

        ksort($ipnParams);
        $i = 0;
        $hashData = '';
        foreach ($ipnParams as $key => $value) {
            if ($i === 1) {
                $hashData .= '&' . urlencode((string) $key) . '=' . urlencode((string) $value);
            } else {
                $hashData .= urlencode((string) $key) . '=' . urlencode((string) $value);
                $i = 1;
            }
        }
        $ipnParams['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $secret);

        // Lần 1: Xác nhận thành công
        $firstIpnRes = $this->postJson('/api/payments/vnpay/ipn', $ipnParams);
        $firstIpnRes->assertStatus(200)
            ->assertJson([
                'RspCode' => '00',
                'Message' => 'Confirm Success',
            ]);

        // Đơn hàng chuyển sang Confirmed, payment chuyển sang Paid
        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $this->assertEquals('Confirmed', $order->status);

        $payment = DB::table('payments')->where('order_id', $order->id)->first();
        $this->assertEquals('Paid', $payment->status);
        $this->assertNotNull($payment->paid_at);

        // Lần 2: Gửi lại cùng params (Idempotency) -> Trả RspCode '02'
        $secondIpnRes = $this->postJson('/api/payments/vnpay/ipn', $ipnParams);
        $secondIpnRes->assertStatus(200)
            ->assertJson([
                'RspCode' => '02',
                'Message' => 'Order already confirmed',
            ]);
    }

    /**
     * Test Scheduled Command hủy đơn hàng VNPay quá hạn thanh toán và hoàn tồn kho đúng 1 lần
     */
    public function test_expire_unpaid_vnpay_orders_cancels_and_restores_inventory(): void
    {
        $variant = $this->prepareProductVariant(10);
        $this->setupCartWithItem($variant->variant_id, 3);

        $checkoutRes = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'VNPay',
        ]);

        $orderNumber = $checkoutRes->json('data.order_number');

        // Sau khi đặt, tồn kho còn 7
        $this->assertEquals(7, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));

        // Giả lập đơn hàng đã quá hạn thanh toán (đặt payment_expires_at về quá khứ)
        $pastTime = Carbon::now('UTC')->subMinutes(5)->format('Y-m-d H:i:s.u');
        DB::table('orders')->where('order_number', $orderNumber)->update([
            'payment_expires_at' => $pastTime,
        ]);

        // Chạy Artisan command orders:expire-unpaid
        $this->artisan('orders:expire-unpaid')
            ->assertExitCode(0);

        // Đơn hàng và payment chuyển sang Cancelled
        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $this->assertEquals('Cancelled', $order->status);

        $payment = DB::table('payments')->where('order_id', $order->id)->first();
        $this->assertEquals('Cancelled', $payment->status);

        $attempts = DB::table('payment_attempts')->where('payment_id', $payment->id)->get();
        foreach ($attempts as $attempt) {
            $this->assertEquals('Cancelled', $attempt->status);
        }

        // Tồn kho được hoàn lại đầy đủ 10
        $this->assertEquals(10, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));

        // Chạy lại command lần 2: Đảm bảo không hoàn kho lặp lại (Idempotency của Expiry)
        $this->artisan('orders:expire-unpaid')
            ->assertExitCode(0);

        $this->assertEquals(10, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));
    }
}
