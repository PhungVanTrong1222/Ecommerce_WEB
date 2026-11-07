<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Repositories\InventoryRepository;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Modules\Catalog\Repositories\VariantRepository;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class VariantService
{
    public function __construct(
        protected VariantRepository $variantRepository,
        protected ProductRepository $productRepository,
        protected InventoryRepository $inventoryRepository
    ) {}

    /**
     * Thêm biến thể mới cho sản phẩm kèm số lượng kho ban đầu (Admin)
     */
    public function addVariant(int $productId, array $data): array
    {
        $sku = trim($data['sku']);

        try {
            return DB::transaction(function () use ($productId, $data, $sku) {
                $prod = $this->productRepository->findById($productId);
                if (!$prod) {
                    throw new BusinessException('RESOURCE_NOT_FOUND', 'Sản phẩm không tồn tại', 404);
                }

                if ($this->variantRepository->existsBySku($sku)) {
                    throw new BusinessException('DUPLICATE_SKU', "Mã SKU '{$sku}' đã tồn tại trong hệ thống", 409);
                }

                $price = (float) $data['price'];
                $salePrice = isset($data['sale_price']) && $data['sale_price'] !== null && $data['sale_price'] !== ''
                    ? (float) $data['sale_price']
                    : null;

                if ($salePrice !== null && $salePrice >= $price) {
                    throw new BusinessException('INVALID_PRICE', "Giá khuyến mãi ({$salePrice}) phải nhỏ hơn giá gốc ({$price})", 422);
                }

                $variantId = $this->variantRepository->create([
                    'product_id' => $productId,
                    'sku' => $sku,
                    'variant_name' => $data['variant_name'],
                    'price' => $price,
                    'sale_price' => $salePrice,
                    'status' => $data['status'] ?? 'Active',
                ]);

                $initialQty = (int) ($data['initial_quantity'] ?? 0);
                if ($initialQty < 0) {
                    throw new BusinessException('INVALID_QUANTITY', 'Số lượng tồn kho ban đầu không được âm', 422);
                }

                $this->inventoryRepository->create($variantId, $initialQty);

                $created = $this->variantRepository->findWithInventoryById($variantId);

                return $this->formatVariant($created);
            });
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new BusinessException('DUPLICATE_SKU', "Mã SKU '{$sku}' đã tồn tại trong hệ thống", 409);
            }
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin biến thể (Admin)
     * Chú ý: KHÔNG sửa quantity ở method này (theo spec)
     */
    public function updateVariant(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $variant = $this->variantRepository->findById($id);
            if (!$variant) {
                throw new BusinessException('RESOURCE_NOT_FOUND', 'Biến thể không tồn tại', 404);
            }

            $updateData = [];

            if (isset($data['variant_name'])) {
                $updateData['variant_name'] = $data['variant_name'];
            }

            $currentPrice = (float) $variant->price;
            $currentSalePrice = $variant->sale_price !== null ? (float) $variant->sale_price : null;

            $newPrice = isset($data['price']) ? (float) $data['price'] : $currentPrice;

            $newSalePrice = $currentSalePrice;
            if (array_key_exists('sale_price', $data)) {
                $newSalePrice = $data['sale_price'] !== null && $data['sale_price'] !== ''
                    ? (float) $data['sale_price']
                    : null;
            }

            if ($newSalePrice !== null && $newSalePrice >= $newPrice) {
                throw new BusinessException('INVALID_PRICE', "Giá khuyến mãi ({$newSalePrice}) phải nhỏ hơn giá gốc ({$newPrice})", 422);
            }

            if (isset($data['price'])) {
                $updateData['price'] = $newPrice;
            }

            if (array_key_exists('sale_price', $data)) {
                $updateData['sale_price'] = $newSalePrice;
            }

            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            if (!empty($updateData)) {
                $this->variantRepository->update($id, $updateData);
            }

            $updated = $this->variantRepository->findWithInventoryById($id);

            return $this->formatVariant($updated);
        });
    }

    /**
     * Kiểm tra xem QueryException có phải là vi phạm UNIQUE constraint (2627 hoặc 2601) trên SQL Server không
     */
    private function isUniqueViolation(QueryException $e): bool
    {
        $driverCode = $e->errorInfo[1] ?? null;
        if ($driverCode === 2627 || $driverCode === 2601) {
            return true;
        }

        $message = $e->getMessage();
        return str_contains($message, '2627') || str_contains($message, '2601');
    }

    /**
     * Format variant data
     */
    private function formatVariant(object $v): array
    {
        $price = (float) $v->price;
        $salePrice = $v->sale_price !== null ? (float) $v->sale_price : null;
        $effectivePrice = $salePrice !== null ? $salePrice : $price;

        return [
            'id' => (string) $v->id,
            'product_id' => (string) $v->product_id,
            'sku' => $v->sku,
            'variant_name' => $v->variant_name,
            'price' => $price,
            'sale_price' => $salePrice,
            'effective_price' => $effectivePrice,
            'quantity' => (int) $v->quantity,
            'status' => $v->status,
            'created_at' => $v->created_at,
            'updated_at' => $v->updated_at,
        ];
    }
}
