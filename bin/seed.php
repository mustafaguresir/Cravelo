<?php
declare(strict_types=1);

/**
 * Cravelo — Aşama 2 JSON Veri Tohumlama (Seeder) Betiği
 * Kullanım: php bin/seed.php
 */

require_once __DIR__ . '/../src/Storage/JsonStorageHelper.php';
require_once __DIR__ . '/../src/Enum/UserRole.php';
require_once __DIR__ . '/../src/Enum/OrderStatus.php';
require_once __DIR__ . '/../src/Enum/CourierStatus.php';

use App\Storage\JsonStorageHelper;

$storageDir = dirname(__DIR__) . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0777, true);
}

echo "==============================================================\n";
echo "Cravelo Aşama 2: JSON Veritabanısız Veri Yükleyici (Seeder)\n";
echo "==============================================================\n";

// 1. KULLANICILAR (users.json)
$users = [
    [
        'id' => 1,
        'username' => 'ahmet_musteri',
        'email' => 'ahmet@ornek.com',
        'password_hash' => password_hash('hash123', PASSWORD_DEFAULT),
        'full_name' => 'Ahmet Yılmaz',
        'phone' => '05551112233',
        'role' => 'customer',
        'created_at' => '2026-10-20 10:00:00'
    ],
    [
        'id' => 2,
        'username' => 'ayse_musteri',
        'email' => 'ayse@ornek.com',
        'password_hash' => password_hash('hash123', PASSWORD_DEFAULT),
        'full_name' => 'Ayşe Kaya',
        'phone' => '05552223344',
        'role' => 'customer',
        'created_at' => '2026-10-20 10:05:00'
    ],
    [
        'id' => 3,
        'username' => 'mehmet_musteri',
        'email' => 'mehmet@ornek.com',
        'password_hash' => password_hash('hash123', PASSWORD_DEFAULT),
        'full_name' => 'Mehmet Demir',
        'phone' => '05553334455',
        'role' => 'customer',
        'created_at' => '2026-10-20 10:10:00'
    ],
    [
        'id' => 4,
        'username' => 'restoran_lezzet',
        'email' => 'lezzet@ornek.com',
        'password_hash' => password_hash('hash123', PASSWORD_DEFAULT),
        'full_name' => 'Lezzet Kebap',
        'phone' => '02163334455',
        'role' => 'restaurant',
        'created_at' => '2026-10-20 09:00:00'
    ],
    [
        'id' => 5,
        'username' => 'restoran_burger',
        'email' => 'burger@ornek.com',
        'password_hash' => password_hash('hash123', PASSWORD_DEFAULT),
        'full_name' => 'Burger Lab',
        'phone' => '02164445566',
        'role' => 'restaurant',
        'created_at' => '2026-10-20 09:10:00'
    ],
    [
        'id' => 6,
        'username' => 'restoran_pizza',
        'email' => 'pizza@ornek.com',
        'password_hash' => password_hash('hash123', PASSWORD_DEFAULT),
        'full_name' => 'Pizza Roma',
        'phone' => '02165556677',
        'role' => 'restaurant',
        'created_at' => '2026-10-20 09:20:00'
    ],
    [
        'id' => 7,
        'username' => 'kurye_ali',
        'email' => 'ali.kurye@ornek.com',
        'password_hash' => password_hash('hash123', PASSWORD_DEFAULT),
        'full_name' => 'Ali Kaya',
        'phone' => '05554445566',
        'role' => 'courier',
        'created_at' => '2026-10-20 08:30:00'
    ],
    [
        'id' => 8,
        'username' => 'kurye_veli',
        'email' => 'veli.kurye@ornek.com',
        'password_hash' => password_hash('hash123', PASSWORD_DEFAULT),
        'full_name' => 'Veli Demir',
        'phone' => '05555556677',
        'role' => 'courier',
        'created_at' => '2026-10-20 08:35:00'
    ],
    [
        'id' => 9,
        'username' => 'kurye_can',
        'email' => 'can.kurye@ornek.com',
        'password_hash' => password_hash('hash123', PASSWORD_DEFAULT),
        'full_name' => 'Can Öz',
        'phone' => '05556667788',
        'role' => 'courier',
        'created_at' => '2026-10-20 08:40:00'
    ],
    [
        'id' => 10,
        'username' => 'admin_mustafa',
        'email' => 'mustafaguresir@gmail.com',
        'password_hash' => password_hash('hash123', PASSWORD_DEFAULT),
        'full_name' => 'Mustafa Güreşir',
        'phone' => '05557778899',
        'role' => 'admin',
        'created_at' => '2026-10-20 08:00:00'
    ]
];
JsonStorageHelper::write($storageDir . '/users.json', $users);
echo "[OK] 10 Kullanıcı (1 Sistem Yöneticisi dahil) başarıyla 'storage/users.json' dosyasına kaydedildi.\n";

