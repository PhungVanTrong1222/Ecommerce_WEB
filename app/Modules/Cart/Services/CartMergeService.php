<?php

namespace App\Modules\Cart\Services;

use App\Modules\Cart\Repositories\CartRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class CartMergeService
{
    public function __construct(
        protected CartRepository $cartRepository
    ) {}

    /**
     * Hợp nhất Guest Cart vào Customer Cart khi đăng nhập hoặc đăng ký.
     *
     * @param Request $request
     * @param int $userId
     * @return void
     */
    public function mergeGuestCartIntoUser(Request $request, int $userId): void
    {
        $rawToken = $request->cookie('guest_cart_token');
        if (empty($rawToken)) {
            return;
        }

        $guestHash = hash('sha256', $rawToken);
        $guestCart = $this->cartRepository->findActiveByGuestHash($guestHash);

        if (! $guestCart) {
            Cookie::queue(Cookie::forget('guest_cart_token'));
            return;
        }

        DB::transaction(function () use ($guestCart, $userId) {
            $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
            $customerCart = $this->cartRepository->findActiveByUserId($userId);

            if (! $customerCart) {
                // Trường hợp 1: Customer chưa có Active Cart
                // Chuyển quyền sở hữu Guest Cart sang cho User
                $this->cartRepository->updateCart((int) $guestCart->id, [
                    'user_id' => $userId,
                    'guest_token_hash' => null,
                ]);

                // Điều chỉnh số lượng tồn kho cho các item nếu cần
                $items = $this->cartRepository->getItemsWithDetails((int) $guestCart->id);
                foreach ($items as $item) {
                    $isActive = ($item->variant_status === 'Active' && $item->product_status === 'Active' && $item->category_status === 'Active');
                    $stock = (int) $item->stock_quantity;
                    $itemQty = (int) $item->quantity;

                    if ($isActive && $stock > 0) {
                        if ($itemQty > $stock) {
                            $this->cartRepository->updateItemQuantity((int) $item->cart_item_id, $stock);
                        }
                    }
                    // Nếu variant Inactive hoặc stock == 0: GIỮ NGUYÊN item
                }
            } else {
                // Trường hợp 2: Customer đã có Active Cart
                $guestItems = $this->cartRepository->getItemsWithDetails((int) $guestCart->id);
                $customerItems = $this->cartRepository->getCartItems((int) $customerCart->id)->keyBy('variant_id');

                foreach ($guestItems as $guestItem) {
                    $isActive = ($guestItem->variant_status === 'Active' && $guestItem->product_status === 'Active' && $guestItem->category_status === 'Active');
                    $stock = (int) $guestItem->stock_quantity;
                    $guestQty = (int) $guestItem->quantity;

                    if ($customerItems->has($guestItem->variant_id)) {
                        $customerItem = $customerItems->get($guestItem->variant_id);
                        $customerQty = (int) $customerItem->quantity;
                        $newQty = $customerQty + $guestQty;

                        if ($isActive && $stock > 0) {
                            $newQty = min($newQty, max($stock, $customerQty));
                        }

                        $this->cartRepository->updateItemQuantity((int) $customerItem->id, $newQty);
                    } else {
                        if ($isActive && $stock > 0) {
                            $qty = min($guestQty, $stock);
                        } else {
                            $qty = $guestQty;
                        }

                        $this->cartRepository->addItem((int) $customerCart->id, (int) $guestItem->variant_id, $qty);
                    }
                }

                // Xóa cart_items của Guest Cart và đánh dấu Merged
                $this->cartRepository->clearCartItems((int) $guestCart->id);
                $this->cartRepository->updateCart((int) $guestCart->id, [
                    'status' => 'Merged',
                ]);

                $this->cartRepository->touchCart((int) $customerCart->id);
            }
        });

        // Xóa cookie guest_cart_token
        Cookie::queue(Cookie::forget('guest_cart_token'));
    }
}
