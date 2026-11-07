<?php

namespace App\Modules\Catalog\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoryRepository
{
    /**
     * Lấy danh sách category đang Active (cho public)
     */
    public function findActive(): Collection
    {
        return DB::table('categories')
            ->select(['id', 'name', 'slug', 'description', 'status', 'created_at', 'updated_at'])
            ->where('status', 'Active')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Lấy toàn bộ danh sách category (cho admin)
     */
    public function findAll(): Collection
    {
        return DB::table('categories')
            ->select(['id', 'name', 'slug', 'description', 'status', 'created_at', 'updated_at'])
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Tìm category theo id
     */
    public function findById(int $id): ?object
    {
        return DB::table('categories')
            ->where('id', $id)
            ->first();
    }

    /**
     * Tìm category theo slug
     */
    public function findBySlug(string $slug): ?object
    {
        return DB::table('categories')
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Kiểm tra slug đã tồn tại chưa (ngoại trừ excludeId nếu có)
     */
    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        $query = DB::table('categories')->where('slug', $slug);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    /**
     * Tạo mới category
     */
    public function create(array $data): int
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        return (int) DB::table('categories')->insertGetId($data);
    }

    /**
     * Cập nhật category theo id
     */
    public function update(int $id, array $data): int
    {
        $data['updated_at'] = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        return DB::table('categories')->where('id', $id)->update($data);
    }
}
