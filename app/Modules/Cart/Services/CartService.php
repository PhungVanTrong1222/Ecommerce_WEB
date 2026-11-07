<?php

namespace App\Modules\Cart\Services;

use App\Modules\Cart\Repositories\CartRepository;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        protected CartRepository $cartRepository
    ) {}

    /**
     * Xác định Cart Active của request hiện tại (Customer hoặc Guest).
     *
     * @param Request $request
     * @return object|null
     */
    public function resolveActiveCart(Request $request): ?object
    {
        if ($user = $request->user()) {
            return $this->cartRepository->findActiveByUserId((int) $user->id);
        }

        $rawToken = $request->cookie('guest_cart_token');
        if ($rawToken) {
            return $this->cartRepository->findActiveByGuestHash(hash('sha256', $rawToken));
        }

        return null;
    }

    /**
     * Lấy dữ liệu giỏ hàng chi tiết.
     *
     * @param Request $request
     * @param object|null $resolvedCart
     * @return array
     */
    public function getCart(Request $request, ?object $resolvedCart = null): array
    {
        $cart = $resolvedCart ?? $this->resolveActiveCart($request);

        if (! $cart) {
            return [
                'id' => null,
                'items' => [],
                'total_quantity' => 0,
                'subtotal' => 0.0,
                'has_unavailable_items' => false,
            ];
        }

        $items = $this->cartRepository->getItemsWithDetails((int) $cart->id);

        $formattedItems = [];
        $totalQuantity = 0;
        $totalSubtotal = 0.0;
        $hasUnavailableItems = false;

        foreach ($items as $item) {
            $price = (float) $item->price;
            $salePrice = $item->sale_price !== null ? (float) $item->sale_price : null;
            $effectivePrice = ($salePrice !== null && $salePrice < $price) ? $salePrice : $price;

            $stockQuantity = (int) $item->stock_quantity;
            $quantity = (int) $item->quantity;

            // Xác định tính khả dụng và lý do
            if ($item->product_status !== 'Active' || $item->category_status !== 'Active' || $item->variant_status !== 'Active') {
                $isAvailable = false;
                $reason = 'INACTIVE';
            } elseif ($stockQuantity <= 0) {
                $isAvailable = false;
                $reason = 'OUT_OF_STOCK';
            } elseif ($quantity > $stockQuantity) {
                $isAvailable = false;
                $reason = 'INSUFFICIENT_STOCK';
            } else {
                $isAvailable = true;
                $reason = null;
            }

            $itemSubtotal = $isAvailable ? (float) ($effectivePrice * $quantity) : 0.0;

            $imageUrl = null;
            if ($item->image_path) {
                if (filter_var($item->image_path, FILTER_VALIDATE_URL) || str_starts_with($item->image_path, 'http://') || str_starts_with($item->image_path, 'https://')) {
                    $imageUrl = $item->image_path;
                } else {
                    $trimmed = ltrim($item->image_path, '/');
                    $imageUrl = str_starts_with($trimmed, 'assets/') ? '/' . $trimmed : '/storage/' . $trimmed;
                }
            }

            $formattedItems[] = [
                'id' => (string) $item->cart_item_id,
                'variant_id' => (string) $item->variant_id,
                'product_id' => (string) $item->product_id,
                'product_name' => $item->product_name,
                'product_slug' => $item->product_slug,
                'variant_name' => $item->variant_name,
                'sku' => $item->sku,
                'price' => $price,
                'sale_price' => $salePrice,
                'effective_price' => $effectivePrice,
                'quantity' => $quantity,
                'available_stock' => $stockQuantity,
                'subtotal' => $itemSubtotal,
                'image_url' => $imageUrl,
                'is_available' => $isAvailable,
                'unavailable_reason' => $reason,
            ];

            $totalQuantity += $quantity;
            $totalSubtotal += $itemSubtotal;
            if (! $isAvailable) {
                $hasUnavailableItems = true;
            }
        }

        return [
            'id' => (string) $cart->id,
            'items' => $formattedItems,
            'total_quantity' => $totalQuantity,
            'subtotal' => (float) $totalSubtotal,
            'has_unavailable_items' => $hasUnavailableItems,
        ];
    }

    /**
     * Thêm sản phẩm vào giỏ hàng.
     *
     * @param Request $request
     * @param int $variantId
     * @param int $quantity
     * @return array
     */
    public function addItem(Request $request, int $variantId, int $quantity): array
    {
        // 1. Kiểm tra variant tồn tại và trạng thái Active
        $variant = DB::table('product_variants')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('inventory', 'product_variants.id', '=', 'inventory.variant_id')
            ->where('product_variants.id', $variantId)
            ->select([
                'product_variants.id',
                'product_variants.status as variant_status',
                'products.status as product_status',
                'categories.status as category_status',
                DB::raw('COALESCE(inventory.quantity, 0) as stock_quantity'),
            ])
            ->first();

        if (! $variant) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Biến thể sản phẩm không tồn tại.', 404);
        }

        if ($variant->variant_status !== 'Active' || $variant->product_status !== 'Active' || $variant->category_status !== 'Active') {
            throw new BusinessException('VARIANT_INACTIVE', 'Sản phẩm hiện không hoạt động.', 422);
        }

        $targetCart = null;

        DB::transaction(function () use ($request, $variantId, $quantity, $variant, &$targetCart) {
            // 2. Tìm hoặc tạo Cart Active
            $user = $request->user();
            $cart = null;

            if ($user) {
                $cart = $this->cartRepository->findActiveByUserId((int) $user->id);
                if (! $cart) {
                    $cartId = $this->cartRepository->createCart([
                        'user_id' => (int) $user->id,
                        'guest_token_hash' => null,
                        'status' => 'Active',
                    ]);
                    $cart = (object) ['id' => $cartId, 'user_id' => (int) $user->id];
                }
            } else {
                $rawToken = $request->cookie('guest_cart_token');
                if ($rawToken) {
                    $cart = $this->cartRepository->findActiveByGuestHash(hash('sha256', $rawToken));
                }

                if (! $cart) {
                    $rawToken = bin2hex(random_bytes(32));
                    $guestHash = hash('sha256', $rawToken);
                    $cartId = $this->cartRepository->createCart([
                        'user_id' => null,
                        'guest_token_hash' => $guestHash,
                        'status' => 'Active',
                    ]);
                    $cart = (object) ['id' => $cartId];
                    Cookie::queue(cookie('guest_cart_token', $rawToken, 60 * 24 * 30, '/', null, false, true, false, 'Lax'));
                    // Cập nhật ngay vào cookie collection của request hiện tại để các hàm đọc cookie kế tiếp nhận được
                    $request->cookies->set('guest_cart_token', $rawToken);
                }
            }

            $targetCart = $cart;

            // 3. Kiểm tra tồn kho
            $existingItem = $this->cartRepository->findItem((int) $cart->id, $variantId);
            $existingQty = $existingItem ? (int) $existingItem->quantity : 0;
            $newQty = $existingQty + $quantity;
            $stock = (int) $variant->stock_quantity;

            if ($stock <= 0 || $newQty > $stock) {
                throw new BusinessException('INSUFFICIENT_STOCK', 'Số lượng sản phẩm trong kho không đủ.', 409);
            }

            // 4. Thêm hoặc cập nhật số lượng item
            if ($existingItem) {
                $this->cartRepository->updateItemQuantity((int) $existingItem->id, $newQty);
            } else {
                $this->cartRepository->addItem((int) $cart->id, $variantId, $quantity);
            }

            $this->cartRepository->touchCart((int) $cart->id);
        });

        return $this->getCart($request, $targetCart);
    }

    /**
     * Cập nhật số lượng của một mục trong giỏ hàng.
     *
     * @param Request $request
     * @param int $itemId
     * @param int $quantity
     * @return array
     */
    public function updateItemQuantity(Request $request, int $itemId, int $quantity): array
    {
        $cart = $this->resolveActiveCart($request);
        if (! $cart) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Mục giỏ hàng không tồn tại.', 404);
        }

        $item = $this->cartRepository->findItemWithDetails($itemId);
        if (! $item || (int) $item->cart_id !== (int) $cart->id) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Mục giỏ hàng không tồn tại.', 404);
        }

        if ($item->variant_status !== 'Active' || $item->product_status !== 'Active' || $item->category_status !== 'Active') {
            throw new BusinessException('VARIANT_INACTIVE', 'Sản phẩm hiện không hoạt động.', 422);
        }

        $stock = (int) $item->stock_quantity;
        if ($quantity > $stock) {
            throw new BusinessException('INSUFFICIENT_STOCK', 'Số lượng sản phẩm trong kho không đủ.', 409);
        }

        $this->cartRepository->updateItemQuantity($itemId, $quantity);
        $this->cartRepository->touchCart((int) $cart->id);

        return $this->getCart($request, $cart);
    }

    /**
     * Xóa một mục khỏi giỏ hàng.
     *
     * @param Request $request
     * @param int $itemId
     * @return array
     */
    public function removeItem(Request $request, int $itemId): array
    {
        $cart = $this->resolveActiveCart($request);
        if (! $cart) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Mục giỏ hàng không tồn tại.', 404);
        }

        $item = $this->cartRepository->findItemById($itemId);
        if (! $item || (int) $item->cart_id !== (int) $cart->id) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Mục giỏ hàng không tồn tại.', 404);
        }

        $this->cartRepository->removeItem($itemId);
        $this->cartRepository->touchCart((int) $cart->id);

        return $this->getCart($request, $cart);
    }

    /**
     * Xóa toàn bộ sản phẩm trong giỏ hàng.
     *
     * @param Request $request
     * @return array
     */
    public function clearCart(Request $request): array
    {
        $cart = $this->resolveActiveCart($request);
        if ($cart) {
            $this->cartRepository->clearCartItems((int) $cart->id);
            $this->cartRepository->touchCart((int) $cart->id);
        }

        return $this->getCart($request, $cart);
    }
}
