<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Cart\Services\CartMergeService;
use App\Modules\Identity\Repositories\PermissionRepository;
use App\Modules\Identity\Repositories\UserRepository;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected PermissionRepository $permissionRepository,
        protected CartMergeService $cartMergeService
    ) {}

    /**
     * Đăng ký người dùng mới.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        $email = strtolower(trim($data['email']));

        // Kiểm tra trùng email
        if ($this->userRepository->findByEmail($email)) {
            throw new BusinessException('EMAIL_TAKEN', 'Email này đã được sử dụng.', 409);
        }

        // Role luôn luôn là 'customer' (tra theo code từ DB, tuyệt đối không lấy từ request)
        $customerRole = $this->permissionRepository->getRoleByCode('customer');
        if (! $customerRole) {
            throw new BusinessException('ROLE_NOT_FOUND', 'Không tìm thấy vai trò khách hàng trên hệ thống.', 500);
        }

        $passwordHash = Hash::make($data['password']);

        $userId = $this->userRepository->create([
            'role_id' => (int) $customerRole->id,
            'fullname' => trim($data['fullname']),
            'email' => $email,
            'password_hash' => $passwordHash,
            'phone' => $data['phone'] ?? null,
            'address' => null,
            'avatar_path' => null,
            'status' => 'Active',
        ]);

        // Đăng nhập luôn vào session sau khi đăng ký
        $userModel = User::find($userId);
        if ($userModel) {
            Auth::guard('web')->login($userModel);

            $request = request();
            if ($request && $request->hasSession()) {
                $request->session()->regenerate();
            }

            if ($request) {
                $this->cartMergeService->mergeGuestCartIntoUser($request, $userId);
            }
        }

        return $this->me($userId);
    }

    /**
     * Đăng nhập người dùng bằng email và password.
     *
     * @param string $email
     * @param string $password
     * @param Request $request
     * @return array
     */
    public function login(string $email, string $password, Request $request): array
    {
        $email = strtolower(trim($email));

        $user = $this->userRepository->findByEmail($email);

        // Thông báo lỗi chung, không tiết lộ sự tồn tại của email
        if (! $user || ! Hash::check($password, $user->password_hash)) {
            throw new BusinessException('INVALID_CREDENTIALS', 'Email hoặc mật khẩu không chính xác.', 401);
        }

        if ($user->status !== 'Active') {
            throw new BusinessException('ACCOUNT_SUSPENDED', 'Tài khoản của bạn đã bị khóa.', 403);
        }

        $userModel = User::find($user->id);
        Auth::guard('web')->login($userModel);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        // Merge guest cart khi đăng nhập
        $this->cartMergeService->mergeGuestCartIntoUser($request, (int) $user->id);

        return $this->me((int) $user->id);
    }

    /**
     * Đăng xuất người dùng khỏi session.
     *
     * @param Request $request
     * @return void
     */
    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    /**
     * Thay đổi mật khẩu của người dùng.
     *
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return void
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): void
    {
        $user = $this->userRepository->findById($userId);

        if (! $user || ! Hash::check($currentPassword, $user->password_hash)) {
            throw new BusinessException('INVALID_CURRENT_PASSWORD', 'Mật khẩu hiện tại không đúng.', 422);
        }

        $newHash = Hash::make($newPassword);
        $this->userRepository->updatePassword($userId, $newHash);

        // Giữ phiên đăng nhập cho user hiện tại sau khi đổi mật khẩu
        $userModel = User::find($userId);
        if ($userModel && Auth::guard('web')->check() && (int) Auth::guard('web')->id() === $userId) {
            Auth::guard('web')->login($userModel);
        }
    }

    /**
     * Lấy thông tin chi tiết người dùng và quyền hạn (User DTO).
     *
     * @param int $userId
     * @return array
     */
    public function me(int $userId): array
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            throw new BusinessException('RESOURCE_NOT_FOUND', 'Người dùng không tồn tại.', 404);
        }

        $role = $this->permissionRepository->getRoleById((int) $user->role_id);
        $permissions = $this->permissionRepository->getPermissionCodesByRoleId((int) $user->role_id);

        return [
            'id' => (string) $user->id,
            'fullname' => $user->fullname,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'avatar_path' => $user->avatar_path,
            'role' => $role ? $role->code : null,
            'permissions' => array_values($permissions),
        ];
    }
}