// 2. RESTORANLAR (restaurants.json — public/js/data.js ile 1:1 hizalı)
$restaurants = [
    [
        'id' => 1,
        'user_id' => 4,
        'name' => 'Lezzet Kebap Salonu',
        'location_x' => 12,
        'location_y' => 8,
        'latitude' => 40.9850,
        'longitude' => 29.0280,
        'neighborhood' => 'Moda, Kadıköy',
        'phone' => '0216 444 10 01',
        'is_active' => true
    ],
    [
        'id' => 2,
        'user_id' => 5,
        'name' => 'Burger Lab',
        'location_x' => 16,
        'location_y' => 12,
        'latitude' => 40.9880,
        'longitude' => 29.0310,
        'neighborhood' => 'Bahariye, Kadıköy',
        'phone' => '0216 444 20 02',
        'is_active' => true
    ],
    [
        'id' => 3,
        'user_id' => 6,
        'name' => 'Pizza Roma',
        'location_x' => 8,
        'location_y' => 19,
        'latitude' => 40.9910,
        'longitude' => 29.0250,
        'neighborhood' => 'Çarşı, Kadıköy',
        'phone' => '0216 333 30 03',
        'is_active' => true
    ],
    [
        'id' => 4,
        'user_id' => 4,
        'name' => 'Sushico Express',
        'location_x' => 14,
        'location_y' => 16,
        'latitude' => 40.9830,
        'longitude' => 29.0290,
        'neighborhood' => 'Moda, Kadıköy',
        'phone' => '0216 222 40 04',
        'is_active' => true
    ],
    [
        'id' => 5,
        'user_id' => 5,
        'name' => 'Tatlı Dünyası',
        'location_x' => 10,
        'location_y' => 14,
        'latitude' => 40.9750,
        'longitude' => 29.0350,
        'neighborhood' => 'Fenerbahçe, Kadıköy',
        'phone' => '0216 777 50 05',
        'is_active' => true
    ],
    [
        'id' => 6,
        'user_id' => 6,
        'name' => 'Tavuk Dünyası',
        'location_x' => 18,
        'location_y' => 10,
        'latitude' => 40.9890,
        'longitude' => 29.0340,
        'neighborhood' => 'Altıyol, Kadıköy',
        'phone' => '0216 888 60 06',
        'is_active' => true
    ],
    [
        'id' => 7,
        'user_id' => 4,
        'name' => 'Deniz Balık Lokantası',
        'location_x' => 5,
        'location_y' => 28,
        'latitude' => 40.9630,
        'longitude' => 29.0620,
        'neighborhood' => 'Caddebostan Sahil, Kadıköy',
        'phone' => '0216 999 70 07',
        'is_active' => true
    ]
];
JsonStorageHelper::write($storageDir . '/restaurants.json', $restaurants);
echo "[OK] 7 Restoran başarıyla 'storage/restaurants.json' dosyasına kaydedildi.\n";

