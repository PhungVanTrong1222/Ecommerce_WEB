<?php

namespace App\Modules\Order\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    /**
     * Escape ký tự đặc biệt trong LIKE cho SQL Server
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(
            ['[', '%', '_'],
            ['[[]', '[%]', '[_]'],
            $value
        );
    }

    /**
     * Thêm mới đơn hàng
     */
    public function createOrder(array $data): int
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        return (int) DB::table('orders')->insertGetId([
            'order_number' => $data['order_number'],
            'user_id' => $data['user_id'],
            'status' => $data['status'] ?? 'Pending',
            'subtotal' => $data['subtotal'],
            'shipping_fee' => $data['shipping_fee'] ?? 0,
            'total_amount' => $data['total_amount'],
            'shipping_address_snapshot' => $data['shipping_address_snapshot'],
            'payment_method' => $data['payment_method'],
            'payment_expires_at' => $data['payment_expires_at'] ?? null,
            'note' => $data['note'] ?? null,
            'created_at' => $data['created_at'] ?? $now,
            'updated_at' => $data['updated_at'] ?? $now,
        ]);
    }

    /**
     * Cập nhật mã đơn hàng chính thức
     */
    public function updateOrderNumber(int $orderId, string $orderNumber): void
    {
        DB::table('orders')->where('id', $orderId)->update([
            'order_number' => $orderNumber,
        ]);
    }

    /**
     * Thêm danh sách mặt hàng của đơn hàng
     */
    public function createOrderItems(array $items): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        $records = [];

        foreach ($items as $item) {
            $records[] = [
                'order_id' => $item['order_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'product_name' => $item['product_name'],
                'variant_name' => $item['variant_name'],
                'sku' => $item['sku'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['subtotal'],
                'created_at' => $item['created_at'] ?? $now,
            ];
        }

        DB::table('order_items')->insert($records);
    }

    /**
     * Tìm đơn hàng theo ID
     */
    public function findOrderById(int $id): ?object
    {
        return DB::table('orders')->where('id', $id)->first();
    }

    /**
     * Tìm đơn hàng theo ID kèm khóa dòng
     */
    public function findOrderByIdForUpdate(int $id): ?object
    {
        return DB::selectOne(
            "SELECT * FROM orders WITH (UPDLOCK, ROWLOCK) WHERE id = ?",
            [$id]
        );
    }

    /**
     * Tìm đơn hàng theo mã đơn
     */
    public function findOrderByNumber(string $orderNumber): ?object
    {
        return DB::table('orders')->where('order_number', $orderNumber)->first();
    }

    /**
     * Tìm đơn hàng theo mã đơn kèm khóa dòng chống race condition
     */
    public function findOrderByNumberForUpdate(string $orderNumber): ?object
    {
        return DB::selectOne(
            "SELECT * FROM orders WITH (UPDLOCK, ROWLOCK) WHERE order_number = ?",
            [$orderNumber]
        );
    }

    /**
     * Tìm đơn hàng của một người dùng theo mã đơn
     */
    public function findUserOrderByNumber(int $userId, string $orderNumber): ?object
    {
        return DB::table('orders')
            ->where('order_number', $orderNumber)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Tìm đơn hàng của user và khóa dòng
     */
    public function findUserOrderByNumberForUpdate(int $userId, string $orderNumber): ?object
    {
        return DB::selectOne(
            "SELECT * FROM orders WITH (UPDLOCK, ROWLOCK) WHERE order_number = ? AND user_id = ?",
            [$orderNumber, $userId]
        );
    }

    /**
     * Lấy các items trong đơn hàng
     */
    public function getOrderItems(int $orderId): Collection
    {
        return DB::table('order_items')
            ->where('order_id', $orderId)
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Cập nhật trạng thái đơn hàng
     */
    public function updateOrderStatus(int $orderId, string $status, ?string $note = null): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        $data = [
            'status' => $status,
            'updated_at' => $now,
        ];

        if ($note !== null) {
            $data['note'] = $note;
        }

        DB::table('orders')->where('id', $orderId)->update($data);
    }

    /**
     * Đếm số lượng đơn hàng của khách hàng
     */
    public function countUserOrders(int $userId, array $filters = []): int
    {
        $query = DB::table('orders')->where('user_id', $userId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->count();
    }

    /**
     * Lấy danh sách đơn hàng của khách hàng phân trang
     */
    public function listUserOrders(int $userId, array $filters, int $page, int $pageSize): Collection
    {
        $query = DB::table('orders')
            ->where('orders.user_id', $userId)
            ->leftJoin('payments', 'orders.id', '=', 'payments.order_id')
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.status as order_status',
                'orders.subtotal',
                'orders.shipping_fee',
                'orders.total_amount',
                'orders.payment_method',
                'orders.payment_expires_at',
                'orders.created_at',
                'payments.status as payment_status',
            ]);

        if (!empty($filters['status'])) {
            $query->where('orders.status', $filters['status']);
        }

        $offset = ($page - 1) * $pageSize;

        return $query->orderBy('orders.id', 'desc')
            ->offset($offset)
            ->limit($pageSize)
            ->get();
    }

    /**
     * Đếm tổng số đơn hàng cho Admin
     */
    public function countAdminOrders(array $filters = []): int
    {
        $query = DB::table('orders')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id');

        if (!empty($filters['status'])) {
            $query->where('orders.status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('orders.payment_method', $filters['payment_method']);
        }

        if (!empty($filters['q'])) {
            $escaped = $this->escapeLike($filters['q']);
            $query->where(function ($q) use ($escaped) {
                $q->where('orders.order_number', 'like', "%{$escaped}%")
                  ->orWhere('users.email', 'like', "%{$escaped}%")
                  ->orWhere('users.fullname', 'like', "%{$escaped}%");
            });
        }

        return $query->count();
    }

    /**
     * Lấy danh sách đơn hàng cho Admin phân trang
     */
    public function listAdminOrders(array $filters, int $page, int $pageSize): Collection
    {
        $query = DB::table('orders')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->leftJoin('payments', 'orders.id', '=', 'payments.order_id')
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.user_id',
                'users.fullname as user_fullname',
                'users.email as user_email',
                'orders.status as order_status',
                'orders.subtotal',
                'orders.shipping_fee',
                'orders.total_amount',
                'orders.payment_method',
                'orders.payment_expires_at',
                'orders.created_at',
                'orders.updated_at',
                'payments.status as payment_status',
            ]);

        if (!empty($filters['status'])) {
            $query->where('orders.status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('orders.payment_method', $filters['payment_method']);
        }

        if (!empty($filters['q'])) {
            $escaped = $this->escapeLike($filters['q']);
            $query->where(function ($q) use ($escaped) {
                $q->where('orders.order_number', 'like', "%{$escaped}%")
                  ->orWhere('users.email', 'like', "%{$escaped}%")
                  ->orWhere('users.fullname', 'like', "%{$escaped}%");
            });
        }

        $offset = ($page - 1) * $pageSize;

        return $query->orderBy('orders.id', 'desc')
            ->offset($offset)
            ->limit($pageSize)
            ->get();
    }

    /**
     * Tìm danh sách các đơn hàng VNPay quá hạn thanh toán chưa được xác nhận
     */
    public function findExpiredUnpaidOrders(): Collection
    {
        return collect(DB::select(
            "SELECT * FROM orders 
             WHERE status = 'Pending' 
               AND payment_method = 'VNPay' 
               AND payment_expires_at IS NOT NULL 
               AND payment_expires_at <= SYSUTCDATETIME()"
        ));
    }
}
