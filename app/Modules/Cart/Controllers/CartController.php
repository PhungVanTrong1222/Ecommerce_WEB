<?php

namespace App\Modules\Cart\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Requests\AddCartItemRequest;
use App\Modules\Cart\Requests\UpdateCartItemRequest;
use App\Modules\Cart\Services\CartService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * GET /api/cart
     * Lấy thông tin chi tiết giỏ hàng hiện tại (Customer hoặc Guest).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request);

        return ApiResponse::success($cart);
    }

    /**
     * POST /api/cart/items
     * Thêm sản phẩm vào giỏ hàng.
     *
     * @param AddCartItemRequest $request
     * @return JsonResponse
     */
    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $cart = $this->cartService->addItem(
            $request,
            (int) $validated['variant_id'],
            (int) $validated['quantity']
        );

        return ApiResponse::success($cart);
    }

    /**
     * PATCH /api/cart/items/{itemId}
     * Cập nhật số lượng của một mục trong giỏ hàng.
     *
     * @param UpdateCartItemRequest $request
     * @param int $itemId
     * @return JsonResponse
     */
    public function updateItem(UpdateCartItemRequest $request, int $itemId): JsonResponse
    {
        $validated = $request->validated();
        $cart = $this->cartService->updateItemQuantity(
            $request,
            $itemId,
            (int) $validated['quantity']
        );

        return ApiResponse::success($cart);
    }

    /**
     * DELETE /api/cart/items/{itemId}
     * Xóa một mục khỏi giỏ hàng.
     *
     * @param Request $request
     * @param int $itemId
     * @return JsonResponse
     */
    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        $cart = $this->cartService->removeItem($request, $itemId);

        return ApiResponse::success($cart);
    }

    /**
     * DELETE /api/cart
     * Xóa toàn bộ sản phẩm trong giỏ hàng.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->clearCart($request);

        return ApiResponse::success($cart);
    }
}
