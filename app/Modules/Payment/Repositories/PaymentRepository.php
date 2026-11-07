<?php

namespace App\Modules\Payment\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentRepository
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
     * Tìm payment theo order_id
     */
    public function findPaymentByOrderId(int $orderId): ?object
    {
        return DB::table('payments')->where('order_id', $orderId)->first();
    }

    /**
     * Tìm payment theo order_id và khóa dòng với UPDLOCK, ROWLOCK
     */
    public function findPaymentByOrderIdForUpdate(int $orderId): ?object
    {
        return DB::selectOne(
            "SELECT * FROM payments WITH (UPDLOCK, ROWLOCK) WHERE order_id = ?",
            [$orderId]
        );
    }

    /**
     * Tìm payment theo ID
     */
    public function findPaymentById(int $paymentId): ?object
    {
        return DB::table('payments')->where('id', $paymentId)->first();
    }

    /**
     * Tìm payment theo ID và khóa dòng với UPDLOCK, ROWLOCK
     */
    public function findPaymentByIdForUpdate(int $paymentId): ?object
    {
        return DB::selectOne(
            "SELECT * FROM payments WITH (UPDLOCK, ROWLOCK) WHERE id = ?",
            [$paymentId]
        );
    }

    /**
     * Tạo bản ghi payment mới
     */
    public function createPayment(array $data): int
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        return (int) DB::table('payments')->insertGetId([
            'order_id' => $data['order_id'],
            'method' => $data['method'],
            'status' => $data['status'] ?? 'Pending',
            'amount' => $data['amount'],
            'paid_at' => $data['paid_at'] ?? null,
            'created_at' => $data['created_at'] ?? $now,
            'updated_at' => $data['updated_at'] ?? $now,
        ]);
    }

    /**
     * Cập nhật thông tin payment
     */
    public function updatePayment(int $paymentId, array $data): void
    {
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        }

        DB::table('payments')->where('id', $paymentId)->update($data);
    }

    /**
     * Tìm payment_attempt theo transaction_reference
     */
    public function findAttemptByRef(string $txnRef): ?object
    {
        return DB::table('payment_attempts')->where('transaction_reference', $txnRef)->first();
    }

    /**
     * Tìm payment_attempt theo transaction_reference và khóa dòng
     */
    public function findAttemptByRefForUpdate(string $txnRef): ?object
    {
        return DB::selectOne(
            "SELECT * FROM payment_attempts WITH (UPDLOCK, ROWLOCK) WHERE transaction_reference = ?",
            [$txnRef]
        );
    }

    /**
     * Lấy số lần attempt lớn nhất của một payment
     */
    public function getMaxAttemptNumber(int $paymentId): int
    {
        $max = DB::table('payment_attempts')
            ->where('payment_id', $paymentId)
            ->max('attempt_number');

        return $max ? (int) $max : 0;
    }

    /**
     * Tạo một payment_attempt mới
     */
    public function createAttempt(array $data): int
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        return (int) DB::table('payment_attempts')->insertGetId([
            'payment_id' => $data['payment_id'],
            'attempt_number' => $data['attempt_number'],
            'transaction_reference' => $data['transaction_reference'],
            'gateway_transaction_id' => $data['gateway_transaction_id'] ?? null,
            'status' => $data['status'] ?? 'Pending',
            'amount' => $data['amount'],
            'gateway_response' => $data['gateway_response'] ?? null,
            'created_at' => $data['created_at'] ?? $now,
            'updated_at' => $data['updated_at'] ?? $now,
        ]);
    }

    /**
     * Cập nhật payment_attempt
     */
    public function updateAttempt(int $attemptId, array $data): void
    {
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        }

        DB::table('payment_attempts')->where('id', $attemptId)->update($data);
    }

    /**
     * Hủy các payment_attempts đang ở trạng thái Pending hoặc Processing
     */
    public function cancelPendingAttempts(int $paymentId): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        DB::table('payment_attempts')
            ->where('payment_id', $paymentId)
            ->whereIn('status', ['Pending', 'Processing'])
            ->update([
                'status' => 'Cancelled',
                'updated_at' => $now,
            ]);
    }

    /**
     * Lấy danh sách attempts của payment
     */
    public function getAttemptsByPaymentId(int $paymentId): Collection
    {
        return DB::table('payment_attempts')
            ->where('payment_id', $paymentId)
            ->orderBy('attempt_number', 'asc')
            ->get();
    }

    /**
     * Đếm tổng số payments cho trang Admin
     */
    public function countAdminPayments(array $filters = []): int
    {
        $query = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id');

        if (!empty($filters['status'])) {
            $query->where('payments.status', $filters['status']);
        }

        if (!empty($filters['method'])) {
            $query->where('payments.method', $filters['method']);
        }

        if (!empty($filters['q'])) {
            $escaped = $this->escapeLike($filters['q']);
            $query->where('orders.order_number', 'like', "%{$escaped}%");
        }

        return $query->count();
    }

    /**
     * Lấy danh sách payments phân trang cho Admin
     */
    public function listAdminPayments(array $filters, int $page, int $pageSize): Collection
    {
        $query = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->select([
                'payments.id',
                'payments.order_id',
                'orders.order_number',
                'orders.status as order_status',
                'users.id as user_id',
                'users.fullname as user_fullname',
                'users.email as user_email',
                'payments.method',
                'payments.status',
                'payments.amount',
                'payments.paid_at',
                'payments.created_at',
                'payments.updated_at',
            ]);

        if (!empty($filters['status'])) {
            $query->where('payments.status', $filters['status']);
        }

        if (!empty($filters['method'])) {
            $query->where('payments.method', $filters['method']);
        }

        if (!empty($filters['q'])) {
            $escaped = $this->escapeLike($filters['q']);
            $query->where('orders.order_number', 'like', "%{$escaped}%");
        }

        $offset = ($page - 1) * $pageSize;

        return $query->orderBy('payments.id', 'desc')
            ->offset($offset)
            ->limit($pageSize)
            ->get();
    }
}
