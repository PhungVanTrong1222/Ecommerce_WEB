<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Modules\Catalog\Repositories\InventoryRepository;
use App\Modules\Catalog\Repositories\ProductImageRepository;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Modules\Catalog\Repositories\VariantRepository;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected CategoryRepository $categoryRepository,
        protected VariantRepository $variantRepository,
        protected InventoryRepository $inventoryRepository,
        protected ProductImageRepository $productImageRepository
    ) {}

    /**
     * Lấy danh sách sản phẩm hiển thị công khai (Public API)
     */
    public function getPublicProducts(array $filters): array
    {
        $page = max((int) ($filters['page'] ?? 1), 1);
        $pageSize = min(max((int) ($filters['page_size'] ?? 12), 1), 48);

        $total = $this->productRepository->countPublicProducts($filters);
        $products = $this->productRepository->getPublicProducts($filters, $page, $pageSize);

        if ($products->isEmpty()) {
            return [
                'items' => [],
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
            ];
        }

        $productIds = $products->pluck('id')->all();

        // Lấy tất cả active variants của danh sách sản phẩm (tránh N+1)
        $variants = $this->variantRepository->findByProductIds($productIds, true)->groupBy('product_id');

        // Lấy tất cả primary images của danh sách sản phẩm (tránh N+1)
        $primaryImages = $this->productImageRepository->findPrimaryImagesByProductIds($productIds)->keyBy('product_id');

        $items = $products->map(function ($prod) use ($variants, $primaryImages) {
            $prodVariants = $variants->get($prod->id, collect());

            $minPrice = null;
            $minOriginalPrice = null;
            $hasSale = false;
            $totalStock = 0;

            foreach ($prodVariants as $v) {
                $price = (float) $v->price;
                $salePrice = $v->sale_price !== null ? (float) $v->sale_price : null;
                $effectivePrice = $salePrice !== null ? $salePrice : $price;

                if ($minPrice === null || $effectivePrice < $minPrice) {
                    $minPrice = $effectivePrice;
                    $minOriginalPrice = $price;
                }

                if ($salePrice !== null && $salePrice < $price) {
                    $hasSale = true;
                }

                $totalStock += (int) $v->quantity;
            }

            $primaryImg = $primaryImages->get($prod->id);
            $imageUrl = $primaryImg ? $this->formatImageUrl($primaryImg->image_path) : null;

            return [
                'id' => (string) $prod->id,
                'name' => $prod->name,
                'slug' => $prod->slug,
                'brand' => $prod->brand,
                'category' => [
                    'id' => (string) $prod->category_id,
                    'name' => $prod->category_name,
                    'slug' => $prod->category_slug,
                ],
                'primary_image' => $imageUrl,
                'min_price' => $minPrice !== null ? (float) $minPrice : 0.0,
                'min_original_price' => $minOriginalPrice !== null ? (float) $minOriginalPrice : 0.0,
                'has_sale' => $hasSale,
                'in_stock' => $totalStock > 0,
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
     * Lấy chi tiết sản phẩm công khai theo slug (Public API)
     */
    public function getPublicProductDetail(string $slug): array
    {
        $prod = $this->productRepository->findBySlug($slug);

        if (!$prod || $prod->status !== 'Active' || $prod->category_status !== 'Active') {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh', 404);
        }

        // Chỉ lấy các variants Active
        $variants = $this->variantRepository->findByProductId($prod->id, true);

        if ($variants->isEmpty()) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh', 404);
        }

        $images = $this->productImageRepository->findByProductId($prod->id);
        $variantIds = $variants->pluck('id')->all();
        $variantImages = $this->variantRepository->findImagesByVariantIds($variantIds)->groupBy('variant_id');

        $specifications = null;
        if (!empty($prod->specifications)) {
            $decoded = json_decode($prod->specifications);
            $specifications = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        $formattedImages = $images->map(function ($img) {
            $url = $this->formatImageUrl($img->image_path);

            return [
                'id' => (string) $img->id,
                'image_path' => $img->image_path,
                'url' => $url,
                'is_primary' => (bool) $img->is_primary,
                'sort_order' => (int) $img->sort_order,
            ];
        })->values()->all();

        $formattedVariants = $variants->map(function ($v) use ($variantImages) {
            $price = (float) $v->price;
            $salePrice = $v->sale_price !== null ? (float) $v->sale_price : null;
            $effectivePrice = $salePrice !== null ? $salePrice : $price;
            $qty = (int) $v->quantity;

            $vImgs = $variantImages->get($v->id, collect())->map(function ($vi) {
                $url = $this->formatImageUrl($vi->image_path);

                return [
                    'id' => (string) $vi->id,
                    'image_path' => $vi->image_path,
                    'url' => $url,
                    'is_primary' => (bool) $vi->is_primary,
                    'sort_order' => (int) $vi->sort_order,
                ];
            })->values()->all();

            return [
                'id' => (string) $v->id,
                'sku' => $v->sku,
                'variant_name' => $v->variant_name,
                'price' => $price,
                'sale_price' => $salePrice,
                'effective_price' => $effectivePrice,
                'quantity' => $qty,
                'in_stock' => $qty > 0,
                'images' => $vImgs,
            ];
        })->values()->all();

        return [
            'id' => (string) $prod->id,
            'name' => $prod->name,
            'slug' => $prod->slug,
            'brand' => $prod->brand,
            'description' => $prod->description,
            'specifications' => $specifications,
            'category' => [
                'id' => (string) $prod->category_id,
                'name' => $prod->category_name,
                'slug' => $prod->category_slug,
            ],
            'images' => $formattedImages,
            'variants' => $formattedVariants,
        ];
    }

    /**
     * Lấy danh sách sản phẩm cho Admin (kể cả Inactive, có phân trang)
     */
    public function getAdminProducts(array $filters): array
    {
        $page = max((int) ($filters['page'] ?? 1), 1);
        $pageSize = min(max((int) ($filters['page_size'] ?? 12), 1), 100);

        $total = $this->productRepository->countAdminProducts($filters);
        $products = $this->productRepository->getAdminProducts($filters, $page, $pageSize);

        if ($products->isEmpty()) {
            return [
                'items' => [],
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
            ];
        }

        $productIds = $products->pluck('id')->all();
        $primaryImages = $this->productImageRepository->findPrimaryImagesByProductIds($productIds)->keyBy('product_id');
        $variants = $this->variantRepository->findByProductIds($productIds, false)->groupBy('product_id');

        $items = $products->map(function ($prod) use ($primaryImages, $variants) {
            $primaryImg = $primaryImages->get($prod->id);
            $imageUrl = $primaryImg ? $this->formatImageUrl($primaryImg->image_path) : null;

            $prodVariants = $variants->get($prod->id, collect());
            $totalStock = $prodVariants->sum('quantity');

            return [
                'id' => (string) $prod->id,
                'name' => $prod->name,
                'slug' => $prod->slug,
                'brand' => $prod->brand,
                'status' => $prod->status,
                'category' => [
                    'id' => (string) $prod->category_id,
                    'name' => $prod->category_name,
                    'slug' => $prod->category_slug,
                ],
                'primary_image' => $imageUrl,
                'variant_count' => $prodVariants->count(),
                'total_stock' => (int) $totalStock,
                'created_at' => $prod->created_at,
                'updated_at' => $prod->updated_at,
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
     * Lấy chi tiết sản phẩm cho Admin (kể cả Inactive, đầy đủ variants, inventory, images)
     */
    public function getAdminProductDetail(int $id): array
    {
        $prod = $this->productRepository->findById($id);
        if (!$prod) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Sản phẩm không tồn tại', 404);
        }

        $variants = $this->variantRepository->findByProductId($prod->id, false);
        $images = $this->productImageRepository->findByProductId($prod->id);

        $variantIds = $variants->pluck('id')->all();
        $variantImages = $this->variantRepository->findImagesByVariantIds($variantIds)->groupBy('variant_id');

        $specifications = null;
        if (!empty($prod->specifications)) {
            $decoded = json_decode($prod->specifications);
            $specifications = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        $formattedImages = $images->map(function ($img) {
            $url = $this->formatImageUrl($img->image_path);

            return [
                'id' => (string) $img->id,
                'image_path' => $img->image_path,
                'url' => $url,
                'is_primary' => (bool) $img->is_primary,
                'sort_order' => (int) $img->sort_order,
                'created_at' => $img->created_at,
            ];
        })->values()->all();

        $formattedVariants = $variants->map(function ($v) use ($variantImages) {
            $price = (float) $v->price;
            $salePrice = $v->sale_price !== null ? (float) $v->sale_price : null;
            $effectivePrice = $salePrice !== null ? $salePrice : $price;

            $vImgs = $variantImages->get($v->id, collect())->map(function ($vi) {
                $url = $this->formatImageUrl($vi->image_path);

                return [
                    'id' => (string) $vi->id,
                    'image_path' => $vi->image_path,
                    'url' => $url,
                    'is_primary' => (bool) $vi->is_primary,
                    'sort_order' => (int) $vi->sort_order,
                ];
            })->values()->all();

            return [
                'id' => (string) $v->id,
                'sku' => $v->sku,
                'variant_name' => $v->variant_name,
                'price' => $price,
                'sale_price' => $salePrice,
                'effective_price' => $effectivePrice,
                'quantity' => (int) $v->quantity,
                'status' => $v->status,
                'images' => $vImgs,
                'created_at' => $v->created_at,
                'updated_at' => $v->updated_at,
            ];
        })->values()->all();

        return [
            'id' => (string) $prod->id,
            'name' => $prod->name,
            'slug' => $prod->slug,
            'brand' => $prod->brand,
            'description' => $prod->description,
            'specifications' => $specifications,
            'status' => $prod->status,
            'category' => [
                'id' => (string) $prod->category_id,
                'name' => $prod->category_name,
                'slug' => $prod->category_slug,
                'status' => $prod->category_status,
            ],
            'images' => $formattedImages,
            'variants' => $formattedVariants,
            'created_at' => $prod->created_at,
            'updated_at' => $prod->updated_at,
        ];
    }

    /**
     * Tạo sản phẩm mới kèm các variants và inventory trong 1 transaction (Admin)
     */
    public function createProduct(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                // Kiểm tra danh mục
                $category = $this->categoryRepository->findById((int) $data['category_id']);
                if (!$category) {
                    throw new BusinessException('RESOURCE_NOT_FOUND', 'Danh mục sản phẩm không tồn tại', 404);
                }

                // Sinh slug duy nhất
                $slug = $this->generateUniqueSlug($data['name']);

                // Validate và chuẩn hóa specifications JSON
                $specificationsJson = $this->normalizeSpecifications($data['specifications'] ?? null);

                // Tạo product
                $productId = $this->productRepository->create([
                    'category_id' => (int) $data['category_id'],
                    'name' => $data['name'],
                    'slug' => $slug,
                    'brand' => $data['brand'] ?? null,
                    'description' => $data['description'] ?? null,
                    'specifications' => $specificationsJson,
                    'status' => $data['status'] ?? 'Active',
                ]);

                // Validate và tạo các variants + inventory
                $variants = $data['variants'] ?? [];
                if (empty($variants)) {
                    throw new BusinessException('INVALID_DATA', 'Sản phẩm phải có ít nhất 1 biến thể', 422);
                }

                $seenSkus = [];
                foreach ($variants as $v) {
                    $sku = trim($v['sku']);
                    if (in_array($sku, $seenSkus, true)) {
                        throw new BusinessException('DUPLICATE_SKU', "Mã SKU '{$sku}' bị trùng lặp trong danh sách gửi lên", 409);
                    }
                    $seenSkus[] = $sku;

                    if ($this->variantRepository->existsBySku($sku)) {
                        throw new BusinessException('DUPLICATE_SKU', "Mã SKU '{$sku}' đã tồn tại trong hệ thống", 409);
                    }

                    $price = (float) $v['price'];
                    $salePrice = isset($v['sale_price']) && $v['sale_price'] !== null && $v['sale_price'] !== ''
                        ? (float) $v['sale_price']
                        : null;

                    if ($salePrice !== null && $salePrice >= $price) {
                        throw new BusinessException('INVALID_PRICE', "Giá khuyến mãi ({$salePrice}) phải nhỏ hơn giá gốc ({$price}) cho SKU '{$sku}'", 422);
                    }

                    $variantId = $this->variantRepository->create([
                        'product_id' => $productId,
                        'sku' => $sku,
                        'variant_name' => $v['variant_name'],
                        'price' => $price,
                        'sale_price' => $salePrice,
                        'status' => $v['status'] ?? 'Active',
                    ]);

                    // Tạo inventory ban đầu trong cùng transaction
                    $initialQty = (int) ($v['initial_quantity'] ?? 0);
                    if ($initialQty < 0) {
                        throw new BusinessException('INVALID_QUANTITY', 'Số lượng tồn kho ban đầu không được âm', 422);
                    }

                    $this->inventoryRepository->create($variantId, $initialQty);
                }

                return $this->getAdminProductDetail($productId);
            });
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                $msg = strtolower($e->getMessage());
                if (str_contains($msg, 'sku') || str_contains($msg, 'product_variants')) {
                    throw new BusinessException('DUPLICATE_SKU', 'Mã SKU đã tồn tại trong hệ thống', 409);
                }
                throw new BusinessException('DUPLICATE_SLUG', 'Slug sản phẩm đã tồn tại trong hệ thống', 409);
            }
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin sản phẩm (Admin)
     */
    public function updateProduct(int $id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $prod = $this->productRepository->findById($id);
                if (!$prod) {
                    throw new BusinessException('RESOURCE_NOT_FOUND', 'Sản phẩm không tồn tại', 404);
                }

                $updateData = [];

                if (isset($data['category_id'])) {
                    $category = $this->categoryRepository->findById((int) $data['category_id']);
                    if (!$category) {
                        throw new BusinessException('RESOURCE_NOT_FOUND', 'Danh mục sản phẩm không tồn tại', 404);
                    }
                    $updateData['category_id'] = (int) $data['category_id'];
                }

                if (isset($data['name'])) {
                    $updateData['name'] = $data['name'];
                    if ($data['name'] !== $prod->name) {
                        $updateData['slug'] = $this->generateUniqueSlug($data['name'], $id);
                    }
                }

                if (array_key_exists('brand', $data)) {
                    $updateData['brand'] = $data['brand'];
                }

                if (array_key_exists('description', $data)) {
                    $updateData['description'] = $data['description'];
                }

                if (array_key_exists('specifications', $data)) {
                    $updateData['specifications'] = $this->normalizeSpecifications($data['specifications']);
                }

                if (isset($data['status'])) {
                    $updateData['status'] = $data['status'];
                }

                if (!empty($updateData)) {
                    $this->productRepository->update($id, $updateData);
                }

                return $this->getAdminProductDetail($id);
            });
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new BusinessException('DUPLICATE_SLUG', 'Slug sản phẩm đã tồn tại trong hệ thống', 409);
            }
            throw $e;
        }
    }

    /**
     * Upload ảnh cho sản phẩm (Admin)
     * Store file trước → bọc DB trong try/catch → nếu DB lỗi thì xóa file vật lý đã lưu để tránh mồ côi
     */
    public function uploadImage(int $productId, UploadedFile $file, bool $isPrimary): array
    {
        $prod = $this->productRepository->findById($productId);
        if (!$prod) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Sản phẩm không tồn tại', 404);
        }

        // Lưu file vào storage/app/public/products
        $path = $file->store('products', 'public');

        try {
            $imageId = DB::transaction(function () use ($productId, $path, $isPrimary) {
                // Nếu đặt là primary, unset ảnh primary cũ trước trong cùng transaction
                if ($isPrimary) {
                    $this->productImageRepository->unsetPrimary($productId);
                }

                return $this->productImageRepository->create([
                    'product_id' => $productId,
                    'image_path' => $path,
                    'sort_order' => 0,
                    'is_primary' => $isPrimary ? 1 : 0,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        $url = $this->formatImageUrl($path);

        return [
            'id' => (string) $imageId,
            'product_id' => (string) $productId,
            'image_path' => $path,
            'url' => $url,
            'is_primary' => $isPrimary,
            'sort_order' => 0,
        ];
    }

    /**
     * Xóa ảnh sản phẩm (Admin)
     * Xóa row trong transaction trước, sau khi commit thành công mới xóa file vật lý
     */
    public function deleteImage(int $imageId): void
    {
        $imagePath = DB::transaction(function () use ($imageId) {
            $img = $this->productImageRepository->findById($imageId);
            if (!$img) {
                throw new BusinessException('RESOURCE_NOT_FOUND', 'Hình ảnh không tồn tại', 404);
            }

            $this->productImageRepository->delete($imageId);

            return $img->image_path;
        });

        // Chỉ xóa file vật lý sau khi transaction commit thành công
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    /**
     * Sinh slug không trùng lặp cho sản phẩm
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'san-pham';
        }

        $slug = $baseSlug;
        $counter = 2;

        while ($this->productRepository->existsBySlug($slug, $excludeId)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Chuẩn hóa và validate specifications JSON
     */
    private function normalizeSpecifications(mixed $specifications): ?string
    {
        if ($specifications === null || $specifications === '') {
            return null;
        }

        if (is_array($specifications) || is_object($specifications)) {
            return json_encode($specifications, JSON_UNESCAPED_UNICODE);
        }

        if (is_string($specifications)) {
            $decoded = json_decode($specifications, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                throw new BusinessException('INVALID_SPECIFICATIONS', 'Thông số kỹ thuật specifications phải là một JSON object hợp lệ', 422);
            }
            return json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        throw new BusinessException('INVALID_SPECIFICATIONS', 'Thông số kỹ thuật specifications phải là một JSON object hợp lệ', 422);
    }

    /**
     * Chuyển đổi image_path thành URL tương đối (/storage/... hoặc /assets/...) hoặc URL tuyệt đối
     */
    protected function formatImageUrl(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        if (filter_var($imagePath, FILTER_VALIDATE_URL) || str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        $trimmed = ltrim($imagePath, '/');
        if (str_starts_with($trimmed, 'assets/')) {
            return '/' . $trimmed;
        }

        return '/storage/' . $trimmed;
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
}
