<?php

namespace App\Modules\Identity\Repositories;

use Illuminate\Support\Facades\DB;

class PermissionRepository
{
    /**
     * Cache danh sách mã quyền theo roleId trong suốt vòng đời của request.
     *
     * @var array<int, array<string>>
     */
    protected static array $permissionsCache = [];

    /**
     * Lấy danh sách mã quyền của một role_id.
     *
     * @param int $roleId
     * @return array<string>
     */
    public function getPermissionCodesByRoleId(int $roleId): array
    {
        if (isset(self::$permissionsCache[$roleId])) {
            return self::$permissionsCache[$roleId];
        }

        $codes = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', $roleId)
            ->pluck('permissions.code')
            ->all();

        self::$permissionsCache[$roleId] = $codes;

        return $codes;
    }

    /**
     * Lấy thông tin role theo role_id.
     *
     * @param int $roleId
     * @return object|null
     */
    public function getRoleById(int $roleId): ?object
    {
        return DB::table('roles')->where('id', $roleId)->first();
    }

    /**
     * Lấy thông tin role theo role code (vd: 'customer', 'admin').
     *
     * @param string $code
     * @return object|null
     */
    public function getRoleByCode(string $code): ?object
    {
        return DB::table('roles')->where('code', $code)->first();
    }

    /**
     * Xóa cache permissions.
     */
    public static function clearCache(): void
    {
        self::$permissionsCache = [];
    }
}
