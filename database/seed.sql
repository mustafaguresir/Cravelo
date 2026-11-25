-- ============================================================================
-- Cravelo — Aşama 3 Hazır İlişkisel Tohum Verisi (MySQL 8.0+ / bin/seed.php ile 1:1 Hizalı)
-- Kapsam: 10 Kullanıcı, 7 Restoran, 27 Menü Ürünü, 4 Kurye, 4 Sipariş, 3 Değerlendirme, 1 Mesaj, 3 Adres
-- Geliştirici: Mustafa Güreşir (mustafaguresir@gmail.com)
-- ============================================================================

SET NAMES utf8mb4;

-- 1. Kullanıcılar (10 Hesap — 3 Müşteri, 3 Restoran Sahibi, 3 Kurye, 1 Yönetici)
-- Not: Varsayılan demo parolası 'hash123' için standart PHP password_hash() Bcrypt özeti kullanılır.
INSERT INTO users (id, username, email, password_hash, full_name, phone, role) VALUES
(1, 'ahmet_musteri', 'ahmet@ornek.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmet Yılmaz', '05551112233', 'customer'),
(2, 'ayse_musteri', 'ayse@ornek.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ayşe Kaya', '05552223344', 'customer'),
(3, 'mehmet_musteri', 'mehmet@ornek.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mehmet Demir', '05553334455', 'customer'),
(4, 'restoran_lezzet', 'lezzet@ornek.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lezzet Kebap', '02163334455', 'restaurant'),
(5, 'restoran_burger', 'burger@ornek.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Burger Lab', '02164445566', 'restaurant'),
(6, 'restoran_pizza', 'pizza@ornek.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pizza Roma', '02165556677', 'restaurant'),
(7, 'kurye_ali', 'ali.kurye@ornek.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ali Kaya', '05554445566', 'courier'),
(8, 'kurye_veli', 'veli.kurye@ornek.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Veli Demir', '05555556677', 'courier'),
(9, 'kurye_can', 'can.kurye@ornek.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Can Öz', '05556667788', 'courier'),
(10, 'admin_mustafa', 'mustafaguresir@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mustafa Güreşir', '05557778899', 'admin')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), email = VALUES(email);

-- 2. Restoranlar (7 Kadıköy Partner Restoranı — bin/seed.php & data.js ile 1:1 Hizalı)
INSERT INTO restaurants (id, user_id, name, location_x, location_y, latitude, longitude, neighborhood, phone, is_active) VALUES
(1, 4, 'Lezzet Kebap Salonu', 12, 8, 40.985000, 29.028000, 'Moda, Kadıköy', '0216 444 10 01', 1),
(2, 5, 'Burger Lab', 16, 12, 40.988000, 29.031000, 'Bahariye, Kadıköy', '0216 444 20 02', 1),
(3, 6, 'Pizza Roma', 8, 19, 40.991000, 29.025000, 'Çarşı, Kadıköy', '0216 333 30 03', 1),
(4, 4, 'Sushico Express', 14, 16, 40.983000, 29.029000, 'Moda, Kadıköy', '0216 222 40 04', 1),
(5, 5, 'Tatlı Dünyası', 10, 14, 40.975000, 29.035000, 'Fenerbahçe, Kadıköy', '0216 777 50 05', 1),
(6, 6, 'Tavuk Dünyası', 18, 10, 40.989000, 29.034000, 'Altıyol, Kadıköy', '0216 888 60 06', 1),
(7, 4, 'Deniz Balık Lokantası', 5, 28, 40.963000, 29.062000, 'Caddebostan Sahil, Kadıköy', '0216 999 70 07', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), latitude = VALUES(latitude), longitude = VALUES(longitude), neighborhood = VALUES(neighborhood);

