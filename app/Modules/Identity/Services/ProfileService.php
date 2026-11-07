<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Repositories\UserRepository;
use App\Shared\Exceptions\BusinessException;

class ProfileService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected AuthService $authService
    ) {}

    /**
     * Cập nhật thông tin profile của người dùng.
     * Chỉ cho phép cập nhật: fullname, phone, address (không cho sửa email, role_id, status).
     *
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function updateProfile(int $userId, array $data): array
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Người dùng không tồn tại.', 404);
        }

        $updateData = [];
        if (array_key_exists('fullname', $data)) {
            $updateData['fullname'] = trim($data['fullname']);
        }
        if (array_key_exists('phone', $data)) {
            $updateData['phone'] = $data['phone'];
        }
        if (array_key_exists('address', $data)) {
            $updateData['address'] = $data['address'];
        }

        $this->userRepository->updateProfile($userId, $updateData);

        return $this->authService->me($userId);
    }
}
