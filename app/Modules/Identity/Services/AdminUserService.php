<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Repositories\UserRepository;
use App\Shared\Exceptions\BusinessException;

class AdminUserService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    /**
     * Lấy danh sách người dùng phân trang cho Admin.
     *
     * @param array $filters
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getPaginatedUsers(array $filters, int $page = 1, int $pageSize = 15): array
    {
        $result = $this->userRepository->getPaginatedUsers($filters, $page, $pageSize);

        $items = collect($result['items'])->map(function ($user) {
            return $this->formatUserDto($user);
        })->all();

        return [
            'total' => (int) $result['total'],
            'items' => $items,
        ];
    }

    /**
     * Cập nhật trạng thái người dùng (Active / Suspended).
     *
     * @param int $currentAdminId
     * @param int $targetUserId
     * @param string $status
     * @return array
     */
    public function updateStatus(int $currentAdminId, int $targetUserId, string $status): array
    {
        if ($currentAdminId === $targetUserId && $status === 'Suspended') {
            throw new BusinessException('CANNOT_SUSPEND_SELF', 'Không thể tự khóa tài khoản của chính mình', 422);
        }

        $user = $this->userRepository->findWithRole($targetUserId);
        if (! $user) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Người dùng không tồn tại.', 404);
        }

        $this->userRepository->updateStatus($targetUserId, $status);

        $updatedUser = $this->userRepository->findWithRole($targetUserId);

        return $this->formatUserDto($updatedUser);
    }

    /**
     * Định dạng User DTO cho Admin.
     *
     * @param object $user
     * @return array
     */
    protected function formatUserDto(object $user): array
    {
        return [
            'id' => (string) $user->id,
            'fullname' => $user->fullname,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'avatar_path' => $user->avatar_path,
            'status' => $user->status,
            'role' => $user->role ?? null,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