-- 3. Menü Ürünleri (Toplam 27 Ürün)
INSERT INTO menu_items (id, restaurant_id, name, description, price, category, is_available) VALUES
(1, 1, 'Adana Zırh Dürüm', 'Zırh kıyması, köz biber, domates, sumaklı soğan', 190.00, 'Kebap', 1),
(2, 1, 'Urfa Kebap Porsiyon', 'Acısız zırh eti, bulgur pilavı, köz sebze', 240.00, 'Kebap', 1),
(3, 1, 'Fındık Lahmacun (3 Adet)', 'Taş fırında çıtır hamur, taze kıymalı harç', 135.00, 'Kebap', 1),
(4, 1, 'İskender Kebap', 'Dana et, tereyağlı pide, domates sosu ve yoğurt', 290.00, 'Kebap', 1),
(5, 1, 'Köpüklü Yayık Ayran', '300ml doğal manda-inek sütü karışımı', 35.00, 'İçecek', 1),
(6, 2, 'Smash Cheeseburger', '140gr dana köfte, çifte cheddar, karamelize soğan', 250.00, 'Burger', 1),
(7, 2, 'BBQ Bacon Burger', 'Çıtır dana bacon, BBQ sos, cheddar, soğan halkası', 280.00, 'Burger', 1),
(8, 2, 'Truffle Parmesan Fries', 'Trüf yağı, rendelenmiş parmesan, cajun baharatı', 95.00, 'Yan Ürün', 1),
(9, 2, 'Nane Limonata', '330ml taze sıkım ev yapımı limonata', 50.00, 'İçecek', 1),
(10, 3, 'Margherita Pizza', 'San Marzano domates, manda mozzarella, fesleğen', 230.00, 'Pizza', 1),
(11, 3, 'Pepperoni Pizza', 'İtalyan sucuk, kekik, bol mozzarella', 280.00, 'Pizza', 1),
(12, 3, '4 Mevsim Pizza', 'Mantar, zeytin, biber, sucuk ve mozzarella', 265.00, 'Pizza', 1),
(13, 3, 'İtalyan Tiramisu', 'Mascarpone peyniri, espresso, kedi dili bisküvi', 120.00, 'Tatlı', 1),
(14, 4, 'California Roll (8 Parça)', 'Yengeç, avokado, salatalık, tobiko kaplamalı', 310.00, 'Sushi', 1),
(15, 4, 'Somon Teriyaki Bowl', 'Izgara somon, buharda pirinç, edamame, teriyaki', 360.00, 'Sushi', 1),
(16, 4, 'Çıtır Tavuk Noodle', 'Wok tavada sebzeler, kızarmış tavuk, susam', 220.00, 'Noodle', 1),
(17, 4, 'Ebi Tempura (6 Adet)', 'Çıtır hamurlu karides tempura, tatlı biber sosu', 290.00, 'Sushi', 1),
(18, 5, 'Belçika Waffle', 'Çilek, muz, fındık, sıcak Belçika çikolatası', 175.00, 'Tatlı', 1),
(19, 5, 'San Sebastian Cheesecake', 'Karamelize yanık üst tabaka, akışkan krema dolgu', 140.00, 'Tatlı', 1),
(20, 5, 'Buzlu Karamel Macchiato', 'Espresso, soğuk süt, vanilya, karamel şurubu', 75.00, 'Kahve', 1),
(21, 5, 'Çikolatalı Profiterol', 'Choux hamur, vanilyalı krema, sıcak çikolata', 110.00, 'Tatlı', 1),
(22, 6, 'Çıtır Tavuk Menü', '4 parça çıtır tavuk, patates, sos ve içecek', 195.00, 'Tavuk', 1),
(23, 6, 'Izgara Tavuk Wrap', 'Izgara tavuk, marul, domates, sarı mustard sos', 150.00, 'Tavuk', 1),
(24, 6, 'Tavuk Kanat (10 Adet)', 'Acılı/sade sos seçenekli, ranch dip sos ile', 180.00, 'Tavuk', 1),
(25, 7, 'Lüfer Izgara', 'Taze günün lüferi, zeytinyağlı, limon ve roka', 380.00, 'Balık', 1),
(26, 7, 'Karides Güveç', 'Tereyağlı sarımsaklı sos, ekmek ile servis', 320.00, 'Balık', 1),
(27, 7, 'Levrek Buğulama', 'Buharda pişirilmiş, taze sebzeler ve limon', 340.00, 'Balık', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price), category = VALUES(category);

-- 4. Kuryeler (4 Saha Kuryesi — Sabit Kadıköy Koordinatları & Tutarlı İş Yükü)
INSERT INTO couriers (id, user_id, name, vehicle_type, current_x, current_y, latitude, longitude, neighborhood, active_orders_count, status) VALUES
(1, 7, 'Ali Kaya (Kadıköy Rıhtım)', 'Motor', 14, 10, 40.991000, 29.025000, 'Rıhtım, Kadıköy', 1, 'musait'),
(2, 8, 'Veli Demir (Fenerbahçe)', 'Motor', 30, 28, 40.976000, 29.038000, 'Fenerbahçe, Kadıköy', 1, 'musait'),
(3, 9, 'Can Yıldız (Bahariye)', 'E-Bisiklet', 8, 16, 40.988000, 29.031000, 'Bahariye, Kadıköy', 0, 'musait'),
(4, NULL, 'Mehmet Arslan (Yeldeğirmeni)', 'Scooter', 10, 6, 40.995000, 29.028000, 'Yeldeğirmeni, Kadıköy', 0, 'mesgul')
ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), status = VALUES(status);

