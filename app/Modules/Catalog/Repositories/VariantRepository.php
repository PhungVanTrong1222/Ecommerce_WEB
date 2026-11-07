<?php

namespace App\Modules\Catalog\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VariantRepository
{
    /**
     * Tìm variant theo id
     */
    public function findById(int $id): ?object
    {
        return DB::table('product_variants')
            ->where('id', $id)
            ->first();
    }

    /**
     * Tìm variant kèm số lượng tồn kho theo id
     */
    public function findWithInventoryById(int $id): ?object
    {
        return DB::table('product_variants')
            ->leftJoin('inventory', 'product_variants.id', '=', 'inventory.variant_id')
            ->where('product_variants.id', $id)
            ->select([
                'product_variants.id',
                'product_variants.product_id',
                'product_variants.sku',
                'product_variants.variant_name',
                'product_variants.price',
                'product_variants.sale_price',
                'product_variants.status',
                'product_variants.created_at',
                'product_variants.updated_at',
                DB::raw('COALESCE(inventory.quantity, 0) as quantity'),
            ])
            ->first();
    }

    /**
     * Tìm variant theo SKU
     */
    public function findBySku(string $sku): ?object
    {
        return DB::table('product_variants')
            ->where('sku', $sku)
            ->first();
    }

    /**
     * Kiểm tra SKU đã tồn tại chưa
     */
    public function existsBySku(string $sku, ?int $excludeId = null): bool
    {
        $query = DB::table('product_variants')->where('sku', $sku);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    /**
     * Lấy các variant của một product, kèm tồn kho
     */
    public function findByProductId(int $productId, bool $activeOnly = false): Collection
    {
        $query = DB::table('product_variants')
            ->leftJoin('inventory', 'product_variants.id', '=', 'inventory.variant_id')
            ->where('product_variants.product_id', $productId)
            ->select([
                'product_variants.id',
                'product_variants.product_id',
                'product_variants.sku',
                'product_variants.variant_name',
                'product_variants.price',
                'product_variants.sale_price',
                'product_variants.status',
                'product_variants.created_at',
                'product_variants.updated_at',
                DB::raw('COALESCE(inventory.quantity, 0) as quantity'),
            ]);

        if ($activeOnly) {
            $query->where('product_variants.status', 'Active');
        }

        return $query->orderBy('product_variants.id', 'asc')->get();
    }

    /**
     * Lấy variants cho nhiều product_ids kèm inventory
     */
    public function findByProductIds(array $productIds, bool $activeOnly = false): Collection
    {
        if (empty($productIds)) {
            return collect();
        }

        $query = DB::table('product_variants')
            ->leftJoin('inventory', 'product_variants.id', '=', 'inventory.variant_id')
            ->whereIn('product_variants.product_id', $productIds)
            ->select([
                'product_variants.id',
                'product_variants.product_id',
                'product_variants.sku',
                'product_variants.variant_name',
                'product_variants.price',
                'product_variants.sale_price',
                'product_variants.status',
                DB::raw('COALESCE(inventory.quantity, 0) as quantity'),
            ]);

        if ($activeOnly) {
            $query->where('product_variants.status', 'Active');
        }

        return $query->orderBy('product_variants.id', 'asc')->get();
    }

    /**
     * Lấy danh sách ảnh cho danh sách variant_ids
     */
    public function findImagesByVariantIds(array $variantIds): Collection
    {
        if (empty($variantIds)) {
            return collect();
        }

        return DB::table('variant_images')
            ->whereIn('variant_id', $variantIds)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Tạo variant mới
     */
    public function create(array $data): int
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        return (int) DB::table('product_variants')->insertGetId($data);
    }

    /**
     * Cập nhật thông tin variant (variant_name, price, sale_price, status)
     */
    public function update(int $id, array $data): int
    {
        $data['updated_at'] = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        return DB::table('product_variants')->where('id', $id)->update($data);
    }
}
