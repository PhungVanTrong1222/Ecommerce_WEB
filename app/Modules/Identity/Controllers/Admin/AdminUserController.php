<?php

namespace App\Modules\Identity\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Requests\AdminUserStatusRequest;
use App\Modules\Identity\Services\AdminUserService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(
        protected AdminUserService $adminUserService
    ) {}

    /**
     * GET /api/admin/users
     * Lấy danh sách người dùng phân trang với các bộ lọc: status, role, q.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $page = max((int) $request->query('page', 1), 1);
        $pageSize = min(max((int) $request->query('page_size', $request->query('limit', 15)), 1), 100);

        $filters = [
            'status' => $request->query('status'),
            'role' => $request->query('role'),
            'q' => $request->query('q'),
        ];

        $result = $this->adminUserService->getPaginatedUsers($filters, $page, $pageSize);

        return ApiResponse::paginated(
            $result['items'],
            $page,
            $pageSize,
            $result['total']
        );
    }

    /**
     * PATCH /api/admin/users/{id}/status
     * Cập nhật trạng thái của người dùng (Active / Suspended).
     *
     * @param AdminUserStatusRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(AdminUserStatusRequest $request, int $id): JsonResponse
    {
        $status = $request->validated()['status'];
        $currentAdminId = (int) $request->user()->id;

        $updatedUser = $this->adminUserService->updateStatus($currentAdminId, $id, $status);

        return ApiResponse::success($updatedUser);
    }
}
