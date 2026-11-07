<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Requests\FilterProductRequest;
use App\Modules\Catalog\Services\ProductService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * GET /api/products
     * Danh sách sản phẩm (hỗ trợ filter, sort, phân trang)
     */
    public function index(FilterProductRequest $request): JsonResponse
    {
        $result = $this->productService->getPublicProducts($request->validated());

        return ApiResponse::paginated(
            $result['items'],
            $result['page'],
            $result['pageSize'],
            $result['total']
        );
    }

    /**
     * GET /api/products/{slug}
     * Chi tiết sản phẩm theo slug
     */
    public function show(string $slug): JsonResponse
    {
        $product = $this->productService->getPublicProductDetail($slug);
        return ApiResponse::success($product);
    }
}