-- 5. Başlangıç Siparişleri (4 Sipariş)
INSERT INTO orders (id, customer_id, restaurant_id, courier_id, status, delivery_mode, total_amount, delivery_x, delivery_y, delivery_latitude, delivery_longitude, estimated_delivery_time) VALUES
(1, 1, 1, 2, 'alindi', 'courier', 380.00, 18, 14, 40.986000, 29.029000, '30 dk'),
(2, 2, 2, NULL, 'hazirlaniyor', 'courier', 345.00, 15, 11, 40.989000, 29.027000, '25 dk'),
(3, 1, 3, 1, 'yolda', 'courier', 280.00, 18, 14, 40.985000, 29.028000, '10 dk'),
(4, 3, 1, 3, 'teslim_edildi', 'courier', 480.00, 20, 26, 40.984000, 29.030000, 'Tamamlandı')
ON DUPLICATE KEY UPDATE status = VALUES(status), total_amount = VALUES(total_amount);

-- 6. Sipariş Kalemleri
INSERT INTO order_items (id, order_id, menu_item_id, item_name, quantity, unit_price, subtotal) VALUES
(1, 1, 1, 'Adana Zırh Dürüm', 2, 190.00, 380.00),
(2, 2, 6, 'Smash Cheeseburger', 1, 250.00, 250.00),
(3, 2, 8, 'Truffle Parmesan Fries', 1, 95.00, 95.00),
(4, 3, 11, 'Pepperoni Pizza', 1, 280.00, 280.00),
(5, 4, 2, 'Urfa Kebap Porsiyon', 2, 240.00, 480.00)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), subtotal = VALUES(subtotal);

-- 7. Müşteri Değerlendirmeleri (3 Doğrulanmış Yorum)
INSERT INTO reviews (id, order_id, restaurant_id, user_id, taste_score, speed_score, service_score, avg_rating, tags_json, comment) VALUES
(1, 4, 1, 3, 5, 5, 5, 5.00, '["Sıcacık Geldi","Süper Hızlı"]', 'Urfa kebap porsiyonu tam kıvamında ve dumanı üstünde geldi, teşekkürler!'),
(2, NULL, 2, 1, 5, 5, 4, 4.70, '["Doyurucu Porsiyon","Özenli Paket"]', 'Smash Cheeseburger ve trüflü patates Kadıköy''de tek geçilir.'),
(3, NULL, 3, 2, 5, 4, 5, 4.70, '["Sıcacık Geldi","Kurye Çok Kibardı"]', 'İnce çıtır hamur, bol mozzarella. Kurye Ali Bey çok nazikti.')
ON DUPLICATE KEY UPDATE comment = VALUES(comment);

-- 8. Sipariş Mesajı
INSERT INTO order_messages (id, order_id, sender_id, sender_role, sender_name, message_text) VALUES
(1, 3, 7, 'courier', 'Ali Kaya', 'Merhaba Ahmet Bey! Pizza Roma siparişinizi aldım, Moda Caddesi üzerinden 5 dakika içinde kapınızdayım.')
ON DUPLICATE KEY UPDATE message_text = VALUES(message_text);

-- 9. Kayıtlı Adresler & Cravelo Cüzdan
INSERT INTO user_addresses (id, user_id, title, address_desc, location_x, location_y, latitude, longitude, neighborhood, is_default) VALUES
(1, 1, 'Evim (Moda)', 'Moda Cad. No:14/2 Kadıköy', 18, 14, 40.984000, 29.027000, 'Moda, Kadıköy', 1),
(2, 1, 'İş Yerim (Bahariye)', 'Bahariye Cad. Süreyya Plaza No:22 K:3', 15, 11, 40.988500, 29.031500, 'Bahariye, Kadıköy', 0),
(3, 1, 'Sahil / Kalamış', 'Münir Nurettin Selçuk Cad. No:8/4 Kalamış', 20, 26, 40.974000, 29.041000, 'Kalamış Marina, Kadıköy', 0)
ON DUPLICATE KEY UPDATE address_desc = VALUES(address_desc);

INSERT INTO user_wallets (user_id, wallet_points) VALUES (1, 145)
ON DUPLICATE KEY UPDATE wallet_points = VALUES(wallet_points);
