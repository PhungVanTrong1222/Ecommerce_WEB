<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        // Danh mục tương ứng
        $categoryMap = DB::table('categories')->pluck('id', 'slug')->all();

        $products = [
            // 1. Điện thoại
            [
                'category_slug' => 'dien-thoai',
                'name' => 'iPhone 15 Pro Max',
                'slug' => 'iphone-15-pro-max',
                'brand' => 'Apple',
                'description' => 'iPhone 15 Pro Max khung Titan cao cấp, chip A17 Pro mạnh mẽ, camera zoom quang 5x ấn tượng.',
                'specifications' => [
                    'Màn hình' => '6.7 inch Super Retina XDR OLED, 120Hz',
                    'Vi xử lý' => 'Apple A17 Pro (3nm)',
                    'RAM' => '8GB',
                    'Pin' => '4422 mAh, Sạc nhanh 20W',
                    'Camera sau' => 'Chính 48MP + Góc siêu rộng 12MP + Tele 12MP (Zoom 5x)',
                    'Trọng lượng' => '221g',
                ],
                'status' => 'Active',
                'variants' => [
                    [
                        'sku' => 'IP15PM-256-TN',
                        'variant_name' => 'Titan Tự Nhiên - 256GB',
                        'price' => 34990000,
                        'sale_price' => 29990000,
                        'quantity' => 20,
                        'status' => 'Active',
                    ],
                    [
                        'sku' => 'IP15PM-512-XD',
                        'variant_name' => 'Titan Xanh - 512GB',
                        'price' => 40990000,
                        'sale_price' => 36990000,
                        'quantity' => 15,
                        'status' => 'Active',
                    ],
                    [
                        'sku' => 'IP15PM-1TB-DEN',
                        'variant_name' => 'Titan Đen - 1TB',
                        'price' => 46990000,
                        'sale_price' => null,
                        'quantity' => 5,
                        'status' => 'Active',
                    ],
                ],
            ],
            [
                'category_slug' => 'dien-thoai',
                'name' => 'Samsung Galaxy S24 Ultra',
                'slug' => 'samsung-galaxy-s24-ultra',
                'brand' => 'Samsung',
                'description' => 'Galaxy S24 Ultra tích hợp Galaxy AI đột phá, camera 200MP, khung titan và bút S-Pen quyền năng.',
                'specifications' => [
                    'Màn hình' => '6.8 inch Dynamic AMOLED 2X, QHD+, 120Hz',
                    'Vi xử lý' => 'Snapdragon 8 Gen 3 for Galaxy',
                    'RAM' => '12GB',
                    'Pin' => '5000 mAh, Sạc nhanh 45W',
                    'Camera sau' => '200MP + 50MP + 12MP + 10MP',
                    'Tính năng đặc biệt' => 'Galaxy AI, Bút S-Pen tích hợp',
                ],
                'status' => 'Active',
                'variants' => [
                    [
                        'sku' => 'S24U-256-XAM',
                        'variant_name' => 'Xám Titan - 256GB',
                        'price' => 31990000,
                        'sale_price' => 26990000,
                        'quantity' => 25,
                        'status' => 'Active',
                    ],
                    [
                        'sku' => 'S24U-512-DEN',
                        'variant_name' => 'Đen Titan - 512GB',
                        'price' => 37490000,
                        'sale_price' => 33990000,
                        'quantity' => 10,
                        'status' => 'Active',
                    ],
                    [
                        'sku' => 'S24U-1TB-VANG',
                        'variant_name' => 'Vàng Titan - 1TB',
                        'price' => 44490000,
                        'sale_price' => null,
                        'quantity' => 0, // Test hết hàng (out of stock)
                        'status' => 'Active',
                    ],
                ],
            ],
            [
                'category_slug' => 'dien-thoai',
                'name' => 'Xiaomi 14',
                'slug' => 'xiaomi-14',
                'brand' => 'Xiaomi',
                'description' => 'Xiaomi 14 thiết kế nhỏ gọn, ống kính quang học Leica thế hệ mới, hiệu năng đỉnh cao với Snapdragon 8 Gen 3.',
                'specifications' => [
                    'Màn hình' => '6.36 inch OLED 1.5K, 120Hz LTPO',
                    'Vi xử lý' => 'Snapdragon 8 Gen 3',
                    'RAM' => '12GB LPDDR5X',
                    'Pin' => '4610 mAh, Sạc nhanh 90W',
                    'Camera' => 'Hệ thống 3 camera Leica 50MP',
                ],
                'status' => 'Active',
                'variants' => [
                    [
                        'sku' => 'MI14-256-DEN',
                        'variant_name' => 'Đen - 256GB',
                        'price' => 22990000,
                        'sale_price' => 19990000,
                        'quantity' => 30,
                        'status' => 'Active',
                    ],
                    [
                        'sku' => 'MI14-512-XANH',
                        'variant_name' => 'Xanh Jade - 512GB',
                        'price' => 24490000,
                        'sale_price' => null,
                        'quantity' => 15,
                        'status' => 'Active',
                    ],
                ],
            ],

            // 2. Laptop
            [
                'category_slug' => 'laptop',
                'name' => 'MacBook Air M3',
                'slug' => 'macbook-air-m3',
                'brand' => 'Apple',
                'description' => 'MacBook Air 13 inch với chip Apple M3 siêu nhanh, thời lượng pin lên đến 18 giờ, thiết kế siêu mỏng nhẹ.',
                'specifications' => [
                    'Màn hình' => '13.6 inch Liquid Retina (2560 x 1664)',
                    'Vi xử lý' => 'Apple M3 (8-core CPU, 10-core GPU)',
                    'RAM' => '16GB Unified Memory',
                    'Bộ nhớ trong' => '512GB SSD',
                    'Pin' => 'Lên đến 18 giờ',
                    'Trọng lượng' => '1.24 kg',
                ],
                'status' => 'Active',
                'variants' => [
                    [
                        'sku' => 'MBA-M3-16-512-MID',
                        'variant_name' => 'Midnight - 16GB / 512GB',
                        'price' => 32990000,
                        'sale_price' => 30490000,
                        'quantity' => 12,
                        'status' => 'Active',
                    ],
                    [
                        'sku' => 'MBA-M3-16-512-SLV',
                        'variant_name' => 'Silver - 16GB / 512GB',
                        'price' => 32990000,
                        'sale_price' => null,
                        'quantity' => 8,
                        'status' => 'Active',
                    ],
                ],
            ],
            [
                'category_slug' => 'laptop',
                'name' => 'Dell XPS 14 9440',
                'slug' => 'dell-xps-14-9440',
                'brand' => 'Dell',
                'description' => 'Dell XPS 14 9440 sở hữu thiết kế viền vô cực sang trọng, màn hình OLED 3.2K sắc nét cùng vi xử lý Intel Core Ultra.',
                'specifications' => [
                    'Màn hình' => '14.5 inch 3.2K (3200 x 2000) OLED Touch 120Hz',
                    'Vi xử lý' => 'Intel Core Ultra 7 155H (16 cores, up to 4.8 GHz)',
                    'RAM' => '32GB LPDDR5x 7467MHz',
                    'Bộ nhớ trong' => '1TB M.2 PCIe NVMe SSD',
                    'Trọng lượng' => '1.68 kg',
                ],
                'status' => 'Active',
                'variants' => [
                    [
                        'sku' => 'XPS14-U7-32-1TB-GRA',
                        'variant_name' => 'Graphite - Ultra 7 / 32GB / 1TB',
                        'price' => 58990000,
                        'sale_price' => 54990000,
                        'quantity' => 6,
                        'status' => 'Active',
                    ],
                    [
                        'sku' => 'XPS14-U7-16-512-PLT',
                        'variant_name' => 'Platinum - Ultra 7 / 16GB / 512GB',
                        'price' => 49990000,
                        'sale_price' => null,
                        'quantity' => 4,
                        'status' => 'Active',
                    ],
                ],
            ],
            [
                'category_slug' => 'laptop',
                'name' => 'ASUS ROG Zephyrus G16',
                'slug' => 'asus-rog-zephyrus-g16',
                'brand' => 'ASUS',
                'description' => 'Laptop gaming mỏng nhẹ cao cấp nhất của ROG với màn hình ROG Nebula OLED 240Hz và GPU RTX 4070.',
                'specifications' => [
                    'Màn hình' => '16 inch 2.5K OLED 240Hz 0.2ms G-Sync',
                    'Vi xử lý' => 'Intel Core Ultra 9 185H',
                    'Card đồ họa' => 'NVIDIA GeForce RTX 4070 8GB GDDR6',
                    'RAM' => '32GB LPDDR5X',
                    'Bộ nhớ trong' => '1TB PCIe 4.0 NVMe M.2 SSD',
                    'Trọng lượng' => '1.85 kg',
                ],
                'status' => 'Active',
                'variants' => [
                    [
                        'sku' => 'ROG-G16-U9-4070-GRY',
                        'variant_name' => 'Eclipse Gray - Core Ultra 9 / RTX 4070',
                        'price' => 64990000,
                        'sale_price' => 59990000,
                        'quantity' => 7,
                        'status' => 'Active',
                    ],
                ],
            ],

            // 3. Máy tính bảng
            [
                'category_slug' => 'may-tinh-bang',
                'name' => 'iPad Pro M4',
                'slug' => 'ipad-pro-m4',
                'brand' => 'Apple',
                'description' => 'iPad Pro siêu mỏng với màn hình Ultra Retina XDR công nghệ Tandem OLED đột phá và chip Apple M4 thế hệ mới.',
                'specifications' => [
                    'Màn hình' => '11 inch Ultra Retina XDR Tandem OLED, 120Hz ProMotion',
                    'Vi xử lý' => 'Apple M4',
                    'RAM' => '8GB',
                    'Độ mỏng' => '5.3 mm',
                    'Trọng lượng' => '444g',
                ],
                'status' => 'Active',
                'variants' => [
                    [
                        'sku' => 'IPAD-M4-11-256-BLK',
                        'variant_name' => 'Space Black - 256GB WiFi',
                        'price' => 28990000,
                        'sale_price' => 26990000,
                        'quantity' => 18,
                        'status' => 'Active',
                    ],
                    [
                        'sku' => 'IPAD-M4-11-512-SLV',
                        'variant_name' => 'Silver - 512GB WiFi',
                        'price' => 34490000,
                        'sale_price' => null,
                        'quantity' => 10,
                        'status' => 'Active',
                    ],
                ],
            ],
            [
                'category_slug' => 'may-tinh-bang',
                'name' => 'Samsung Galaxy Tab S9 Ultra',
                'slug' => 'galaxy-tab-s9-ultra',
                'brand' => 'Samsung',
                'description' => 'Galaxy Tab S9 Ultra màn hình cực đại 14.6 inch Dynamic AMOLED 2X, chuẩn kháng nước IP68 đầu tiên trên tablet cao cấp.',
                'specifications' => [
                    'Màn hình' => '14.6 inch Dynamic AMOLED 2X 120Hz HDR10+',
                    'Vi xử lý' => 'Snapdragon 8 Gen 2 for Galaxy',
                    'RAM' => '12GB',
                    'Bộ nhớ' => '256GB',
                    'Pin' => '11200 mAh, Sạc nhanh 45W',
                    'Kháng nước' => 'IP68 (kèm bút S-Pen kháng nước)',
                ],
                'status' => 'Active',
                'variants' => [
                    [
                        'sku' => 'TABS9U-256-XAM',
                        'variant_name' => 'Xám Graphite - 256GB 5G',
                        'price' => 32990000,
                        'sale_price' => 27990000,
                        'quantity' => 14,
                        'status' => 'Active',
                    ],
                ],
            ],

            // 4. Âm thanh
            [
                'category_slug' => 'am-thanh',
                'name' => 'AirPods Pro 2',
                'slug' => 'airpods-pro-2',
                'brand' => 'Apple',
                'description' => 'Tai nghe không dây Apple AirPods Pro thế hệ 2 với chip H2, chống ồn chủ động gấp đôi và hộp sạc MagSafe cổng USB-C.',
                'specifications' => [
                    'Vi xử lý' => 'Apple H2 headphone chip, Apple U1 trong hộp sạc',
                    'Thời lượng pin' => 'Lên đến 6 giờ nghe (30 giờ kèm hộp)',
                    'Cổng kết nối' => 'USB-C, Hỗ trợ sạc MagSafe',
                    'Công nghệ âm thanh' => 'Chống ồn chủ động 2X, Âm thanh không gian thích ứng',
                ],
                'status' => 'Active',
                'variants' => [
                    [
                        'sku' => 'APP2-USBC-WHT',
                        'variant_name' => 'Trắng (White) - Hộp sạc USB-C',
                        'price' => 6190000,
                        'sale_price' => 5390000,
                        'quantity' => 45,
                        'status' => 'Active',
                    ],
                ],
            ],
            [
                'category_slug' => 'am-thanh',
                'name' => 'Sony WH-1000XM5',
                'slug' => 'sony-wh-1000xm5',
                'brand' => 'Sony',
                'description' => 'Tai nghe chống ồn đỉnh cao Sony WH-1000XM5 với 8 micro chống ồn, chip xử lý V1 và chất lượng âm thanh Hi-Res LDAC.',
                'specifications' => [
                    'Driver' => '30mm màng loa sợi carbon tổng hợp',
                    'Thời lượng pin' => '30 giờ (bật chống ồn), 40 giờ (tắt chống ồn)',
                    'Kết nối' => 'Bluetooth 5.2, LDAC, jack 3.5mm',
                    'Trọng lượng' => '250g',
                ],
                'status' => 'Active',
                'variants' => [
                    [
                        'sku' => 'WH1000XM5-BLK',
                        'variant_name' => 'Đen (Black)',
                        'price' => 8490000,
                        'sale_price' => 6990000,
                        'quantity' => 20,
                        'status' => 'Active',
                    ],
                    [
                        'sku' => 'WH1000XM5-SLV',
                        'variant_name' => 'Bạc (Silver)',
                        'price' => 8490000,
                        'sale_price' => 6990000,
                        'quantity' => 15,
                        'status' => 'Active',
                    ],
                ],
            ],

            // 5. Phụ kiện
            [
                'category_slug' => 'phu-kien',
                'name' => 'Logitech MX Master 3S',
                'slug' => 'logitech-mx-master-3s',
                'brand' => 'Logitech',
                'description' => 'Chuột công thái học cao cấp Logitech MX Master 3S với cảm biến quang học 8000 DPI trên mọi bề mặt và phím click êm ái.',
                'specifications' => [
                    'Cảm biến' => 'Darkfield high precision 8000 DPI',
                    'Con lăn' => 'MagSpeed điện từ siêu nhanh',
                    'Kết nối' => 'Bluetooth Low Energy & Logi Bolt USB',
                    'Thời lượng pin' => 'Lên tới 70 ngày một lần sạc đầy',
                ],
                'status' => 'Active',
                'variants' => [
                    [
                        'sku' => 'MXM3S-GRA',
                        'variant_name' => 'Graphite',
                        'price' => 2490000,
                        'sale_price' => 2190000,
                        'quantity' => 35,
                        'status' => 'Active',
                    ],
                    [
                        'sku' => 'MXM3S-PGR',
                        'variant_name' => 'Pale Grey',
                        'price' => 2490000,
                        'sale_price' => null,
                        'quantity' => 20,
                        'status' => 'Active',
                    ],
                ],
            ],

            // 6. Đồng hồ thông minh
            [
                'category_slug' => 'dong-ho-thong-minh',
                'name' => 'Apple Watch Ultra 2',
                'slug' => 'apple-watch-ultra-2',
                'brand' => 'Apple',
                'description' => 'Đồng hồ thể thao chuyên nghiệp Apple Watch Ultra 2 vỏ titan 49mm bền bỉ, màn hình 3000 nits siêu sáng và chip S9 SiP.',
                'specifications' => [
                    'Kích thước' => '49mm vỏ Titanium chuẩn hàng không vũ trụ',
                    'Màn hình' => 'OLED Retina Always-On 3000 nits, kính Sapphire',
                    'Vi xử lý' => 'Apple S9 SiP, thao tác chạm hai lần Double Tap',
                    'Pin' => 'Lên đến 36 giờ (72 giờ chế độ tiết kiệm pin)',
                    'Kháng nước' => '100m, chứng nhận lặn EN13319',
                ],
                'status' => 'Active',
                'variants' => [
                    [
                        'sku' => 'AWU2-49-OCEAN-BLU',
                        'variant_name' => 'Dây Ocean Xanh - 49mm',
                        'price' => 21990000,
                        'sale_price' => 19990000,
                        'quantity' => 10,
                        'status' => 'Active',
                    ],
                    [
                        'sku' => 'AWU2-49-TRAIL-GRN',
                        'variant_name' => 'Dây Trail Loop Xanh/Xám - 49mm',
                        'price' => 21990000,
                        'sale_price' => null,
                        'quantity' => 8,
                        'status' => 'Active',
                    ],
                ],
            ],

            // 7. Sản phẩm INACTIVE để test ẩn khỏi Public API
            [
                'category_slug' => 'phu-kien',
                'name' => 'Bàn phím cơ Logitech G Pro X TKL',
                'slug' => 'logitech-g-pro-x-tkl',
                'brand' => 'Logitech',
                'description' => 'Bàn phím cơ không dây TKL dành cho game thủ esports thi đấu chuyên nghiệp (đã ngừng kinh doanh).',
                'specifications' => [
                    'Switch' => 'GX Red Linear',
                    'Kết nối' => 'Lightspeed không dây, Bluetooth, USB-C',
                    'Đèn nền' => 'RGB Lightsync',
                ],
                'status' => 'Inactive', // Inactive test
                'variants' => [
                    [
                        'sku' => 'LOGI-GPROX-BLK',
                        'variant_name' => 'Đen - GX Red Switch',
                        'price' => 3890000,
                        'sale_price' => null,
                        'quantity' => 10,
                        'status' => 'Active',
                    ],
                ],
            ],
        ];

        $productImagesMap = [
            'iphone-15-pro-max' => 'products/1777637811_iphone.jpg',
            'samsung-galaxy-s24-ultra' => 'products/1777637830_samsung.jpg',
            'xiaomi-14' => 'products/1777637844_xiaomi.jpg',
            'macbook-pro-14-m3' => 'products/1777637866_macbook.jpg',
            'asus-rog-strix-g16' => 'products/1777637936_lap_rog.jpg',
            'dell-xps-14-9440' => 'products/1777637925_lap.jpg',
            'ipad-pro-m4-11-inch' => 'products/1777637975_tablet_apple.jpg',
            'samsung-galaxy-tab-s9' => 'products/1777637951_tablet.jpg',
            'airpods-pro-2-usb-c' => 'products/1777638022_tai_nghe_ko_d__y.jpg',
            'sony-wh-1000xm5' => 'products/1777638105_tainghe.jpg',
            'chuot-logitech-mx-master-3s' => 'products/1777638040_chu___t.jpg',
            'ban-phim-co-logitech-g-pro-x-tkl' => 'products/1777638032_d__y_s___c.jpg',
        ];

        foreach ($products as $p) {
            $catId = $categoryMap[$p['category_slug']] ?? null;
            if (!$catId) {
                continue;
            }

            $specJson = json_encode($p['specifications'], JSON_UNESCAPED_UNICODE);

            $prodRecord = DB::table('products')->where('slug', $p['slug'])->first();

            if ($prodRecord) {
                DB::table('products')->where('id', $prodRecord->id)->update([
                    'category_id' => $catId,
                    'name' => $p['name'],
                    'brand' => $p['brand'],
                    'description' => $p['description'],
                    'specifications' => $specJson,
                    'status' => $p['status'],
                    'updated_at' => $now,
                ]);
                $productId = $prodRecord->id;
            } else {
                $productId = DB::table('products')->insertGetId([
                    'category_id' => $catId,
                    'name' => $p['name'],
                    'slug' => $p['slug'],
                    'brand' => $p['brand'],
                    'description' => $p['description'],
                    'specifications' => $specJson,
                    'status' => $p['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Seed primary image
            if (isset($productImagesMap[$p['slug']])) {
                DB::table('product_images')->updateOrInsert(
                    [
                        'product_id' => $productId,
                        'is_primary' => 1,
                    ],
                    [
                        'image_path' => $productImagesMap[$p['slug']],
                        'sort_order' => 0,
                        'created_at' => $now,
                    ]
                );
            }

            // Seed variants + inventory
            foreach ($p['variants'] as $v) {
                $variantRecord = DB::table('product_variants')->where('sku', $v['sku'])->first();

                if ($variantRecord) {
                    DB::table('product_variants')->where('id', $variantRecord->id)->update([
                        'product_id' => $productId,
                        'variant_name' => $v['variant_name'],
                        'price' => $v['price'],
                        'sale_price' => $v['sale_price'],
                        'status' => $v['status'],
                        'updated_at' => $now,
                    ]);
                    $variantId = $variantRecord->id;
                } else {
                    $variantId = DB::table('product_variants')->insertGetId([
                        'product_id' => $productId,
                        'sku' => $v['sku'],
                        'variant_name' => $v['variant_name'],
                        'price' => $v['price'],
                        'sale_price' => $v['sale_price'],
                        'status' => $v['status'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                // Inventory
                DB::table('inventory')->updateOrInsert(
                    ['variant_id' => $variantId],
                    ['quantity' => $v['quantity'], 'updated_at' => $now]
                );
            }
        }
    }
}
