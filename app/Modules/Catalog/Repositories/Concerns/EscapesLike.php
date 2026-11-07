<?php

namespace App\Modules\Catalog\Repositories\Concerns;

trait EscapesLike
{
    /**
     * Escape các ký tự đặc biệt trong LIKE pattern cho SQL Server bằng bracket syntax:
     * Thay '[' trước thành '[[]', sau đó '%' thành '[%]', '_' thành '[_]'.
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(
            ['[', '%', '_'],
            ['[[]', '[%]', '[_]'],
            $value
        );
    }
}
