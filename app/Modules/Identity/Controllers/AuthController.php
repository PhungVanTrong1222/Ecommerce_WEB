<?php

namespace App\Modules\Identity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Requests\ChangePasswordRequest;
use App\Modules\Identity\Requests\LoginRequest;
use App\Modules\Identity\Requests\RegisterRequest;
use App\Modules\Identity\Services\AuthService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Đăng ký người dùng mới.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $userDto = $this->authService->register($request->validated());

        return ApiResponse::success($userDto, 201);
    }

    /**
     * Đăng nhập người dùng vào hệ thống.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userDto = $this->authService->login($validated['email'], $validated['password'], $request);

        return ApiResponse::success($userDto, 200);
    }

    /**
     * Đăng xuất người dùng.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return ApiResponse::success(['message' => 'Đăng xuất thành công.'], 200);
    }

    /**
     * Lấy thông tin người dùng hiện tại (kèm roles, permissions).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $userDto = $this->authService->me((int) $request->user()->id);

        return ApiResponse::success($userDto, 200);
    }

    /**
     * Đổi mật khẩu người dùng.
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->authService->changePassword(
            (int) $request->user()->id,
            $validated['current_password'],
            $validated['new_password']
        );

        return ApiResponse::success(['message' => 'Đổi mật khẩu thành công.'], 200);
    }
}
