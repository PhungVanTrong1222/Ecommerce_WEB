<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Bảng liên kết trong cơ sở dữ liệu.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Danh sách các thuộc tính có thể gán hàng loạt.
     * Lưu ý: role_id không được phép gán trực tiếp từ request của người dùng.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fullname',
        'email',
        'password_hash',
        'phone',
        'address',
        'avatar_path',
        'status',
    ];

    /**
     * Các thuộc tính cần ẩn khi serialize (JSON/Array).
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Lấy mật khẩu phục vụ cho Auth Guard của Laravel.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Không sử dụng remember_token theo yêu cầu thiết kế.
     */
    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * Định dạng các trường dữ liệu.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