// 3. MENÜ ÜRÜNLERİ (menu_items.json — public/js/data.js ile 1:1 hizalı 27 ürün)
$menuItems = [
    ['id' => 1, 'restaurant_id' => 1, 'name' => 'Adana Zırh Dürüm', 'description' => 'Zırh kıyması, köz biber, domates, sumaklı soğan', 'price' => 190.0, 'category' => 'Kebap', 'is_available' => true],
    ['id' => 2, 'restaurant_id' => 1, 'name' => 'Urfa Kebap Porsiyon', 'description' => 'Acısız zırh eti, bulgur pilavı, köz sebze', 'price' => 240.0, 'category' => 'Kebap', 'is_available' => true],
    ['id' => 3, 'restaurant_id' => 1, 'name' => 'Fındık Lahmacun (3 Adet)', 'description' => 'Taş fırında çıtır hamur, taze kıymalı harç', 'price' => 135.0, 'category' => 'Kebap', 'is_available' => true],
    ['id' => 4, 'restaurant_id' => 1, 'name' => 'İskender Kebap', 'description' => 'Dana et, tereyağlı pide, domates sosu ve yoğurt', 'price' => 290.0, 'category' => 'Kebap', 'is_available' => true],
    ['id' => 5, 'restaurant_id' => 1, 'name' => 'Köpüklü Yayık Ayran', 'description' => '300ml doğal manda-inek sütü karışımı', 'price' => 35.0, 'category' => 'İçecek', 'is_available' => true],

    ['id' => 6, 'restaurant_id' => 2, 'name' => 'Smash Cheeseburger', 'description' => '140gr dana köfte, çifte cheddar, karamelize soğan', 'price' => 250.0, 'category' => 'Burger', 'is_available' => true],
    ['id' => 7, 'restaurant_id' => 2, 'name' => 'BBQ Bacon Burger', 'description' => 'Çıtır dana bacon, BBQ sos, cheddar, soğan halkası', 'price' => 280.0, 'category' => 'Burger', 'is_available' => true],
    ['id' => 8, 'restaurant_id' => 2, 'name' => 'Truffle Parmesan Fries', 'description' => 'Trüf yağı, rendelenmiş parmesan, cajun baharatı', 'price' => 95.0, 'category' => 'Yan Ürün', 'is_available' => true],
    ['id' => 9, 'restaurant_id' => 2, 'name' => 'Nane Limonata', 'description' => '330ml taze sıkım ev yapımı limonata', 'price' => 50.0, 'category' => 'İçecek', 'is_available' => true],

    ['id' => 10, 'restaurant_id' => 3, 'name' => 'Margherita Pizza', 'description' => 'San Marzano domates, manda mozzarella, fesleğen', 'price' => 230.0, 'category' => 'Pizza', 'is_available' => true],
    ['id' => 11, 'restaurant_id' => 3, 'name' => 'Pepperoni Pizza', 'description' => 'İtalyan sucuk, kekik, bol mozzarella', 'price' => 280.0, 'category' => 'Pizza', 'is_available' => true],
    ['id' => 12, 'restaurant_id' => 3, 'name' => '4 Mevsim Pizza', 'description' => 'Mantar, zeytin, biber, sucuk ve mozzarella', 'price' => 265.0, 'category' => 'Pizza', 'is_available' => true],
    ['id' => 13, 'restaurant_id' => 3, 'name' => 'İtalyan Tiramisu', 'description' => 'Mascarpone peyniri, espresso, kedi dili bisküvi', 'price' => 120.0, 'category' => 'Tatlı', 'is_available' => true],

    ['id' => 14, 'restaurant_id' => 4, 'name' => 'California Roll (8 Parça)', 'description' => 'Yengeç, avokado, salatalık, tobiko kaplamalı', 'price' => 310.0, 'category' => 'Sushi', 'is_available' => true],
    ['id' => 15, 'restaurant_id' => 4, 'name' => 'Somon Teriyaki Bowl', 'description' => 'Izgara somon, buharda pirinç, edamame, teriyaki', 'price' => 360.0, 'category' => 'Sushi', 'is_available' => true],
    ['id' => 16, 'restaurant_id' => 4, 'name' => 'Çıtır Tavuk Noodle', 'description' => 'Wok tavada sebzeler, kızarmış tavuk, susam', 'price' => 220.0, 'category' => 'Noodle', 'is_available' => true],
    ['id' => 17, 'restaurant_id' => 4, 'name' => 'Ebi Tempura (6 Adet)', 'description' => 'Çıtır hamurlu karides tempura, tatlı biber sosu', 'price' => 290.0, 'category' => 'Sushi', 'is_available' => true],

    ['id' => 18, 'restaurant_id' => 5, 'name' => 'Belçika Waffle', 'description' => 'Çilek, muz, fındık, sıcak Belçika çikolatası', 'price' => 175.0, 'category' => 'Tatlı', 'is_available' => true],
    ['id' => 19, 'restaurant_id' => 5, 'name' => 'San Sebastian Cheesecake', 'description' => 'Karamelize yanık üst tabaka, akışkan krema dolgu', 'price' => 140.0, 'category' => 'Tatlı', 'is_available' => true],
    ['id' => 20, 'restaurant_id' => 5, 'name' => 'Buzlu Karamel Macchiato', 'description' => 'Espresso, soğuk süt, vanilya, karamel şurubu', 'price' => 75.0, 'category' => 'Kahve', 'is_available' => true],
    ['id' => 21, 'restaurant_id' => 5, 'name' => 'Çikolatalı Profiterol', 'description' => 'Choux hamur, vanilyalı krema, sıcak çikolata', 'price' => 110.0, 'category' => 'Tatlı', 'is_available' => true],

    ['id' => 22, 'restaurant_id' => 6, 'name' => 'Çıtır Tavuk Menü', 'description' => '4 parça çıtır tavuk, patates, sos ve içecek', 'price' => 195.0, 'category' => 'Tavuk', 'is_available' => true],
    ['id' => 23, 'restaurant_id' => 6, 'name' => 'Izgara Tavuk Wrap', 'description' => 'Izgara tavuk, marul, domates, sarı mustard sos', 'price' => 150.0, 'category' => 'Tavuk', 'is_available' => true],
    ['id' => 24, 'restaurant_id' => 6, 'name' => 'Tavuk Kanat (10 Adet)', 'description' => 'Acılı/sade sos seçenekli, ranch dip sos ile', 'price' => 180.0, 'category' => 'Tavuk', 'is_available' => true],

    ['id' => 25, 'restaurant_id' => 7, 'name' => 'Lüfer Izgara', 'description' => 'Taze günün lüferi, zeytinyağlı, limon ve roka', 'price' => 380.0, 'category' => 'Balık', 'is_available' => true],
    ['id' => 26, 'restaurant_id' => 7, 'name' => 'Karides Güveç', 'description' => 'Tereyağlı sarımsaklı sos, ekmek ile servis', 'price' => 320.0, 'category' => 'Balık', 'is_available' => true],
    ['id' => 27, 'restaurant_id' => 7, 'name' => 'Levrek Buğulama', 'description' => 'Buharda pişirilmiş, taze sebzeler ve limon', 'price' => 340.0, 'category' => 'Balık', 'is_available' => true]
];
JsonStorageHelper::write($storageDir . '/menu_items.json', $menuItems);
echo "[OK] 27 Menü Ürünü başarıyla 'storage/menu_items.json' dosyasına kaydedildi.\n";

