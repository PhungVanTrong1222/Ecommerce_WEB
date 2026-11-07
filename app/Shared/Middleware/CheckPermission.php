<?php

namespace App\Shared\Middleware;

use App\Modules\Identity\Repositories\PermissionRepository;
use App\Shared\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        protected PermissionRepository $permissionRepository
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('UNAUTHENTICATED', 'Unauthenticated', 401);
        }

        $permissionCodes = $this->permissionRepository->getPermissionCodesByRoleId((int) $user->role_id);

        if (! in_array($permission, $permissionCodes, true)) {
            return ApiResponse::error('PERMISSION_DENIED', 'Permission denied', 403);
        }

        return $next($request);
    }
}
