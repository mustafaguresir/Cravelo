# Cravelo — Yemek Sipariş ve Akıllı Kurye Atama Sistemi
## Aşama 2: Veritabanısız Çalışan Prototip (Saf PHP 8.2+ MVC)

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![PSR-4](https://img.shields.io/badge/Autoload-PSR--4-blue)](https://www.php-fig.org/psr/psr-4/)
[![PSR-12](https://img.shields.io/badge/Style-PSR--12-brightgreen)](https://www.php-fig.org/psr/psr-12/)
[![Architecture](https://img.shields.io/badge/Architecture-Pure%20MVC-orange)](#-mimari-ve-katman-sorumlulukları)
[![Server](https://img.shields.io/badge/Server-XAMPP%20%7C%20WAMP%20%7C%20PHP%20CLI-success)](#-kurulum-ve-çalıştırma-seçenekleri)
[![Tests](https://img.shields.io/badge/Tests-312%20Passing-green)](tests/)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%205%20%E2%80%94%200%20Errors-brightgreen)](phpstan.neon)

Modern, akıllı, katmanlı mimariye sahip ve **Aşama 2 (Veritabanısız Çalışan Prototip)** şartnamesine %100 uyumlu PHP web prototipi.

Bu proje; bir veritabanı sunucusuna (MySQL/SQLite) bağlanmadan çalışmakta, tüm kalıcı verilerini `storage/` klasöründeki JSON dosyalarında `flock(LOCK_EX)` dosya kilidi ve geçici dosya (`.tmp`) atomik takasıyla güvenle saklamaktadır.

---

## 👤 Proje Yöneticisi ve Geliştirici

* **Mustafa Güreşir** (Proje Yöneticisi & Full-Stack Geliştirici — `mustafaguresir@gmail.com`)

---

## 📌 Aşama 2 Şartname Kriterleri ve Projedeki Karşılıkları

| Şartname Kriteri | Beklenen Gereksinim | Cravelo Prototipindeki Karşılığı |
|---|---|---|
| **PHP Sürümü** | PHP 8.2 veya üzeri | PHP 8.2+ Typed Properties, Enums (`OrderStatus`, `CourierStatus`, `UserRole`) |
| **Paket & Standartlar** | Composer, PSR-4 otomatik yükleme, PSR-12, namespace | `composer.json`, `App\` namespace, PSR-4 uyumlu dizin yapısı |
| **Framework Yasağı** | Laravel/Symfony yok, saf PHP | Saf PHP ile sıfırdan geliştirilen MVC altyapısı |
| **Giriş Noktası & Katmanlar** | Tek giriş noktası (Front Controller), Router, Controller, Service, Repository, View ayrımı | `public/index.php`, `src/Core/*`, `src/Controller/*`, `src/Service/*`, `src/Repository/*`, `views/*` |
| **Veri Saklama (Aşama 2)** | MySQL/SQLite YOK; `storage/` altındaki JSON dosyaları | `storage/users.json`, `restaurants.json`, `menu_items.json`, `couriers.json`, `orders.json` |
| **Dosya Kilidi (`flock`)** | Eşzamanlı yazma riskine karşı `flock(LOCK_EX)` ve `.tmp` atomik dosya değişimi | `src/Storage/JsonStorageHelper.php` |
| **Başlangıç Verisi (Seeder)** | `bin/seed.php` CLI başlangıç veri yükleme betiği | `bin/seed.php` (10 kullanıcı, 7 restoran, 24 menü ürünü, 4 kurye, 4 sipariş; SPA demo: 27 ürün) |
| **Auth & PRG Akışı** | Kayıt, giriş, çıkış (`password_hash`), POST-Redirect-GET (PRG), flash mesajlar, CSRF, IDOR engeli | `AuthService.php`, `AuthController.php`, `Session::setFlash()`, `_csrf_token`, HTTP 303 Redirect |
| **Çekirdek Problem** | Sipariş durum makinesi (State Pattern) ve Haversine kurye puanlama optimizasyonu | `OrderStateMachineService.php`, `ScoreBasedAssignmentStrategy.php`, `OrderService.php` |
| **Birim Testler & Statik Analiz** | `vendor/bin/phpunit` (en az 10 test, InMemory depolar, sınır durumları) ve PHPStan | **31 PHPUnit 11 Testi (91 Assertion)** + **PHPStan Seviye 5 (0 Hata)** + 281 Ek Otomatik Test |
| **Kısmi Yenileme (AJAX)** | Sayfa yenilemeden JSON ile arayüz parçasını güncelleme + HTTP hata yönetimi | `craveloApiFetch()` (`/api/order-status`, `/api/kds-orders`, `/api/courier-tasks`) & hata toast bildirimi |
| **XAMPP / WAMP Uyumu** | Alt klasörde ve Apache `mod_rewrite` ile çalışabilme | Kök ve public `.htaccess`, dinamik `Request::detectBasePath()` |
| **Zorunlu Dokümanlar** | `docs/izlenebilirlik.md`, `docs/ai-gunlugu.md`, `docs/surec-raporu.md` | `docs/` altında eksiksiz mevcut |

---

## 🚀 Kurulum ve Çalıştırma Seçenekleri

Uygulama hem **XAMPP / WAMP** Apache web sunucularında hem de **PHP Dahili Sunucusunda** sıfır konfigürasyonla çalışacak şekilde tasarlanmıştır.

### Seçenek A: XAMPP / WAMP ile Çalıştırma (Önerilen)

Projeyi XAMPP veya WAMP dizinine taşımak zorunda kalmadan doğrudan çalıştırmak için hazır sihirbazlarımızı kullanabilirsiniz:
* **XAMPP için:** `XAMPP_PROJEYI_BAGLA.bat` dosyasına çift tıklayın (otomatik sembolik bağlantı oluşturur, Control Panel'i ve tarayıcıyı açar).
* **WAMP için:** `WAMP_PROJEYI_BAGLA.bat` dosyasına çift tıklayın (otomatik sembolik bağlantı oluşturur, WampServer'ı ve tarayıcıyı açar).

**Manuel Kurulum:**
1. **Projeyi Web Dizinine Yerleştirin (veya Junction Oluşturun):**
   * **XAMPP için:** `C:\xampp\htdocs\yemek-kurye-sistemi`
   * **WAMP için:** `C:\wamp64\www\yemek-kurye-sistemi`

2. **Apache Sunucusunu Başlatın:**
   * XAMPP Control Panel'den veya WampServer'dan **Apache** servisini başlatın (`mod_rewrite` ve `AllowOverride All` etkindir).

3. **Tarayıcınızda Açın:**
   👉 **`http://localhost/yemek-kurye-sistemi/`**
   * *Giriş Ekranı (PRG):* 👉 **`http://localhost/yemek-kurye-sistemi/login`**

*(Önemli Not: XAMPP ve WAMP ikisi de varsayılan olarak Port 80'i kullandığından aynı anda ikisinin de Apache servisi açılamaz; birini çalıştırırken diğerini durdurunuz).*

---

### Seçenek B: PHP Dahili Web Sunucusu ile Çalıştırma (CLI)

1. Proje kök dizininde terminal açın:
   ```bash
   composer install
   php bin/seed.php
   ```

2. Sunucuyu başlatın:
   ```bash
   php -S localhost:8000 -t public
   ```

3. Tarayıcınızda açın:
   👉 **`http://localhost:8000`**
   * *Giriş Ekranı (PRG):* 👉 **`http://localhost:8000/login`**

---

### Seçenek C: Windows Hızlı Başlatıcı (Çift Tıklama)

Windows ortamında kurulu PHP veya Python algılanarak sunucuyu ve tarayıcıyı tek tıkla açan toplu iş dosyasını çalıştırabilirsiniz:
* 👉 **`BASLAT.bat`** dosyasına çift tıklayın.

---

## 👥 Demo Kullanıcı Hesapları (Toplam 10 Kullanıcı)

Sistemdeki tüm tohumlanmış (seed) kullanıcıların parolası: **`hash123`** (PHP `password_hash` Bcrypt ile şifrelenmiştir).

| Rol | Kullanıcı Adı | E-Posta / Giriş | Parola | Yetki ve Panel Kapsamı |
| :--- | :--- | :--- | :--- | :--- |
| **Yönetici** | `admin_mustafa` | `mustafaguresir@gmail.com` | `hash123` | Tüm sistem denetimi, filo analitiği, tek yetkili yönetici |
| **Müşteri** | `ahmet_musteri` | `ahmet@ornek.com` | `hash123` | Sepet, sipariş verme, adres yönetimi, canlı harita takibi, hazırlık öncesi iptal |
| **Müşteri** | `ayse_musteri` | `ayse@ornek.com` | `hash123` | Müşteri sipariş paneli |
| **Müşteri** | `mehmet_musteri` | `mehmet@ornek.com` | `hash123` | Müşteri sipariş paneli |
| **Kurye** | `kurye_ali` | `ali.kurye@ornek.com` | `hash123` | Kurye kokpiti, görev kabulü ("Yolda"), teslimat onayı ("Teslim Edildi") |
| **Kurye** | `kurye_veli` | `veli.kurye@ornek.com` | `hash123` | Kurye kokpiti |
| **Kurye** | `kurye_can` | `can.kurye@ornek.com` | `hash123` | Kurye kokpiti |
| **Restoran** | `restoran_lezzet` | `lezzet@ornek.com` | `hash123` | KDS mutfak ekranı, sipariş onaylama ("Hazırlanıyor"), paketleme ("Hazır"), ciro raporu |
| **Restoran** | `restoran_burger` | `burger@ornek.com` | `hash123` | KDS mutfak ekranı |
| **Restoran** | `restoran_pizza` | `pizza@ornek.com` | `hash123` | KDS mutfak ekranı |

---

## 🏛️ Mimari ve Katman Sorumlulukları

Uygulama, sorumlulukların birbirinden kesin sınırlarla ayrıldığı saf MVC (Model-View-Controller) prensibiyle yapılandırılmıştır:

```text
yemek-kurye-sistemi/
├── bin/
│   └── seed.php                     # CLI Başlangıç veri yükleme betiği
├── composer.json                    # PSR-4 otomatik yükleme ve test/phpstan betikleri
├── phpunit.xml                      # PHPUnit 11 test süiti yapılandırması
├── phpstan.neon                     # PHPStan Seviye 5 statik analiz yapılandırması
├── Cravelo_Asama2_Sunum.pptx        # Akademik jüri savunma sunumu
├── docs/                            # Resmi aşama belgeleri
│   ├── izlenebilirlik.md            # Gereksinim - Test eşleştirme matrisi
│   ├── ai-gunlugu.md                # Yapay zekâ kullanım günlüğü (Hata vakaları dahil)
│   ├── surec-raporu.md              # 2-4 sayfalık resmi Aşama 2 akademik süreç raporu
│   ├── srs.md                       # Yazılım gereksinim şartnamesi
│   └── tasarim.md                   # 8 bölümlük Aşama 1 mimari tasarım dokümanı
├── storage/                         # Aşama 2 JSON Veritabanı (flock korumalı)
│   ├── users.json                   # 10 kullanıcı ve Bcrypt parolaları
│   ├── restaurants.json             # 7 restoran ve sabit Kadıköy koordinatları
│   ├── menu_items.json              # 24 tohumlanmış menü ürünü, kategoriler ve fiyatlar
│   ├── couriers.json                # 4 kurye, sabit konumlar ve iş yükleri
│   └── orders.json                  # 4 başlangıç siparişi ve yaşam döngüsü verileri
├── src/
│   ├── Core/                        # MVC Çekirdeği
│   │   ├── Controller.php           # Soyut temel kontrolcü sınıfı
│   │   ├── Request.php              # Dinamik Base Path (XAMPP/WAMP), HTTP metodları
│   │   ├── Response.php             # JSON, HTML ve 303 Redirect yanıtları
│   │   ├── Router.php               # Parametrik Regex rota motoru & güvenli 500 handler
│   │   ├── Session.php              # Flash mesajlar, eski form girdileri (old input), CSRF
│   │   └── View.php                 # Layout destekli güvenli şablon render motoru
│   ├── Exception/                   # Domain İstisnaları
│   │   ├── InvalidOrderTransitionException.php # Durum makinesi ihlali (DomainException alt sınıfı)
│   │   └── NoAvailableCourierException.php     # Boş kurye havuzu istisnası (RuntimeException)
│   ├── Controller/                  # İstek yönlendirme ve HTTP yanıt kontrolü
│   │   ├── AuthController.php       # Login (PRG + CSRF), Register (PRG + CSRF), Logout
│   │   ├── HomeController.php       # Kimlik denetimli rol bazlı ana gösterge paneli
│   │   ├── OrderController.php      # OrderService delegasyonu, PRG sipariş & IDOR koruma
│   │   └── ApiController.php        # Tek giriş noktalı AJAX kısmi yenileme uç noktaları
│   ├── Service/                     # Çekirdek İş Kuralları ve Domain Mantığı
│   │   ├── OrderService.php         # Sipariş yaşam döngüsü, KDS, kurye görev, ciro & IDOR orkestratörü
│   │   ├── OrderStateMachineService.php # Durum makinesi çekirdeği (State Pattern)
│   │   ├── CourierAssignmentService.php # Haversine mesafe ve puanlama algoritması
│   │   ├── HaversineDistanceService.php # R = 6371 km küresel mesafe hesabı
│   │   ├── AuthService.php          # password_hash, yetkilendirme ve IDOR denetimi
│   │   ├── Courier/                 # Strategy Pattern (ScoreBasedAssignmentStrategy)
│   │   └── State/                   # State Pattern somut durum sınıfları (6 durum)
│   ├── Repository/                  # Veri Erişim Soyutlama Katmanı (DIP)
│   │   ├── *RepositoryInterface.php # Repository arayüzleri
│   │   ├── Json/                    # Aşama 2 JSON veri depoları
│   │   └── InMemory/                # PHPUnit testleri için bellek içi sahte depolar
│   ├── Model/                       # Saf Varlıklar (Order, User, Courier, Restaurant vb.)
│   ├── Enum/                        # Tip güvenli PHP 8.2+ Enumlar (OrderStatus, UserRole)
│   └── Storage/
│       └── JsonStorageHelper.php    # flock(LOCK_EX) dosya kilidi ve .tmp atomik yazım
├── views/                           # Arayüz Şablonları (Views)
│   ├── layouts/
│   │   ├── main.php                 # Genel navigasyon ve arayüz iskeleti
│   │   └── auth.php                 # Kimlik doğrulama kart iskeleti
│   ├── auth/
│   │   ├── login.php                # Bootstrap giriş formu (Old input & Flash mesajlar)
│   │   └── register.php             # Bootstrap kayıt formu (Doğrulama hata kutuları)
│   ├── dashboard/
│   │   └── index.php                # Çok rollü ana gösterge ve harita paneli
│   └── errors/
│       ├── 403.php                  # Yetkisiz erişim güvenli hata ekranı
│       ├── 404.php                  # Sayfa bulunamadı hata ekranı
│       └── 500.php                  # Sunucu hatası güvenli ekranı (İç bilgi sızdırmaz)
├── public/                          # Web Kök Dizini (Tek Giriş Noktası)
│   ├── .htaccess                    # Apache Front Controller yönlendirme kuralları
│   ├── index.php                    # Front Controller (Tüm isteklerin giriş kapısı)
│   ├── index.html & login.html      # İstemci tarafı zengin SPA arayüzü
│   ├── css/                         # Stil dosyaları
│   └── js/                          # Harita motoru, KDS ve AJAX takip betikleri
└── tests/                           # Test Süitleri (Toplam 312 Test + PHPStan Seviye 5)
    ├── Unit/                        # 31 Adet PHPUnit 11 Birim Testi (6 Sınıf, 91 Assertion)
    ├── run_tests.php                # 20 Adet Bağımsız PHP Çekirdek Kural Testi
    ├── test_architectural_flows.php # 12 Adet Bağımsız PHP Mimari Akış Testi
    ├── test_architectural_flows.py  # 31 Adet Python PRG, BasePath ve flock Testi
    ├── run_all_cmd_tests.py         # 20 Adet Python Çekirdek Şartname Testi
    ├── verify_system.py             # 8 Adet Python Sistem Doğrulama Testi
    ├── test_burger_hero_scrollytelling.js # 58 Adet Scrollytelling & Hero Testi
    ├── test_landing_and_auth_gate.js      # 35 Adet Karşılama & Auth Gate Testi
    ├── test_address_and_restaurant.js     # 31 Adet Adres ve KDS Testi
    ├── test_comprehensive_improvements.js # 42 Adet Rol İzolasyonu ve HUD Testi
    └── test_login_and_registration.js     # 24 Adet Giriş ve Kayıt Doğrulama Testi
```

---

## 🧪 Test Süitlerini ve Statik Analizi Çalıştırma

Projede şartnamenin tüm kurallarını, sınır değerlerini, statik tip güvenliğini ve mutasyon deneylerini doğrulayan **312 otomatik test** mevcuttur:

### 1. PHPUnit 11 Birim Testleri (31 Test, 91 Assertion) & PHPStan (Seviye 5 — 0 Hata)
Bellek içi sahte repository sınıflarıyla (`InMemory*Repository`) diske bağımlı olmadan çalışır:
```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse
# veya Composer kısayolları:
composer test
composer phpstan
# veya bağımsız PHP koşucuları (32 Test):
php tests/run_tests.php
php tests/test_architectural_flows.php
```

### 2. Python Mimari, Şartname ve Sistem Doğrulama Testleri (59 Test)
Sipariş durum makinesi, Haversine algoritması, `password_hash`, IDOR engeli, sınır değerleri, Base Path ve `flock` kilit mekanizmasını terminal seviyesinde doğrular:
```bash
python tests/test_architectural_flows.py   # 31 Test
python tests/run_all_cmd_tests.py          # 20 Test
python tests/verify_system.py              # 8 Test
```

### 3. Node.js Arayüz, Scrollytelling ve RBAC Testleri (190 Test)
```bash
node tests/test_burger_hero_scrollytelling.js   # 58 Test
node tests/test_landing_and_auth_gate.js        # 35 Test
node tests/test_comprehensive_improvements.js   # 42 Test
node tests/test_address_and_restaurant.js       # 31 Test
node tests/test_login_and_registration.js       # 24 Test
```

### 4. Tüm Testleri Tek Seferde Çalıştırma (Windows)
* 👉 **`TESTLERI_CALISTIR.bat`** dosyasına çift tıklayarak PHPUnit, PHPStan, PHP, Python ve Node.js test süitlerinin tamamını arka arkaya çalıştırabilirsiniz.

---

## 📑 Teslim Edilen Zorunlu Dokümanlar

1. **İzlenebilirlik Matrisi:** [`docs/izlenebilirlik.md`](docs/izlenebilirlik.md)  
   *Aşama 1 kullanıcı hikâyeleri (US-01..04) ve fonksiyonel gereksinimler (FR-01..16) ile test metotlarının birebir eşleştirmesi ve mutasyon testi deney sonuçları.*
2. **Yapay Zekâ Günlüğü:** [`docs/ai-gunlugu.md`](docs/ai-gunlugu.md)  
   *Yapay zekânın önerdiği SQLite veritabanı ve XAMPP alt dizin yolu hatalarının Mustafa Güreşir tarafından nasıl fark edilip düzeltildiğinin somut kayıtları.*
3. **Süreç Raporu:** [`docs/surec-raporu.md`](docs/surec-raporu.md)  
   *2–4 sayfalık akademik formatta; haftalık geliştirme özeti, mimari tasarım kararları, PHPStan Seviye 5 sonucu, eleştirmen geri bildirimleri ve Aşama 3 MySQL geçiş analizi.*
4. **Yazılım Gereksinim Şartnamesi (SRS):** [`docs/srs.md`](docs/srs.md)  
   *Aşama 1 resmi gereksinim dokümanı.*
5. **Mimari Tasarım Dokümanı:** [`docs/tasarim.md`](docs/tasarim.md)  
   *Aşama 1 sekiz bölümlük detaylı mimari tasarım belgesi (Aşama 2 JSON/Cravelo geçiş notuyla senkronize).*
