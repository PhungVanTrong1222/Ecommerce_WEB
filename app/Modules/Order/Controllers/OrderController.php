<?php

namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Requests\CancelOrderRequest;
use App\Modules\Order\Requests\CheckoutRequest;
use App\Modules\Order\Services\CheckoutService;
use App\Modules\Order\Services\OrderService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected OrderService $orderService
    ) {}

    /**
     * POST /api/orders/checkout
     * Tiến hành đặt hàng từ giỏ hàng hiện tại
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $clientIp = (string) ($request->ip() ?? '127.0.0.1');

        $result = $this->checkoutService->checkout($userId, $request->validated(), $clientIp);

        return ApiResponse::success($result, 201);
    }

    /**
     * GET /api/orders
     * Lấy danh sách đơn hàng của người dùng hiện tại
     */
    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $filters = $request->only(['status', 'page', 'page_size']);

        $result = $this->orderService->listUserOrders($userId, $filters);

        return ApiResponse::paginated(
            $result['items'],
            $result['page'],
            $result['page_size'],
            $result['total']
        );
    }

    /**
     * GET /api/orders/{orderNumber}
     * Lấy chi tiết một đơn hàng của người dùng hiện tại
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $result = $this->orderService->getUserOrderDetail($userId, $orderNumber);

        return ApiResponse::success($result);
    }

    /**
     * POST /api/orders/{orderNumber}/cancel
     * Người dùng tự hủy đơn hàng đang Pending
     */
    public function cancel(CancelOrderRequest $request, string $orderNumber): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $reason = $request->input('reason');

        $result = $this->orderService->cancelUserOrder($userId, $orderNumber, $reason);

        return ApiResponse::success($result);
    }
}
