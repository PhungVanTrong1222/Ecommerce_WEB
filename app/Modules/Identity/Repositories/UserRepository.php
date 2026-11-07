<?php

namespace App\Modules\Identity\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    /**
     * Escape ký tự LIKE pattern cho SQL Server.
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(
            ['[', '%', '_'],
            ['[[]', '[%]', '[_]'],
            $value
        );
    }

    /**
     * Tìm người dùng theo ID bằng Query Builder.
     *
     * @param int $id
     * @return object|null
     */
    public function findById(int $id): ?object
    {
        return DB::table('users')->where('id', $id)->first();
    }

    /**
     * Tìm người dùng kèm thông tin Role theo ID.
     *
     * @param int $id
     * @return object|null
     */
    public function findWithRole(int $id): ?object
    {
        return DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.id', $id)
            ->select([
                'users.*',
                'roles.code as role',
                'roles.name as role_name',
            ])
            ->first();
    }

    /**
     * Tìm người dùng theo email bằng Query Builder.
     *
     * @param string $email
     * @return object|null
     */
    public function findByEmail(string $email): ?object
    {
        return DB::table('users')->where('email', $email)->first();
    }

    /**
     * Tạo mới người dùng bằng Query Builder.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        return (int) DB::table('users')->insertGetId($data);
    }

    /**
     * Cập nhật mật khẩu băm cho người dùng.
     *
     * @param int $id
     * @param string $hash
     * @return bool
     */
    public function updatePassword(int $id, string $hash): bool
    {
        DB::table('users')->where('id', $id)->update([
            'password_hash' => $hash,
            'updated_at' => Carbon::now('UTC')->format('Y-m-d H:i:s.u'),
        ]);

        return true;
    }

    /**
     * Cập nhật thông tin profile người dùng (fullname, phone, address).
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateProfile(int $id, array $data): bool
    {
        $allowed = array_intersect_key($data, array_flip(['fullname', 'phone', 'address']));
        $allowed['updated_at'] = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        DB::table('users')->where('id', $id)->update($allowed);

        return true;
    }

    /**
     * Cập nhật trạng thái người dùng (Active / Suspended).
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        DB::table('users')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => Carbon::now('UTC')->format('Y-m-d H:i:s.u'),
        ]);

        return true;
    }

    /**
     * Lấy danh sách người dùng phân trang phục vụ Admin.
     *
     * @param array $filters
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getPaginatedUsers(array $filters, int $page, int $pageSize): array
    {
        $query = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->select([
                'users.id',
                'users.role_id',
                'roles.code as role',
                'roles.name as role_name',
                'users.fullname',
                'users.email',
                'users.phone',
                'users.address',
                'users.avatar_path',
                'users.status',
                'users.created_at',
                'users.updated_at',
            ]);

        if (!empty($filters['status'])) {
            $query->where('users.status', $filters['status']);
        }

        if (!empty($filters['role'])) {
            $query->where('roles.code', $filters['role']);
        }

        if (!empty($filters['q'])) {
            $escaped = $this->escapeLike(trim($filters['q']));
            $query->where(function ($sub) use ($escaped) {
                $sub->where('users.fullname', 'like', "%{$escaped}%")
                    ->orWhere('users.email', 'like', "%{$escaped}%")
                    ->orWhere('users.phone', 'like', "%{$escaped}%");
            });
        }

        $total = $query->count();
        $offset = ($page - 1) * $pageSize;
        $items = $query->orderBy('users.id', 'desc')
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        return [
            'total' => $total,
            'items' => $items,
        ];
    }
}