// 4. KURYELER (couriers.json — benzersiz user_id ve orders.json ile tutarlı active_orders_count)
$couriers = [
    [
        'id' => 1,
        'user_id' => 7,
        'name' => 'Ali Kaya (Kadıköy Rıhtım)',
        'current_x' => 14,
        'current_y' => 10,
        'latitude' => 40.9910,
        'longitude' => 29.0250,
        'neighborhood' => 'Rıhtım, Kadıköy',
        'active_orders_count' => 1,
        'status' => 'musait'
    ],
    [
        'id' => 2,
        'user_id' => 8,
        'name' => 'Veli Demir (Fenerbahçe)',
        'current_x' => 30,
        'current_y' => 28,
        'latitude' => 40.9760,
        'longitude' => 29.0380,
        'neighborhood' => 'Fenerbahçe, Kadıköy',
        'active_orders_count' => 1,
        'status' => 'musait'
    ],
    [
        'id' => 3,
        'user_id' => 9,
        'name' => 'Can Yıldız (Bahariye)',
        'current_x' => 8,
        'current_y' => 16,
        'latitude' => 40.9880,
        'longitude' => 29.0310,
        'neighborhood' => 'Bahariye, Kadıköy',
        'active_orders_count' => 0,
        'status' => 'musait'
    ],
    [
        'id' => 4,
        'user_id' => 0,
        'name' => 'Mehmet Arslan (Yeldeğirmeni)',
        'current_x' => 10,
        'current_y' => 6,
        'latitude' => 40.9950,
        'longitude' => 29.0280,
        'neighborhood' => 'Yeldeğirmeni, Kadıköy',
        'active_orders_count' => 0,
        'status' => 'mesgul'
    ]
];
JsonStorageHelper::write($storageDir . '/couriers.json', $couriers);
echo "[OK] 4 Kurye başarıyla 'storage/couriers.json' dosyasına kaydedildi.\n";

