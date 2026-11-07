<?php

namespace App\Shared\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Trả về JSON response thành công theo định dạng chuẩn.
     *
     * @param mixed $data
     * @param int $status
     * @param array $meta
     * @return JsonResponse
     */
    public static function success(mixed $data = null, int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => (object) $meta,
        ], $status);
    }

    /**
     * Trả về JSON response phân trang theo định dạng chuẩn.
     *
     * @param array $items
     * @param int $page
     * @param int $pageSize
     * @param int $total
     * @return JsonResponse
     */
    public static function paginated(array $items, int $page, int $pageSize, int $total): JsonResponse
    {
        $meta = [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => $pageSize > 0 ? (int) ceil($total / $pageSize) : 0,
        ];

        return self::success($items, 200, $meta);
    }

    /**
     * Trả về JSON response lỗi theo định dạng chuẩn.
     *
     * @param string $code
     * @param string $message
     * @param int $status
     * @param array $details
     * @return JsonResponse
     */
    public static function error(string $code, string $message, int $status, array $details = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }
}
