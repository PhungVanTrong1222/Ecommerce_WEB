<?php

namespace App\Modules\Catalog\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Requests\AdminInventoryAdjustRequest;
use App\Modules\Catalog\Services\InventoryService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * GET /api/admin/inventory
     * Danh sách tồn kho theo variant (hỗ trợ filter low_stock=1, q, phân trang)
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->inventoryService->getInventoryList($request->all());

        return ApiResponse::paginated(
            $result['items'],
            $result['page'],
            $result['pageSize'],
            $result['total']
        );
    }

    /**
     * POST /api/admin/inventory/{variantId}/adjust
     * Điều chỉnh số lượng tồn kho nguyên tử
     */
    public function adjust(int $variantId, AdminInventoryAdjustRequest $request): JsonResponse
    {
        $delta = (int) $request->input('delta');
        $reason = (string) $request->input('reason');
        $userId = $request->user()?->id;

        $inventory = $this->inventoryService->adjustInventory($variantId, $delta, $reason, $userId);

        return ApiResponse::success($inventory);
    }
}
