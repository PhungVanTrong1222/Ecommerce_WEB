<?php

namespace App\Modules\Identity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Requests\UpdateProfileRequest;
use App\Modules\Identity\Services\ProfileService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Cập nhật thông tin profile của người dùng đang đăng nhập.
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $userDto = $this->profileService->updateProfile(
            (int) $request->user()->id,
            $request->validated()
        );

        return ApiResponse::success($userDto, 200);
    }
}
