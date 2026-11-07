<?php

namespace App\Modules\Order\Services;

use App\Modules\Cart\Repositories\CartRepository;
use App\Modules\Catalog\Repositories\InventoryRepository;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Payment\Repositories\PaymentRepository;
use App\Modules\Payment\Services\VnPayService;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected CartRepository $cartRepository,
        protected InventoryRepository $inventoryRepository,
        protected PaymentRepository $paymentRepository,
        protected VnPayService $vnPayService
    ) {}

    /**
     * Xử lý đặt hàng (Checkout)
     *
     * @param int $userId
     * @param array $data
     * @param string|null $clientIp
     * @return array
     */
    public function checkout(int $userId, array $data, ?string $clientIp = '127.0.0.1'): array
    {
        return DB::transaction(function () use ($userId, $data, $clientIp) {
            // 1. Tìm Active Cart của user
            $cart = $this->cartRepository->findActiveByUserId($userId);
            if (!$cart) {
                throw new BusinessException('EMPTY_CART', 'Giỏ hàng của bạn đang trống', 422);
            }

            $rawItems = $this->cartRepository->getItemsWithDetails((int) $cart->id);
            if ($rawItems->isEmpty()) {
                throw new BusinessException('EMPTY_CART', 'Giỏ hàng của bạn đang trống', 422);
            }

            // 2. Sắp xếp items theo variant_id ASC để tránh deadlock khi trừ kho đồng thời
            $items = $rawItems->sortBy('variant_id')->values();

            // 3. Kiểm tra tính khả dụng và trừ kho nguyên tử
            $calculatedItems = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                // Kiểm tra trạng thái Product, Category, Variant
                if (
                    $item->product_status !== 'Active' ||
                    $item->category_status !== 'Active' ||
                    $item->variant_status !== 'Active'
                ) {
                    throw new BusinessException(
                        'ITEM_UNAVAILABLE',
                        "Sản phẩm '{$item->product_name} ({$item->variant_name})' đã ngừng kinh doanh",
                        409
                    );
                }

                $quantity = (int) $item->quantity;
                $variantId = (int) $item->variant_id;

                // Trừ tồn kho nguyên tử bằng T-SQL
                $decremented = $this->inventoryRepository->decrement($variantId, $quantity);
                if (!$decremented) {
                    throw new BusinessException(
                        'INSUFFICIENT_STOCK',
                        "Sản phẩm '{$item->product_name} ({$item->variant_name})' không đủ số lượng tồn kho",
                        409
                    );
                }

                // 4. Tính tiền từng item ở backend
                $unitPrice = $item->sale_price !== null ? (float) $item->sale_price : (float) $item->price;
                $itemSubtotal = round($unitPrice * $quantity, 2);
                $subtotal += $itemSubtotal;

                $calculatedItems[] = [
                    'variant_id' => $variantId,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'sku' => $item->sku,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $shippingFee = 0.0;
            $totalAmount = round($subtotal + $shippingFee, 2);

            $paymentMethod = $data['payment_method'];
            $now = Carbon::now('UTC');
            $nowFormatted = $now->format('Y-m-d H:i:s.u');

            $paymentExpiresAt = $paymentMethod === 'VNPay'
                ? $now->copy()->addMinutes(15)->format('Y-m-d H:i:s.u')
                : null;

            $shippingAddressSnapshot = json_encode([
                'fullname' => trim($data['fullname']),
                'phone' => trim($data['phone']),
                'address' => trim($data['address']),
            ], JSON_UNESCAPED_UNICODE);

            // 5. Chuẩn bị orders: sinh order_number tạm thời không trùng
            $tempOrderNumber = 'TMP' . substr(str_replace('-', '', (string) Str::uuid()), 0, 17);

            $orderId = $this->orderRepository->createOrder([
                'order_number' => $tempOrderNumber,
                'user_id' => $userId,
                'status' => 'Pending',
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'shipping_address_snapshot' => $shippingAddressSnapshot,
                'payment_method' => $paymentMethod,
                'payment_expires_at' => $paymentExpiresAt,
                'note' => !empty($data['note']) ? trim($data['note']) : null,
                'created_at' => $nowFormatted,
                'updated_at' => $nowFormatted,
            ]);

            $orderNumber = 'ORD-' . str_pad((string) $orderId, 8, '0', STR_PAD_LEFT);
            $this->orderRepository->updateOrderNumber($orderId, $orderNumber);

            // 6. Thêm order_items
            foreach ($calculatedItems as &$cItem) {
                $cItem['order_id'] = $orderId;
                $cItem['created_at'] = $nowFormatted;
            }
            unset($cItem);

            $this->orderRepository->createOrderItems($calculatedItems);

            // 7. Tạo bản ghi payments
            $paymentId = $this->paymentRepository->createPayment([
                'order_id' => $orderId,
                'method' => $paymentMethod,
                'status' => 'Pending',
                'amount' => $totalAmount,
                'paid_at' => null,
                'created_at' => $nowFormatted,
                'updated_at' => $nowFormatted,
            ]);

            // 8. Nếu là VNPay: tạo payment_attempts đầu tiên & sinh payment_url
            $paymentUrl = null;
            if ($paymentMethod === 'VNPay') {
                $transactionReference = $orderNumber . '-1';

                $this->paymentRepository->createAttempt([
                    'payment_id' => $paymentId,
                    'attempt_number' => 1,
                    'transaction_reference' => $transactionReference,
                    'status' => 'Pending',
                    'amount' => $totalAmount,
                    'created_at' => $nowFormatted,
                    'updated_at' => $nowFormatted,
                ]);

                $paymentUrl = $this->vnPayService->buildPaymentUrl(
                    $orderNumber,
                    $transactionReference,
                    $totalAmount,
                    $clientIp ?: '127.0.0.1'
                );
            }

            // 9. Xóa giỏ hàng
            $this->cartRepository->clearCartItems((int) $cart->id);

            // 10. Trả về kết quả với các trường ID được ép kiểu (string) theo chuẩn DTO
            $formattedItems = array_map(function ($cItem) {
                return [
                    'order_id' => (string) $cItem['order_id'],
                    'variant_id' => $cItem['variant_id'] !== null ? (string) $cItem['variant_id'] : null,
                    'product_name' => $cItem['product_name'],
                    'variant_name' => $cItem['variant_name'],
                    'sku' => $cItem['sku'],
                    'unit_price' => $cItem['unit_price'],
                    'quantity' => $cItem['quantity'],
                    'subtotal' => $cItem['subtotal'],
                ];
            }, $calculatedItems);

            return [
                'order_id' => (string) $orderId,
                'order_number' => $orderNumber,
                'status' => 'Pending',
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'payment_expires_at' => $paymentExpiresAt,
                'shipping_address' => json_decode($shippingAddressSnapshot, true),
                'items' => $formattedItems,
                'payment' => [
                    'id' => (string) $paymentId,
                    'order_id' => (string) $orderId,
                    'method' => $paymentMethod,
                    'status' => 'Pending',
                    'amount' => $totalAmount,
                    'paid_at' => null,
                ],
                'payment_url' => $paymentUrl,
            ];
        });
    }
}
