<?php

namespace App\Modules\Payment\Services;

use Illuminate\Support\Carbon;

class VnPayService
{
    /**
     * Tạo URL thanh toán VNPay chuẩn version 2.1.0 với chữ ký HMAC-SHA512
     */
    public function buildPaymentUrl(
        string $orderNumber,
        string $transactionReference,
        float $amount,
        string $ipAddress,
        ?string $orderInfo = null,
        string $locale = 'vn'
    ): string {
        $tmnCode = (string) config('vnpay.tmn_code');
        $hashSecret = (string) config('vnpay.hash_secret');
        $vnpUrl = (string) config('vnpay.url');
        $returnUrl = (string) config('vnpay.return_url');

        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $createDate = $now->format('YmdHis');
        $expireDate = $now->copy()->addMinutes(15)->format('YmdHis');

        $vnpAmount = (int) round($amount * 100);

        if (empty($orderInfo)) {
            $orderInfo = "Thanh toan don hang {$orderNumber}";
        }

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => (string) $vnpAmount,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => $createDate,
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $ipAddress ?: '127.0.0.1',
            'vnp_Locale' => $locale,
            'vnp_OrderInfo' => $orderInfo,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_TxnRef' => $transactionReference,
            'vnp_ExpireDate' => $expireDate,
        ];

        ksort($inputData);

        $query = '';
        $i = 0;
        $hashData = '';
        foreach ($inputData as $key => $value) {
            if ($value !== null && $value !== '') {
                if ($i === 1) {
                    $hashData .= '&' . urlencode((string) $key) . '=' . urlencode((string) $value);
                } else {
                    $hashData .= urlencode((string) $key) . '=' . urlencode((string) $value);
                    $i = 1;
                }
                $query .= urlencode((string) $key) . '=' . urlencode((string) $value) . '&';
            }
        }

        $vnpSecureHash = hash_hmac('sha512', $hashData, $hashSecret);
        $vnpUrl = $vnpUrl . '?' . $query . 'vnp_SecureHash=' . $vnpSecureHash;

        return $vnpUrl;
    }

    /**
     * Xác thực chữ ký HMAC-SHA512 từ VNPay phản hồi (Return URL hoặc IPN)
     */
    public function verifySignature(array $params): bool
    {
        $vnpSecureHash = $params['vnp_SecureHash'] ?? '';
        if (empty($vnpSecureHash)) {
            return false;
        }

        $hashSecret = (string) config('vnpay.hash_secret');

        unset($params['vnp_SecureHash']);
        unset($params['vnp_SecureHashType']);

        ksort($params);

        $i = 0;
        $hashData = '';
        foreach ($params as $key => $value) {
            if ($value !== null && $value !== '') {
                if ($i === 1) {
                    $hashData .= '&' . urlencode((string) $key) . '=' . urlencode((string) $value);
                } else {
                    $hashData .= urlencode((string) $key) . '=' . urlencode((string) $value);
                    $i = 1;
                }
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $hashSecret);

        return hash_equals(strtolower($secureHash), strtolower($vnpSecureHash));
    }
}
