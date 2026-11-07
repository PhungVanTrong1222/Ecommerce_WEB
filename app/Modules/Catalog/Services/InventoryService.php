<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Repositories\InventoryRepository;
use App\Modules\Catalog\Repositories\VariantRepository;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function __construct(
        protected InventoryRepository $inventoryRepository,
        protected VariantRepository $variantRepository
    ) {}

    /**
     * Lấy danh sách tồn kho cho Admin (hỗ trợ filter low_stock=1 và q)
     */
    public function getInventoryList(array $filters): array
    {
        $page = max((int) ($filters['page'] ?? 1), 1);
        $pageSize = min(max((int) ($filters['page_size'] ?? 15), 1), 100);

        $total = $this->inventoryRepository->countInventory($filters);
        $rows = $this->inventoryRepository->getInventoryList($filters, $page, $pageSize);

        $items = $rows->map(function ($row) {
            $price = (float) $row->price;
            $salePrice = $row->sale_price !== null ? (float) $row->sale_price : null;
            $effectivePrice = $salePrice !== null ? $salePrice : $price;

            return [
                'variant_id' => (string) $row->variant_id,
                'product_id' => (string) $row->product_id,
                'product_name' => $row->product_name,
                'sku' => $row->sku,
                'variant_name' => $row->variant_name,
                'price' => $price,
                'sale_price' => $salePrice,
                'effective_price' => $effectivePrice,
                'quantity' => (int) $row->quantity,
                'status' => $row->variant_status,
                'updated_at' => $row->updated_at,
            ];
        })->values()->all();

        return [
            'items' => $items,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
        ];
    }

    /**
     * Điều chỉnh số lượng tồn kho nguyên tử (Admin)
     */
    public function adjustInventory(int $variantId, int $delta, string $reason, ?int $userId = null): array
    {
        if ($delta === 0) {
            throw new BusinessException('INVALID_DELTA', 'Độ lệch tồn kho phải khác 0', 422);
        }

        // Kiểm tra biến thể có tồn tại không
        $variant = $this->variantRepository->findById($variantId);
        if (!$variant) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Biến thể sản phẩm không tồn tại', 404);
        }

        // Thực hiện cập nhật nguyên tử bằng T-SQL
        $affected = $this->inventoryRepository->adjust($variantId, $delta);

        if ($affected === 0) {
            throw new BusinessException('INSUFFICIENT_STOCK', 'Số lượng tồn kho không đủ để giảm', 409);
        }

        // Ghi log Laravel theo spec
        Log::info("Adjust inventory: variant {$variantId}, delta {$delta}, reason: '{$reason}', user_id: " . ($userId ?? 'guest'));

        $updated = $this->variantRepository->findWithInventoryById($variantId);

        $price = (float) $updated->price;
        $salePrice = $updated->sale_price !== null ? (float) $updated->sale_price : null;
        $effectivePrice = $salePrice !== null ? $salePrice : $price;

        return [
            'variant_id' => (string) $updated->id,
            'product_id' => (string) $updated->product_id,
            'sku' => $updated->sku,
            'variant_name' => $updated->variant_name,
            'price' => $price,
            'sale_price' => $salePrice,
            'effective_price' => $effectivePrice,
            'quantity' => (int) $updated->quantity,
            'status' => $updated->status,
            'updated_at' => $updated->updated_at,
        ];
    }
}
