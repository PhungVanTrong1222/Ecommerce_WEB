<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Repositories\PaymentRepository;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected PaymentRepository $paymentRepository,
        protected VnPayService $vnPayService
    ) {}

    /**
     * Tạo lại link thanh toán VNPay cho đơn hàng Pending chưa hết hạn
     */
    public function createVnPayRetryUrl(int $userId, string $orderNumber, string $ipAddr): array
    {
        return DB::transaction(function () use ($userId, $orderNumber, $ipAddr) {
            $order = DB::selectOne(
                "SELECT * FROM orders WITH (UPDLOCK, ROWLOCK) WHERE order_number = ? AND user_id = ?",
                [$orderNumber, $userId]
            );

            if (!$order) {
                throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy đơn hàng', 404);
            }

            $payment = $this->paymentRepository->findPaymentByOrderIdForUpdate((int) $order->id);
            if (!$payment) {
                throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy thông tin thanh toán của đơn hàng', 404);
            }

            $nowUtc = Carbon::now('UTC');
            $expiresAt = $order->payment_expires_at ? Carbon::parse($order->payment_expires_at, 'UTC') : null;

            $isNotExpired = $expiresAt && $expiresAt->greaterThan($nowUtc);

            if (
                $order->payment_method !== 'VNPay' ||
                $order->status !== 'Pending' ||
                $payment->status === 'Paid' ||
                !$isNotExpired
            ) {
                throw new BusinessException(
                    'PAYMENT_NOT_ALLOWED',
                    'Đơn hàng không thể thanh toán hoặc đã quá hạn thanh toán',
                    409
                );
            }

            // Hủy các attempts đang Pending/Processing cũ
            $this->paymentRepository->cancelPendingAttempts((int) $payment->id);

            // Tạo attempt mới
            $maxAttempt = $this->paymentRepository->getMaxAttemptNumber((int) $payment->id);
            $newAttemptNumber = $maxAttempt + 1;
            $transactionReference = "{$orderNumber}-{$newAttemptNumber}";

            $this->paymentRepository->createAttempt([
                'payment_id' => $payment->id,
                'attempt_number' => $newAttemptNumber,
                'transaction_reference' => $transactionReference,
                'status' => 'Pending',
                'amount' => $payment->amount,
            ]);

            $paymentUrl = $this->vnPayService->buildPaymentUrl(
                $orderNumber,
                $transactionReference,
                (float) $payment->amount,
                $ipAddr
            );

            return [
                'payment_url' => $paymentUrl,
                'transaction_reference' => $transactionReference,
            ];
        });
    }

    /**
     * Xử lý VNPay Return URL (chỉ đọc để hiển thị UI, không cập nhật database)
     */
    public function handleVnPayReturn(array $queryParams): array
    {
        if (!$this->vnPayService->verifySignature($queryParams)) {
            throw new BusinessException('INVALID_SIGNATURE', 'Chữ ký VNPay không hợp lệ', 400);
        }

        $txnRef = (string) ($queryParams['vnp_TxnRef'] ?? '');
        $attempt = $this->paymentRepository->findAttemptByRef($txnRef);

        $orderNumber = null;
        $paymentStatus = null;
        $order = null;
        $payment = null;

        if ($attempt) {
            $payment = $this->paymentRepository->findPaymentById((int) $attempt->payment_id);
            if ($payment) {
                $paymentStatus = $payment->status;
                $order = DB::table('orders')->where('id', $payment->order_id)->first();
                if ($order) {
                    $orderNumber = $order->order_number;
                }
            }
        }

        if (!$orderNumber && !empty($txnRef)) {
            // Fallback trích xuất order_number từ txnRef: format "ORD-00000001-1"
            $parts = explode('-', $txnRef);
            if (count($parts) >= 2) {
                array_pop($parts);
                $orderNumber = implode('-', $parts);
                $order = DB::table('orders')->where('order_number', $orderNumber)->first();
            }
        }

        $responseCode = $queryParams['vnp_ResponseCode'] ?? null;
        $transactionStatus = $queryParams['vnp_TransactionStatus'] ?? '00';
        $isSuccess = ($responseCode === '00') && ($transactionStatus === '00');
        $amount = isset($queryParams['vnp_Amount'])
            ? ((float) $queryParams['vnp_Amount'] / 100)
            : ($payment ? (float) $payment->amount : 0.0);

        return [
            // Các trường cũ
            'signature_valid' => true,
            'vnp_response_code' => $responseCode,
            'transaction_reference' => $txnRef,
            'order_number' => $orderNumber,
            'payment_status' => $paymentStatus,
            // Các trường bổ sung đồng bộ với frontend order-return.js
            'is_valid_signature' => true,
            'is_success' => $isSuccess,
            'response_code' => $responseCode,
            'transaction_no' => $queryParams['vnp_TransactionNo'] ?? null,
            'amount' => $amount,
            'order_status' => $order->status ?? null,
            'message' => ($responseCode === '00')
                ? 'Giao dịch thanh toán VNPay thành công'
                : 'Giao dịch thanh toán không thành công hoặc đã bị hủy',
        ];
    }

    /**
     * Xử lý VNPay IPN (Webhook từ server VNPay)
     */
    public function handleVnPayIpn(array $params): array
    {
        // 1. Kiểm tra chữ ký
        if (!$this->vnPayService->verifySignature($params)) {
            return ['RspCode' => '97', 'Message' => 'Invalid signature'];
        }

        $txnRef = (string) ($params['vnp_TxnRef'] ?? '');
        $vnpAmount = (int) ($params['vnp_Amount'] ?? 0);

        // 2. Tìm payment attempt theo transaction reference
        $attemptCheck = $this->paymentRepository->findAttemptByRef($txnRef);
        if (!$attemptCheck) {
            return ['RspCode' => '01', 'Message' => 'Order not found'];
        }

        $paymentCheck = $this->paymentRepository->findPaymentById((int) $attemptCheck->payment_id);
        if (!$paymentCheck) {
            return ['RspCode' => '01', 'Message' => 'Order not found'];
        }

        $orderId = (int) $paymentCheck->order_id;

        // 3. Kiểm tra số tiền
        $expectedAmount = (int) round(((float) $attemptCheck->amount) * 100);
        if ($vnpAmount !== $expectedAmount) {
            return ['RspCode' => '04', 'Message' => 'Invalid amount'];
        }

        // 4. Xử lý trong transaction kèm lock dòng theo thứ tự phân cấp nghiêm ngặt:
        // (1) orders -> (2) payments -> (3) payment_attempts để tránh Deadlock vòng tròn với cancelUserOrder / expireUnpaidOrders
        return DB::transaction(function () use ($params, $txnRef, $orderId, $paymentCheck) {
            // (1) Khóa orders
            $order = DB::selectOne(
                "SELECT * FROM orders WITH (UPDLOCK, ROWLOCK) WHERE id = ?",
                [$orderId]
            );
            if (!$order) {
                return ['RspCode' => '01', 'Message' => 'Order record not found'];
            }

            // (2) Khóa payments
            $payment = $this->paymentRepository->findPaymentByIdForUpdate((int) $paymentCheck->id);
            if (!$payment) {
                return ['RspCode' => '01', 'Message' => 'Payment not found'];
            }

            // (3) Khóa payment_attempts
            $attempt = $this->paymentRepository->findAttemptByRefForUpdate($txnRef);
            if (!$attempt) {
                return ['RspCode' => '01', 'Message' => 'Order not found'];
            }

            // Idempotency: Nếu attempt hoặc payment đã Paid
            if ($attempt->status === 'Paid' || $payment->status === 'Paid') {
                return ['RspCode' => '02', 'Message' => 'Order already confirmed'];
            }

            // Chống xung đột với Expiry / Cancel: Nếu order đã bị Cancelled trước đó
            if ($order->status === 'Cancelled') {
                $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
                $this->paymentRepository->updateAttempt((int) $attempt->id, [
                    'gateway_transaction_id' => $params['vnp_TransactionNo'] ?? null,
                    'gateway_response' => json_encode($params, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                ]);

                return ['RspCode' => '02', 'Message' => 'Order already cancelled'];
            }

            $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
            $responseCode = (string) ($params['vnp_ResponseCode'] ?? '');
            $transactionStatus = (string) ($params['vnp_TransactionStatus'] ?? '');

            if ($responseCode === '00' && $transactionStatus === '00') {
                // Thanh toán thành công tại cổng
                $this->paymentRepository->updateAttempt((int) $attempt->id, [
                    'status' => 'Paid',
                    'gateway_transaction_id' => $params['vnp_TransactionNo'] ?? null,
                    'gateway_response' => json_encode($params, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                ]);

                $this->paymentRepository->updatePayment((int) $payment->id, [
                    'status' => 'Paid',
                    'paid_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('orders')->where('id', $order->id)->update([
                    'status' => 'Confirmed',
                    'updated_at' => $now,
                ]);
            } else {
                // Giao dịch không thành công
                $this->paymentRepository->updateAttempt((int) $attempt->id, [
                    'status' => 'Failed',
                    'gateway_transaction_id' => $params['vnp_TransactionNo'] ?? null,
                    'gateway_response' => json_encode($params, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                ]);

                $this->paymentRepository->updatePayment((int) $payment->id, [
                    'status' => 'Failed',
                    'updated_at' => $now,
                ]);

                // Giữ nguyên orders.status = 'Pending' cho đến khi hết hạn payment_expires_at
            }

            return ['RspCode' => '00', 'Message' => 'Confirm Success'];
        });
    }

    /**
     * Admin xác nhận đã thu tiền COD
     */
    public function confirmCodPayment(string $orderNumber): array
    {
        return DB::transaction(function () use ($orderNumber) {
            $order = DB::selectOne(
                "SELECT * FROM orders WITH (UPDLOCK, ROWLOCK) WHERE order_number = ?",
                [$orderNumber]
            );

            if (!$order) {
                throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy đơn hàng', 404);
            }

            $payment = $this->paymentRepository->findPaymentByOrderIdForUpdate((int) $order->id);
            if (!$payment) {
                throw new BusinessException('RESOURCE_NOT_FOUND', 'Không tìm thấy thông tin thanh toán', 404);
            }

            if (
                $payment->method !== 'COD' ||
                $payment->status !== 'Pending' ||
                !in_array($order->status, ['Shipped', 'Delivered'])
            ) {
                throw new BusinessException(
                    'INVALID_PAYMENT_STATUS',
                    'Chỉ có thể xác nhận thanh toán COD cho đơn hàng ở trạng thái Shipped hoặc Delivered và đang chờ thanh toán',
                    409
                );
            }

            $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
            $this->paymentRepository->updatePayment((int) $payment->id, [
                'status' => 'Paid',
                'paid_at' => $now,
                'updated_at' => $now,
            ]);

            $updatedPayment = (array) $this->paymentRepository->findPaymentById((int) $payment->id);
            if (isset($updatedPayment['id'])) {
                $updatedPayment['id'] = (string) $updatedPayment['id'];
            }
            if (isset($updatedPayment['order_id'])) {
                $updatedPayment['order_id'] = (string) $updatedPayment['order_id'];
            }

            return [
                'order_number' => $order->order_number,
                'payment' => $updatedPayment,
            ];
        });
    }

    /**
     * Danh sách payment cho Admin
     */
    public function listAdminPayments(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['page_size'] ?? 15)));

        $total = $this->paymentRepository->countAdminPayments($filters);
        $rawItems = $this->paymentRepository->listAdminPayments($filters, $page, $pageSize)->toArray();

        $items = array_map(function ($item) {
            $arr = (array) $item;
            if (isset($arr['id'])) {
                $arr['id'] = (string) $arr['id'];
            }
            if (isset($arr['order_id'])) {
                $arr['order_id'] = (string) $arr['order_id'];
            }
            if (isset($arr['user_id']) && $arr['user_id'] !== null) {
                $arr['user_id'] = (string) $arr['user_id'];
            }
            return $arr;
        }, $rawItems);

        return [
            'items' => $items,
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
        ];
    }
}
