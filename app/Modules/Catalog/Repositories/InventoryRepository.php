<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Repositories\Concerns\EscapesLike;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryRepository
{
    use EscapesLike;
    /**
     * Tìm bản ghi tồn kho theo variant_id
     */
    public function findByVariantId(int $variantId): ?object
    {
        return DB::table('inventory')
            ->where('variant_id', $variantId)
            ->first();
    }

    /**
     * Tạo bản ghi tồn kho ban đầu cho variant
     */
    public function create(int $variantId, int $quantity): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        DB::table('inventory')->insert([
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'updated_at' => $now,
        ]);
    }

    /**
     * Điều chỉnh số lượng tồn kho theo delta (nguyên tử bằng T-SQL)
     * Trả về số dòng affected (0 hoặc 1)
     */
    public function adjust(int $variantId, int $delta): int
    {
        return DB::affectingStatement(
            "UPDATE inventory 
             SET quantity = quantity + ?, updated_at = SYSUTCDATETIME() 
             WHERE variant_id = ? AND quantity + ? >= 0",
            [$delta, $variantId, $delta]
        );
    }

    /**
     * Giảm tồn kho nguyên tử (dành cho Order module)
     */
    public function decrement(int $variantId, int $qty): bool
    {
        $affected = DB::affectingStatement(
            "UPDATE inventory 
             SET quantity = quantity - ?, updated_at = SYSUTCDATETIME() 
             WHERE variant_id = ? AND quantity >= ?",
            [$qty, $variantId, $qty]
        );

        return $affected === 1;
    }

    /**
     * Tăng tồn kho nguyên tử (dành cho hoàn trả / hủy đơn trong Order module)
     */
    public function increment(int $variantId, int $qty): void
    {
        DB::affectingStatement(
            "UPDATE inventory 
             SET quantity = quantity + ?, updated_at = SYSUTCDATETIME() 
             WHERE variant_id = ?",
            [$qty, $variantId]
        );
    }

    /**
     * Đếm tổng số bản ghi tồn kho cho admin
     */
    public function countInventory(array $filters = []): int
    {
        $query = DB::table('inventory')
            ->join('product_variants', 'inventory.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id');

        if (!empty($filters['low_stock'])) {
            $query->where('inventory.quantity', '<=', 5);
        }

        if (!empty($filters['q'])) {
            $escaped = $this->escapeLike($filters['q']);
            $query->where(function ($q) use ($escaped) {
                $q->where('product_variants.variant_name', 'like', "%{$escaped}%")
                  ->orWhere('product_variants.sku', 'like', "%{$escaped}%")
                  ->orWhere('products.name', 'like', "%{$escaped}%");
            });
        }

        return $query->count();
    }

    /**
     * Lấy danh sách tồn kho phân trang cho admin
     */
    public function getInventoryList(array $filters, int $page, int $pageSize): Collection
    {
        $query = DB::table('inventory')
            ->join('product_variants', 'inventory.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select([
                'product_variants.id as variant_id',
                'product_variants.product_id',
                'products.name as product_name',
                'product_variants.sku',
                'product_variants.variant_name',
                'product_variants.price',
                'product_variants.sale_price',
                'product_variants.status as variant_status',
                'inventory.quantity',
                'inventory.updated_at',
            ]);

        if (!empty($filters['low_stock'])) {
            $query->where('inventory.quantity', '<=', 5);
        }

        if (!empty($filters['q'])) {
            $escaped = $this->escapeLike($filters['q']);
            $query->where(function ($q) use ($escaped) {
                $q->where('product_variants.variant_name', 'like', "%{$escaped}%")
                  ->orWhere('product_variants.sku', 'like', "%{$escaped}%")
                  ->orWhere('products.name', 'like', "%{$escaped}%");
            });
        }

        $offset = ($page - 1) * $pageSize;

        return $query->orderBy('product_variants.id', 'asc')
            ->offset($offset)
            ->limit($pageSize)
            ->get();
    }
}
