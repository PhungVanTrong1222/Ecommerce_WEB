<?php

namespace App\Modules\Cart\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CartRepository
{
    /**
     * Tìm Cart Active theo user_id.
     *
     * @param int $userId
     * @return object|null
     */
    public function findActiveByUserId(int $userId): ?object
    {
        return DB::table('carts')
            ->where('user_id', $userId)
            ->where('status', 'Active')
            ->first();
    }

    /**
     * Tìm Cart Active theo hash của guest token.
     *
     * @param string $guestTokenHash
     * @return object|null
     */
    public function findActiveByGuestHash(string $guestTokenHash): ?object
    {
        return DB::table('carts')
            ->where('guest_token_hash', $guestTokenHash)
            ->where('status', 'Active')
            ->first();
    }

    /**
     * Tìm Cart theo id.
     *
     * @param int $cartId
     * @return object|null
     */
    public function findCartById(int $cartId): ?object
    {
        return DB::table('carts')
            ->where('id', $cartId)
            ->first();
    }

    /**
     * Lấy danh sách item kèm đầy đủ thông tin variant, product, category, inventory và image.
     *
     * @param int $cartId
     * @return Collection
     */
    public function getItemsWithDetails(int $cartId): Collection
    {
        return DB::table('cart_items')
            ->join('product_variants', 'cart_items.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('inventory', 'product_variants.id', '=', 'inventory.variant_id')
            ->leftJoin('variant_images', function ($join) {
                $join->on('product_variants.id', '=', 'variant_images.variant_id')
                    ->where('variant_images.is_primary', '=', 1);
            })
            ->leftJoin('product_images', function ($join) {
                $join->on('products.id', '=', 'product_images.product_id')
                    ->where('product_images.is_primary', '=', 1);
            })
            ->where('cart_items.cart_id', $cartId)
            ->orderBy('cart_items.id', 'asc')
            ->select([
                'cart_items.id as cart_item_id',
                'cart_items.cart_id',
                'cart_items.variant_id',
                'cart_items.quantity',
                'product_variants.sku',
                'product_variants.variant_name',
                'product_variants.price',
                'product_variants.sale_price',
                'product_variants.status as variant_status',
                'products.id as product_id',
                'products.name as product_name',
                'products.slug as product_slug',
                'products.status as product_status',
                'categories.status as category_status',
                DB::raw('COALESCE(inventory.quantity, 0) as stock_quantity'),
                DB::raw('COALESCE(variant_images.image_path, product_images.image_path) as image_path'),
            ])
            ->get();
    }

    /**
     * Xóa toàn bộ item của một Cart.
     *
     * @param int $cartId
     * @return void
     */
    public function clearCartItems(int $cartId): void
    {
        DB::table('cart_items')->where('cart_id', $cartId)->delete();
    }

    /**
     * Tạo mới một Cart.
     *
     * @param array $data
     * @return int
     */
    public function createCart(array $data): int
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        return (int) DB::table('carts')->insertGetId($data);
    }

    /**
     * Cập nhật thông tin Cart.
     *
     * @param int $cartId
     * @param array $data
     * @return bool
     */
    public function updateCart(int $cartId, array $data): bool
    {
        $data['updated_at'] = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        DB::table('carts')->where('id', $cartId)->update($data);

        return true;
    }

    /**
     * Cập nhật thời gian updated_at của Cart.
     *
     * @param int $cartId
     * @return bool
     */
    public function touchCart(int $cartId): bool
    {
        DB::table('carts')->where('id', $cartId)->update([
            'updated_at' => Carbon::now('UTC')->format('Y-m-d H:i:s.u'),
        ]);

        return true;
    }

    /**
     * Tìm 1 item trong Cart theo variant_id.
     *
     * @param int $cartId
     * @param int $variantId
     * @return object|null
     */
    public function findItem(int $cartId, int $variantId): ?object
    {
        return DB::table('cart_items')
            ->where('cart_id', $cartId)
            ->where('variant_id', $variantId)
            ->first();
    }

    /**
     * Tìm 1 item theo ID của cart_item.
     *
     * @param int $itemId
     * @return object|null
     */
    public function findItemById(int $itemId): ?object
    {
        return DB::table('cart_items')
            ->where('id', $itemId)
            ->first();
    }

    /**
     * Tìm 1 item kèm thông tin variant, product, category, inventory.
     *
     * @param int $itemId
     * @return object|null
     */
    public function findItemWithDetails(int $itemId): ?object
    {
        return DB::table('cart_items')
            ->join('product_variants', 'cart_items.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('inventory', 'product_variants.id', '=', 'inventory.variant_id')
            ->where('cart_items.id', $itemId)
            ->select([
                'cart_items.id as cart_item_id',
                'cart_items.cart_id',
                'cart_items.variant_id',
                'cart_items.quantity',
                'product_variants.status as variant_status',
                'products.status as product_status',
                'categories.status as category_status',
                DB::raw('COALESCE(inventory.quantity, 0) as stock_quantity'),
            ])
            ->first();
    }

    /**
     * Lấy toàn bộ items của một cart.
     *
     * @param int $cartId
     * @return Collection
     */
    public function getCartItems(int $cartId): Collection
    {
        return DB::table('cart_items')
            ->where('cart_id', $cartId)
            ->get();
    }

    /**
     * Thêm mới một item vào cart_items.
     *
     * @param int $cartId
     * @param int $variantId
     * @param int $quantity
     * @return int
     */
    public function addItem(int $cartId, int $variantId, int $quantity): int
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        return (int) DB::table('cart_items')->insertGetId([
            'cart_id' => $cartId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Cập nhật số lượng item trong cart_items.
     *
     * @param int $itemId
     * @param int $quantity
     * @return bool
     */
    public function updateItemQuantity(int $itemId, int $quantity): bool
    {
        DB::table('cart_items')->where('id', $itemId)->update([
            'quantity' => $quantity,
            'updated_at' => Carbon::now('UTC')->format('Y-m-d H:i:s.u'),
        ]);

        return true;
    }

    /**
     * Xóa 1 item khỏi cart_items.
     *
     * @param int $itemId
     * @return bool
     */
    public function removeItem(int $itemId): bool
    {
        DB::table('cart_items')->where('id', $itemId)->delete();

        return true;
    }
}
