<?php

namespace App\Modules\Catalog\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Requests\AdminCategoryRequest;
use App\Modules\Catalog\Requests\AdminCategoryUpdateRequest;
use App\Modules\Catalog\Services\CategoryService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminCategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * GET /api/admin/categories
     * Danh sách category (kể cả Inactive)
     */
    public function index(): JsonResponse
    {
        $categories = $this->categoryService->getAllCategories();
        return ApiResponse::success($categories);
    }

    /**
     * POST /api/admin/categories
     * Tạo mới danh mục
     */
    public function store(AdminCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());
        return ApiResponse::success($category, 201);
    }

    /**
     * PATCH /api/admin/categories/{id}
     * Cập nhật danh mục
     */
    public function update(int $id, AdminCategoryUpdateRequest $request): JsonResponse
    {
        $category = $this->categoryService->updateCategory($id, $request->validated());
        return ApiResponse::success($category);
    }
}