// 5. BAŞLANGIÇ SİPARİŞLERİ (orders.json — hizalı menü ID'leri, birim fiyatları ve delivery_mode)
$orders = [
    [
        'id' => 1,
        'customer_id' => 1,
        'restaurant_id' => 1,
        'courier_id' => 2,
        'status' => 'alindi',
        'total_amount' => 380.0,
        'delivery_latitude' => 40.9860,
        'delivery_longitude' => 29.0290,
        'delivery_x' => 18,
        'delivery_y' => 14,
        'delivery_mode' => 'courier',
        'estimated_delivery_time' => '30 dk',
        'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
        'items' => [
            ['id' => 1, 'order_id' => 1, 'menu_item_id' => 1, 'name' => 'Adana Zırh Dürüm', 'quantity' => 2, 'unit_price' => 190.0, 'subtotal' => 380.0]
        ]
    ],
    [
        'id' => 2,
        'customer_id' => 2,
        'restaurant_id' => 2,
        'courier_id' => null,
        'status' => 'hazirlaniyor',
        'total_amount' => 345.0,
        'delivery_latitude' => 40.9890,
        'delivery_longitude' => 29.0270,
        'delivery_x' => 15,
        'delivery_y' => 11,
        'delivery_mode' => 'courier',
        'estimated_delivery_time' => '25 dk',
        'created_at' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
        'items' => [
            ['id' => 2, 'order_id' => 2, 'menu_item_id' => 6, 'name' => 'Smash Cheeseburger', 'quantity' => 1, 'unit_price' => 250.0, 'subtotal' => 250.0],
            ['id' => 3, 'order_id' => 2, 'menu_item_id' => 8, 'name' => 'Truffle Parmesan Fries', 'quantity' => 1, 'unit_price' => 95.0, 'subtotal' => 95.0]
        ]
    ],
    [
        'id' => 3,
        'customer_id' => 1,
        'restaurant_id' => 3,
        'courier_id' => 1,
        'status' => 'yolda',
        'total_amount' => 280.0,
        'delivery_latitude' => 40.9850,
        'delivery_longitude' => 29.0280,
        'delivery_x' => 18,
        'delivery_y' => 14,
        'delivery_mode' => 'courier',
        'estimated_delivery_time' => '10 dk',
        'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
        'items' => [
            ['id' => 4, 'order_id' => 3, 'menu_item_id' => 11, 'name' => 'Pepperoni Pizza', 'quantity' => 1, 'unit_price' => 280.0, 'subtotal' => 280.0]
        ]
    ],
    [
        'id' => 4,
        'customer_id' => 3,
        'restaurant_id' => 1,
        'courier_id' => 3,
        'status' => 'teslim_edildi',
        'total_amount' => 480.0,
        'delivery_latitude' => 40.9840,
        'delivery_longitude' => 29.0300,
        'delivery_x' => 20,
        'delivery_y' => 26,
        'delivery_mode' => 'courier',
        'estimated_delivery_time' => 'Tamamlandı',
        'created_at' => date('Y-m-d H:i:s', strtotime('-120 minutes')),
        'items' => [
            ['id' => 5, 'order_id' => 4, 'menu_item_id' => 2, 'name' => 'Urfa Kebap Porsiyon', 'quantity' => 2, 'unit_price' => 240.0, 'subtotal' => 480.0]
        ]
    ]
];
JsonStorageHelper::write($storageDir . '/orders.json', $orders);
echo "[OK] 4 Sipariş başarıyla 'storage/orders.json' dosyasına kaydedildi.\n";

// 6. DENETİM GÜNLÜĞÜ (audit_logs.json)
$auditLogs = [
    [
        'time' => date('H:i:s', strtotime('-120 minutes')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-120 minutes')),
        'action' => 'Sipariş Teslim Edildi',
        'user' => 'Can Yıldız (Kurye)',
        'detail' => 'Sipariş #4 — Lezzet Kebap Salonu • 480.00 ₺'
    ],
    [
        'time' => date('H:i:s', strtotime('-30 minutes')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
        'action' => 'Durum Geçişi: yolda',
        'user' => 'Ali Kaya (Kurye)',
        'detail' => 'Sipariş #3 — Pizza Roma • 280.00 ₺'
    ],
    [
        'time' => date('H:i:s', strtotime('-10 minutes')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
        'action' => 'Durum Geçişi: hazirlaniyor',
        'user' => 'Burger Lab (Mutfak)',
        'detail' => 'Sipariş #2 — 345.00 ₺'
    ],
    [
        'time' => date('H:i:s', strtotime('-15 minutes')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
        'action' => 'Yeni Sipariş Oluşturuldu',
        'user' => 'Ahmet Yılmaz (Müşteri)',
        'detail' => 'Sipariş #1 — Lezzet Kebap Salonu • 380.00 ₺ (Kurye: Veli Demir)'
    ]
];
JsonStorageHelper::write($storageDir . '/audit_logs.json', $auditLogs);
echo "[OK] 4 Başlangıç Denetim Kaydı 'storage/audit_logs.json' dosyasına kaydedildi.\n";

echo "==============================================================\n";
echo "AŞAMA 2 TOHUMLAMA (SEED) BAŞARIYLA TAMAMLANDI!\n";
echo "Tüm veriler 'storage/' altındaki JSON dosyalarında hazır.\n";
echo "==============================================================\n";

