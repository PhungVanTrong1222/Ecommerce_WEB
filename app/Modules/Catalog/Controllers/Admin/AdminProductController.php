<?php

namespace App\Modules\Catalog\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Requests\AdminImageUploadRequest;
use App\Modules\Catalog\Requests\AdminProductRequest;
use App\Modules\Catalog\Requests\AdminProductUpdateRequest;
use App\Modules\Catalog\Services\ProductService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * GET /api/admin/products
     * Danh sách sản phẩm cho admin (kể cả Inactive, phân trang)
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->productService->getAdminProducts($request->all());

        return ApiResponse::paginated(
            $result['items'],
            $result['page'],
            $result['pageSize'],
            $result['total']
        );
    }

    /**
     * GET /api/admin/products/{id}
     * Chi tiết sản phẩm cho admin
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getAdminProductDetail($id);
        return ApiResponse::success($product);
    }

    /**
     * POST /api/admin/products
     * Tạo sản phẩm + variants + inventory trong 1 transaction
     */
    public function store(AdminProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());
        return ApiResponse::success($product, 201);
    }

    /**
     * PATCH /api/admin/products/{id}
     * Cập nhật thông tin sản phẩm
     */
    public function update(int $id, AdminProductUpdateRequest $request): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->validated());
        return ApiResponse::success($product);
    }

    /**
     * POST /api/admin/products/{id}/images
     * Upload ảnh cho sản phẩm
     */
    public function uploadImage(int $id, AdminImageUploadRequest $request): JsonResponse
    {
        $isPrimary = filter_var($request->input('is_primary', false), FILTER_VALIDATE_BOOLEAN);
        $image = $this->productService->uploadImage($id, $request->file('image'), $isPrimary);

        return ApiResponse::success($image, 201);
    }

    /**
     * DELETE /api/admin/product-images/{id}
     * Xóa ảnh sản phẩm
     */
    public function deleteImage(int $id): JsonResponse
    {
        $this->productService->deleteImage($id);
        return ApiResponse::success(['message' => 'Đã xóa hình ảnh thành công']);
    }
}
