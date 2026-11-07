<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Repositories\Concerns\EscapesLike;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    use EscapesLike;
    /**
     * Tìm sản phẩm theo id
     */
    public function findById(int $id): ?object
    {
        return DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.id', $id)
            ->select([
                'products.*',
                'categories.name as category_name',
                'categories.slug as category_slug',
                'categories.status as category_status',
            ])
            ->first();
    }

    /**
     * Tìm sản phẩm theo slug
     */
    public function findBySlug(string $slug): ?object
    {
        return DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.slug', $slug)
            ->select([
                'products.*',
                'categories.name as category_name',
                'categories.slug as category_slug',
                'categories.status as category_status',
            ])
            ->first();
    }

    /**
     * Kiểm tra slug đã tồn tại chưa
     */
    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        $query = DB::table('products')->where('slug', $slug);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    /**
     * Tạo sản phẩm mới
     */
    public function create(array $data): int
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        return (int) DB::table('products')->insertGetId($data);
    }

    /**
     * Cập nhật thông tin sản phẩm
     */
    public function update(int $id, array $data): int
    {
        $data['updated_at'] = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        return DB::table('products')->where('id', $id)->update($data);
    }

    /**
     * Đếm tổng số sản phẩm public theo điều kiện lọc
     */
    public function countPublicProducts(array $filters = []): int
    {
        $query = $this->buildPublicQuery($filters);
        return $query->count();
    }

    /**
     * Lấy danh sách sản phẩm public có phân trang
     */
    public function getPublicProducts(array $filters, int $page, int $pageSize): Collection
    {
        $query = $this->buildPublicQuery($filters);

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        $minPriceSubquery = "(SELECT MIN(CASE WHEN v.sale_price IS NOT NULL THEN v.sale_price ELSE v.price END) FROM product_variants v WHERE v.product_id = products.id AND v.status = 'Active')";

        switch ($sort) {
            case 'price_asc':
                $query->orderByRaw("{$minPriceSubquery} ASC")->orderBy('products.id', 'asc');
                break;
            case 'price_desc':
                $query->orderByRaw("{$minPriceSubquery} DESC")->orderBy('products.id', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('products.name', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('products.created_at', 'desc')->orderBy('products.id', 'desc');
                break;
        }

        $offset = ($page - 1) * $pageSize;

        return $query->offset($offset)->limit($pageSize)->get();
    }

    /**
     * Xây dựng query cơ bản cho Public products
     */
    private function buildPublicQuery(array $filters): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.status', 'Active')
            ->where('categories.status', 'Active')
            // Chỉ trả product có ít nhất 1 variant Active
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('product_variants as v')
                    ->whereColumn('v.product_id', 'products.id')
                    ->where('v.status', 'Active');
            })
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.brand',
                'products.created_at',
                'categories.id as category_id',
                'categories.name as category_name',
                'categories.slug as category_slug',
            ]);

        // Filter category slug
        if (!empty($filters['category'])) {
            $query->where('categories.slug', $filters['category']);
        }

        // Filter brand
        if (!empty($filters['brand'])) {
            $query->where('products.brand', $filters['brand']);
        }

        // Filter q (tìm kiếm theo tên sản phẩm)
        if (!empty($filters['q'])) {
            $escaped = $this->escapeLike($filters['q']);
            $query->where('products.name', 'like', "%{$escaped}%");
        }

        $minPriceSub = "(SELECT MIN(CASE WHEN v.sale_price IS NOT NULL THEN v.sale_price ELSE v.price END) FROM product_variants v WHERE v.product_id = products.id AND v.status = 'Active')";

        // Filter min_price
        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $query->whereRaw("{$minPriceSub} >= ?", [(float) $filters['min_price']]);
        }

        // Filter max_price
        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $query->whereRaw("{$minPriceSub} <= ?", [(float) $filters['max_price']]);
        }

        // Filter on_sale = 1
        if (!empty($filters['on_sale'])) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('product_variants as v')
                    ->whereColumn('v.product_id', 'products.id')
                    ->where('v.status', 'Active')
                    ->whereNotNull('v.sale_price')
                    ->whereRaw('v.sale_price < v.price');
            });
        }

        return $query;
    }

    /**
     * Đếm tổng số sản phẩm cho admin
     */
    public function countAdminProducts(array $filters = []): int
    {
        $query = $this->buildAdminQuery($filters);
        return $query->count();
    }

    /**
     * Lấy danh sách sản phẩm cho admin có phân trang
     */
    public function getAdminProducts(array $filters, int $page, int $pageSize): Collection
    {
        $query = $this->buildAdminQuery($filters);

        $offset = ($page - 1) * $pageSize;

        return $query->orderBy('products.created_at', 'desc')
            ->orderBy('products.id', 'desc')
            ->offset($offset)
            ->limit($pageSize)
            ->get();
    }

    /**
     * Xây dựng query cho Admin products
     */
    private function buildAdminQuery(array $filters): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select([
                'products.id',
                'products.category_id',
                'products.name',
                'products.slug',
                'products.brand',
                'products.status',
                'products.created_at',
                'products.updated_at',
                'categories.name as category_name',
                'categories.slug as category_slug',
            ]);

        if (!empty($filters['status'])) {
            $query->where('products.status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('products.category_id', (int) $filters['category_id']);
        } elseif (!empty($filters['category'])) {
            $query->where('categories.slug', $filters['category']);
        }

        if (!empty($filters['brand'])) {
            $query->where('products.brand', $filters['brand']);
        }

        if (!empty($filters['q'])) {
            $escaped = $this->escapeLike($filters['q']);
            $query->where('products.name', 'like', "%{$escaped}%");
        }

        return $query;
    }
}
