<?php

namespace App\Modules\Catalog\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Requests\AdminVariantRequest;
use App\Modules\Catalog\Requests\AdminVariantUpdateRequest;
use App\Modules\Catalog\Services\VariantService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminVariantController extends Controller
{
    public function __construct(
        protected VariantService $variantService
    ) {}

    /**
     * POST /api/admin/products/{id}/variants
     * Thêm biến thể và tạo kho ban đầu cho sản phẩm
     */
    public function store(int $id, AdminVariantRequest $request): JsonResponse
    {
        $variant = $this->variantService->addVariant($id, $request->validated());
        return ApiResponse::success($variant, 201);
    }

    /**
     * PATCH /api/admin/variants/{id}
     * Cập nhật thông tin biến thể (variant_name, price, sale_price, status)
     */
    public function update(int $id, AdminVariantUpdateRequest $request): JsonResponse
    {
        $variant = $this->variantService->updateVariant($id, $request->validated());
        return ApiResponse::success($variant);
    }
}
