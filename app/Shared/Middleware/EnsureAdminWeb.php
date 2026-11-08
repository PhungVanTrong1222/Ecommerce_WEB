<?php

namespace App\Shared\Middleware;

use App\Modules\Identity\Repositories\PermissionRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminWeb
{
    public function __construct(
        protected PermissionRepository $permissionRepository
    ) {}

    /**
     * Kiểm tra quyền truy cập khu vực Quản trị (Admin) trên giao diện web.
     * Chặn truy cập trực tiếp qua URL nếu chưa đăng nhập hoặc không có quyền Admin.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->status !== 'Active') {
            return redirect('/login?redirect=' . urlencode($request->getRequestUri()));
        }

        $role = $this->permissionRepository->getRoleById((int) $user->role_id);

        if (! $role || ! in_array($role->code, ['admin', 'super_admin'], true)) {
            return redirect('/');
        }

        return $next($request);
    }
}
