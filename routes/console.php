<?php

use App\Modules\Order\Services\OrderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:expire-unpaid', function (OrderService $orderService) {
    $count = $orderService->expireUnpaidOrders();
    $this->info("Đã tự động hủy {$count} đơn hàng VNPay quá hạn thanh toán.");
})->purpose('Tự động hủy các đơn hàng VNPay quá hạn thanh toán sau 15 phút');

Schedule::command('orders:expire-unpaid')->everyMinute();
