-- ============================================================================
-- Cravelo — Aşama 3 Hazır İlişkisel Veritabanı Şeması (MySQL 8.0+ / InnoDB / 3NF)
-- Geliştirici: Mustafa Güreşir (mustafaguresir@gmail.com)
-- Not: Aşama 2 saf JSON depolama (storage/*.json) ile 1:1 varlık ve alan uyumludur.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Kullanıcılar Tablosu (RBAC: customer, restaurant, courier, admin)
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(32) NOT NULL DEFAULT '',
    role ENUM('customer', 'restaurant', 'courier', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Restoranlar Tablosu (Kadıköy Sabit Koordinatları + Şube Açık/Kapalı Durumu)
CREATE TABLE IF NOT EXISTS restaurants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    location_x SMALLINT NOT NULL DEFAULT 12,
    location_y SMALLINT NOT NULL DEFAULT 8,
    latitude DECIMAL(9,6) NOT NULL DEFAULT 40.985000,
    longitude DECIMAL(9,6) NOT NULL DEFAULT 29.028000,
    neighborhood VARCHAR(120) NOT NULL DEFAULT 'Moda, Kadıköy',
    phone VARCHAR(32) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_restaurants_user (user_id),
    INDEX idx_restaurants_active (is_active),
    CONSTRAINT fk_restaurants_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Menü Ürünleri Tablosu (KDS Tam CRUD + Anlık Stok Kontrolü)
CREATE TABLE IF NOT EXISTS menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(64) NOT NULL DEFAULT 'Ana Yemek',
    image_url VARCHAR(512) NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_menu_restaurant_avail (restaurant_id, is_available),
    CONSTRAINT fk_menu_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Kuryeler Tablosu (Canlı Konum, Vardiya Durumu ve Aktif İş Yükü)
CREATE TABLE IF NOT EXISTS couriers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL UNIQUE,
    name VARCHAR(140) NOT NULL,
    vehicle_type VARCHAR(40) NOT NULL DEFAULT 'Motor',
    current_x SMALLINT NOT NULL DEFAULT 14,
    current_y SMALLINT NOT NULL DEFAULT 10,
    latitude DECIMAL(9,6) NOT NULL DEFAULT 40.991000,
    longitude DECIMAL(9,6) NOT NULL DEFAULT 29.025000,
    neighborhood VARCHAR(120) NOT NULL DEFAULT 'Rıhtım, Kadıköy',
    active_orders_count INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('musait', 'mesgul', 'offline') NOT NULL DEFAULT 'musait',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_couriers_status_load (status, active_orders_count),
    CONSTRAINT fk_couriers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Siparişler Tablosu (Durum Makinesi, Teslimat Modu ve Koordinatlar)
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    restaurant_id INT UNSIGNED NOT NULL,
    courier_id INT UNSIGNED NULL,
    status ENUM('alindi', 'hazirlaniyor', 'hazir', 'yolda', 'teslim_edildi', 'iptal') NOT NULL DEFAULT 'alindi',
    delivery_mode ENUM('courier', 'takeaway') NOT NULL DEFAULT 'courier',
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_x SMALLINT NOT NULL DEFAULT 18,
    delivery_y SMALLINT NOT NULL DEFAULT 14,
    delivery_latitude DECIMAL(9,6) NOT NULL DEFAULT 40.985000,
    delivery_longitude DECIMAL(9,6) NOT NULL DEFAULT 29.028000,
    estimated_delivery_time VARCHAR(40) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_status (status),
    INDEX idx_orders_customer (customer_id),
    INDEX idx_orders_restaurant (restaurant_id),
    INDEX idx_orders_courier (courier_id),
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES users(id),
    CONSTRAINT fk_orders_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id),
    CONSTRAINT fk_orders_courier FOREIGN KEY (courier_id) REFERENCES couriers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Sipariş Kalemleri Tablosu (N-M İlişki + Fiyat Anlık Görüntüsü)
CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    menu_item_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(140) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    INDEX idx_order_items_order (order_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Sipariş Durum Tarihçesi & Sistem Denetim Kaydı (Audit Log)
CREATE TABLE IF NOT EXISTS order_status_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NULL,
    previous_status VARCHAR(32) NULL,
    new_status VARCHAR(64) NOT NULL,
    changed_by_user_id INT UNSIGNED NULL,
    actor_label VARCHAR(120) NOT NULL DEFAULT 'Sistem',
    comment VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_order (order_id),
    INDEX idx_audit_created (created_at),
    CONSTRAINT fk_audit_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_audit_user FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Müşteri Değerlendirmeleri & Yorumlar (3 Kırılımlı Puanlama)
CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NULL,
    restaurant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    taste_score TINYINT UNSIGNED NOT NULL DEFAULT 5,
    speed_score TINYINT UNSIGNED NOT NULL DEFAULT 5,
    service_score TINYINT UNSIGNED NOT NULL DEFAULT 5,
    avg_rating DECIMAL(3,2) NOT NULL DEFAULT 5.00,
    tags_json JSON NULL,
    comment VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reviews_restaurant (restaurant_id),
    INDEX idx_reviews_order_user (order_id, user_id),
    CONSTRAINT fk_reviews_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    CONSTRAINT fk_reviews_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Çift Yönlü Müşteri <-> Kurye Sipariş Mesajları
CREATE TABLE IF NOT EXISTS order_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    sender_role ENUM('customer', 'restaurant', 'courier', 'admin') NOT NULL,
    sender_name VARCHAR(120) NOT NULL,
    message_text VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_messages_order_created (order_id, created_at),
    CONSTRAINT fk_messages_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Kullanıcı Kayıtlı Teslimat Adresleri
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(80) NOT NULL,
    address_desc VARCHAR(200) NOT NULL,
    location_x SMALLINT NOT NULL DEFAULT 18,
    location_y SMALLINT NOT NULL DEFAULT 14,
    latitude DECIMAL(9,6) NOT NULL DEFAULT 40.984000,
    longitude DECIMAL(9,6) NOT NULL DEFAULT 29.027000,
    neighborhood VARCHAR(120) NOT NULL DEFAULT 'Moda, Kadıköy',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_addresses_user (user_id),
    CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Cravelo Puan & Cüzdan Bakiyesi
CREATE TABLE IF NOT EXISTS user_wallets (
    user_id INT UNSIGNED PRIMARY KEY,
    wallet_points INT UNSIGNED NOT NULL DEFAULT 145,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
