<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\CategoryService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * GET /api/categories
     * Lấy danh sách danh mục đang Active
     */
    public function index(): JsonResponse
    {
        $categories = $this->categoryService->getActiveCategories();
        return ApiResponse::success($categories);
    }
}
