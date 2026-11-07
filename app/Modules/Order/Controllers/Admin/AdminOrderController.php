<?php

namespace App\Modules\Order\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Order\Requests\AdminOrderStatusRequest;
use App\Modules\Order\Services\OrderService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * GET /api/admin/orders
     * Lấy danh sách tất cả đơn hàng cho Admin phân trang
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'payment_method', 'q', 'page', 'page_size']);
        $result = $this->orderService->listAdminOrders($filters);

        return ApiResponse::paginated(
            $result['items'],
            $result['page'],
            $result['page_size'],
            $result['total']
        );
    }

    /**
     * GET /api/admin/orders/{orderNumber}
     * Xem chi tiết đầy đủ đơn hàng dành cho Admin
     */
    public function show(string $orderNumber): JsonResponse
    {
        $result = $this->orderService->getAdminOrderDetail($orderNumber);

        return ApiResponse::success($result);
    }

    /**
     * PATCH /api/admin/orders/{orderNumber}/status
     * Cập nhật trạng thái đơn hàng theo luồng State Machine
     */
    public function updateStatus(AdminOrderStatusRequest $request, string $orderNumber): JsonResponse
    {
        $result = $this->orderService->updateOrderStatus($orderNumber, $request->input('status'));

        return ApiResponse::success($result);
    }
}
