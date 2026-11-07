<?php

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Services\PaymentService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * POST /api/payments/vnpay/create
     * Tạo lại link thanh toán VNPay cho đơn hàng Pending
     */
    public function createVnPay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:20'],
        ]);

        $userId = (int) $request->user()->id;
        $ipAddr = (string) ($request->ip() ?? '127.0.0.1');

        $result = $this->paymentService->createVnPayRetryUrl(
            $userId,
            $validated['order_number'],
            $ipAddr
        );

        return ApiResponse::success($result);
    }

    /**
     * GET /api/payments/vnpay/return
     * VNPay redirect sau khi thanh toán trên trình duyệt của khách hàng
     */
    public function vnpayReturn(Request $request): JsonResponse
    {
        $result = $this->paymentService->handleVnPayReturn($request->query());

        return ApiResponse::success($result);
    }

    /**
     * GET|POST /api/payments/vnpay/ipn
     * Webhook từ máy chủ VNPay
     */
    public function vnpayIpn(Request $request): JsonResponse
    {
        $result = $this->paymentService->handleVnPayIpn($request->all());

        // VNPay IPN yêu cầu định dạng response thô dạng {"RspCode":"00","Message":"Confirm Success"}
        return response()->json($result, 200);
    }
}
