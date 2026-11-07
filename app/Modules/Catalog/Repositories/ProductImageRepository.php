<?php

namespace App\Modules\Catalog\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductImageRepository
{
    /**
     * Tìm ảnh theo id
     */
    public function findById(int $id): ?object
    {
        return DB::table('product_images')
            ->where('id', $id)
            ->first();
    }

    /**
     * Lấy tất cả ảnh của một sản phẩm theo product_id
     */
    public function findByProductId(int $productId): Collection
    {
        return DB::table('product_images')
            ->where('product_id', $productId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Lấy primary image cho danh sách product_ids
     */
    public function findPrimaryImagesByProductIds(array $productIds): Collection
    {
        if (empty($productIds)) {
            return collect();
        }

        return DB::table('product_images')
            ->whereIn('product_id', $productIds)
            ->where('is_primary', 1)
            ->get();
    }

    /**
     * Lấy toàn bộ ảnh cho danh sách product_ids
     */
    public function findImagesByProductIds(array $productIds): Collection
    {
        if (empty($productIds)) {
            return collect();
        }

        return DB::table('product_images')
            ->whereIn('product_id', $productIds)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Tạo bản ghi ảnh sản phẩm
     */
    public function create(array $data): int
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        $data['created_at'] = $now;

        return (int) DB::table('product_images')->insertGetId($data);
    }

    /**
     * Bỏ đánh dấu primary cho tất cả ảnh của sản phẩm
     */
    public function unsetPrimary(int $productId): int
    {
        return DB::table('product_images')
            ->where('product_id', $productId)
            ->where('is_primary', 1)
            ->update(['is_primary' => 0]);
    }

    /**
     * Xóa ảnh theo id
     */
    public function delete(int $id): int
    {
        return DB::table('product_images')
            ->where('id', $id)
            ->delete();
    }
}
