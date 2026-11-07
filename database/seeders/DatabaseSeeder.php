<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        // 1. Seed roles
        $roles = [
            ['code' => 'customer', 'name' => 'Khách hàng', 'created_at' => $now],
            ['code' => 'admin', 'name' => 'Quản trị viên', 'created_at' => $now],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['code' => $role['code']],
                ['name' => $role['name'], 'created_at' => $role['created_at']]
            );
        }

        $adminRoleId = DB::table('roles')->where('code', 'admin')->value('id');
        $customerRoleId = DB::table('roles')->where('code', 'customer')->value('id');

        // 2. Seed permissions (8 records)
        $permissions = [
            ['code' => 'admin.dashboard', 'name' => 'Xem Dashboard quản trị'],
            ['code' => 'admin.products', 'name' => 'Quản lý sản phẩm'],
            ['code' => 'admin.categories', 'name' => 'Quản lý danh mục'],
            ['code' => 'admin.inventory', 'name' => 'Quản lý kho hàng'],
            ['code' => 'admin.orders', 'name' => 'Quản lý đơn hàng'],
            ['code' => 'admin.users', 'name' => 'Quản lý người dùng'],
            ['code' => 'admin.payments', 'name' => 'Quản lý thanh toán'],
            ['code' => 'admin.banners', 'name' => 'Quản lý banner'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $permission['code']],
                ['name' => $permission['name'], 'created_at' => $now]
            );
        }

        // 3. Seed role_permissions (admin có tất cả 8 permissions, customer không có permission nào)
        $allPermissionIds = DB::table('permissions')->pluck('id');
        foreach ($allPermissionIds as $permissionId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $adminRoleId, 'permission_id' => $permissionId],
                []
            );
        }

        // 4. Seed users (2 test accounts)
        $users = [
            [
                'fullname' => 'Admin TechShop',
                'email' => 'admin@techshop.vn',
                'password_hash' => Hash::make('admin123'),
                'role_id' => $adminRoleId,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'fullname' => 'Nguyễn Văn A',
                'email' => 'customer@example.com',
                'password_hash' => Hash::make('customer123'),
                'role_id' => $customerRoleId,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                $user
            );
        }

        // 5. Seed categories (6 records)
        $categories = [
            ['name' => 'Điện thoại', 'slug' => 'dien-thoai'],
            ['name' => 'Laptop', 'slug' => 'laptop'],
            ['name' => 'Máy tính bảng', 'slug' => 'may-tinh-bang'],
            ['name' => 'Phụ kiện', 'slug' => 'phu-kien'],
            ['name' => 'Đồng hồ thông minh', 'slug' => 'dong-ho-thong-minh'],
            ['name' => 'Âm thanh', 'slug' => 'am-thanh'],
        ];

        foreach ($categories as $cat) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'status' => 'Active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->call(CatalogSeeder::class);
    }
}
