<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {}

    /**
     * Lấy danh sách category đang Active (Public)
     */
    public function getActiveCategories(): array
    {
        $categories = $this->categoryRepository->findActive();

        return $categories->map(function ($cat) {
            return $this->formatCategory($cat);
        })->values()->all();
    }

    /**
     * Lấy toàn bộ danh sách category (Admin)
     */
    public function getAllCategories(): array
    {
        $categories = $this->categoryRepository->findAll();

        return $categories->map(function ($cat) {
            return $this->formatCategory($cat);
        })->values()->all();
    }

    /**
     * Lấy chi tiết category theo ID
     */
    public function getCategoryById(int $id): array
    {
        $cat = $this->categoryRepository->findById($id);
        if (!$cat) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Danh mục không tồn tại', 404);
        }

        return $this->formatCategory($cat);
    }

    /**
     * Tạo mới category (Admin)
     */
    public function createCategory(array $data): array
    {
        try {
            $slug = $this->generateUniqueSlug($data['name']);

            $insertData = [
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'Active',
            ];

            $id = $this->categoryRepository->create($insertData);

            return $this->getCategoryById($id);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new BusinessException('DUPLICATE_SLUG', 'Slug danh mục đã tồn tại trong hệ thống', 409);
            }
            throw $e;
        }
    }

    /**
     * Cập nhật category (Admin)
     */
    public function updateCategory(int $id, array $data): array
    {
        try {
            $cat = $this->categoryRepository->findById($id);
            if (!$cat) {
                throw new BusinessException('RESOURCE_NOT_FOUND', 'Danh mục không tồn tại', 404);
            }

            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
                if ($data['name'] !== $cat->name) {
                    $updateData['slug'] = $this->generateUniqueSlug($data['name'], $id);
                }
            }

            if (array_key_exists('description', $data)) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            if (!empty($updateData)) {
                $this->categoryRepository->update($id, $updateData);
            }

            return $this->getCategoryById($id);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new BusinessException('DUPLICATE_SLUG', 'Slug danh mục đã tồn tại trong hệ thống', 409);
            }
            throw $e;
        }
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
     * Sinh slug không trùng lặp cho danh mục
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'danh-muc';
        }

        $slug = $baseSlug;
        $counter = 2;

        while ($this->categoryRepository->existsBySlug($slug, $excludeId)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Định dạng dữ liệu category serialize theo spec
     */
    private function formatCategory(object $cat): array
    {
        return [
            'id' => (string) $cat->id,
            'name' => $cat->name,
            'slug' => $cat->slug,
            'description' => $cat->description,
            'status' => $cat->status,
            'created_at' => $cat->created_at,
            'updated_at' => $cat->updated_at,
        ];
    }
}
