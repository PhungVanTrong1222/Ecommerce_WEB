<?php

namespace App\Shared\Exceptions;

use RuntimeException;

class BusinessException extends RuntimeException
{
    protected string $errorCode;
    protected int $httpStatus;
    protected array $details;

    /**
     * Khởi tạo BusinessException với mã lỗi nghiệp vụ và HTTP status tương ứng.
     *
     * @param string $errorCode
     * @param string $message
     * @param int $httpStatus
     * @param array $details
     */
    public function __construct(
        string $errorCode,
        string $message,
        int $httpStatus = 422,
        array $details = []
    ) {
        parent::__construct($message, $httpStatus);
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
        $this->details = $details;
    }

    /**
     * Lấy mã lỗi nghiệp vụ.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Lấy mã HTTP status.
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * Lấy thông tin chi tiết của lỗi.
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
