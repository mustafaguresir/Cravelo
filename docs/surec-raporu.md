# Cravelo — Aşama 2 Resmi Süreç Raporu (Akademik Süreç Günlüğü)

**Ders:** Web Tabanlı Programlama / Yazılım Mimarisi ve Tasarımı  
**Proje Adı:** Cravelo — Yemek Sipariş ve Akıllı Kurye Atama Sistemi  
**Proje Sahibi ve Tek Geliştirici (Bireysel Sunum):**  
* **Mustafa Güreşir** (Proje Yöneticisi & Full-Stack Geliştirici — `mustafaguresir@gmail.com`)  
**GitHub Deposu:** `https://github.com/mustafaguresir/Cravelo`  
**Proje Aşaması:** Aşama 2 (Veritabanısız Çalışan Prototip)  
**Doküman Sürümü:** 2.2 (PHPUnit 11 + PHPStan Seviye 5 + Saf MVC & Cravelo Arayüz Sürümü)

---

## 1. Proje Tanımı ve Çekirdek Problem

### 1.1. Projenin Amacı ve İşlevi
**Cravelo**, yemek siparişi veren müşteriler, siparişi mutfakta hazırlayan restoranlar ve siparişi adrese ulaştıran kuryeler arasındaki döngüyü modern web standartlarında ve yüksek güvenilirlikle yöneten katmanlı bir platformdur. Sistem; müşteri sipariş ve canlı harita takip panelini, restoran mutfak yönetim ekranını (Kitchen Display System - KDS), kurye lojistik kokpitini ve yönetici denetim merkezini tek bir mimaride birleştirir.

Uygulama; herhangi bir üçüncü taraf PHP framework (Laravel, Symfony vb.) kullanılmaksızın, saf PHP 8.2+ ile **Front Controller, Router, Controller, Service, Repository ve View** katmanlarına ayrılmış saf MVC mimarisinde geliştirilmiştir.

### 1.2. Çekirdek Problemlerimiz
Projemizin ayırt edici ve çözümü zor olan iki ana çekirdek problemi bulunmaktadır:

1. **Sipariş Durum Makinesi Bütünlüğü (Order State Machine Integrity):**  
   Sipariş durumları (`alindi ➔ hazirlaniyor ➔ hazir ➔ yolda ➔ teslim_edildi`) tek yönlü, sıralı ve geri döndürülemez bir otomat olarak çalışmalıdır. Restoran yemeği pişirmeye başladığında (`hazirlaniyor`) müşterinin siparişi keyfi iptal etmesi engellenmelidir; aynı şekilde kuryeye teslim edilen (`yolda`) bir siparişin sistem hatasıyla geriye "hazırlanıyor" veya "alındı" durumuna döndürülmesi kesinlikle engellenmelidir. Bu kural **State Pattern (Durum Tasarım Deseni)** kullanılarak nesne yönelimli olarak kapsüllenmiştir.

2. **İş Yükü Dengelemeli Akıllı Kurye Atama Algoritması:**  
   Restoran paketi hazır ettiğinde, yalnızca en yakın kuryeyi seçmek kuryeler arasında adaletsizliğe ve aşırı iş yükü nedeniyle teslimat gecikmelerine yol açar. Sistemimiz **Haversine Büyük Daire Küresel Mesafesi** ile kuryenin anlık iş yükünü harmanlayan bir ağırlık formülüyle çalışır:
   $$\text{Puan} = \text{Haversine Mesafesi (km)} \times 1.0 + 2.0 \times \text{Aktif Sipariş Sayısı}$$
   En düşük puana sahip kurye seçilerek hem mesafesi yakın hem de iş yükü boş olan kurye adil ve optimize biçimde atanır.

---

## 2. Aşama 2 Haftalık İş Özeti ve Görev Dağılımı

| Hafta | Planlanan ve Gerçekleştirilen İş Paketleri | Sorumlu Grup Üyeleri | Somut Çıktılar / Dosyalar |
| :--- | :--- | :--- | :--- |
| **5. Hafta** | Aşama 1 gereksinimlerinin incelenmesi; JSON dosya tabanlı mimariye geçiş; `storage/` klasör yapısı ve `JsonStorageHelper` sınıfında `flock(LOCK_EX)` dosya kilit mekanizmasının kurulması. | **Mustafa Güreşir** | `src/Storage/JsonStorageHelper.php`, `composer.json` |
| **6. Hafta** | Domain varlıkları (Model), Repository arayüzleri ve somut `Json*Repository` sınıflarının kodlanması; `bin/seed.php` başlangıç veri yükleyicisinin hazırlanması (10 kullanıcı, 7 restoran, 27 menü ürünü, 4 kurye, 4 sipariş). | **Mustafa Güreşir** | `src/Model/*`, `src/Repository/Json/*`, `bin/seed.php` |
| **7. Hafta** | Tek giriş noktalı Front Controller (`public/index.php`), dinamik Router (`src/Core/Router.php`), `OrderService` iş katmanı, Session, Flash Mesajlar, CSRF (`_csrf_token`), POST-Redirect-GET (PRG), Bcrypt `password_hash` ve IDOR koruması. | **Mustafa Güreşir** | `src/Core/*`, `src/Controller/*`, `src/Service/OrderService.php`, `src/Service/AuthService.php` |
| **8. Hafta** | **44 adet PHPUnit 11 birim testi** (`tests/Unit/*`), InMemory sahte depolar, **PHPStan Seviye 5 (0 hata)**, mutasyon testi deneyleri ve izlenebilirlik matrisi oluşturulması. | **Mustafa Güreşir** | `tests/Unit/*`, `phpstan.neon`, `src/Repository/InMemory/*`, `docs/izlenebilirlik.md` |
| **9. Hafta** | XAMPP/WAMP uyumluluğu (`.htaccess`, `Request::detectBasePath()`), tek giriş noktalı gerçek `fetch()` AJAX kısmi yenileme (`craveloApiFetch`), modern Cravelo arayüzü (Açık/Koyu mod), hata ekranları (403/404/500) ve akademik savunma sunumu. | **Mustafa Güreşir** | `.htaccess`, `public/.htaccess`, `public/js/app.js`, `views/*`, `docs/surec-raporu.md` |

---

## 3. Değerlendirilen Tasarım ve Uygulama Kararları

Aşama 2 mimarisi oluşturulurken üç temel karar teknik alternatifleriyle birlikte değerlendirilmiş ve seçim gerekçeleri kayıt altına alınmıştır:

### Karar 1: JSON Dosya Depolamada Atomik Geçici Dosya (.tmp) ve `flock(LOCK_EX)` Kullanımı
* **Alternatif:** Standart PHP `file_put_contents('orders.json', json_encode($data))` ile doğrudan yazma.
* **Neden Elendi:** Eşzamanlı iki HTTP isteği aynı anda sipariş kaydetmeye çalıştığında dosya kilidi olmadığı için son yazan ilkinin verisini ezer (Race Condition); ayrıca sunucu çökmesi veya elektrik kesintisi anında dosya 0 bayt (boş) kalır ve tüm veriler kaybolur.
* **Alınan Karar:** `JsonStorageHelper` sınıfında yazma işlemi önce geçici bir dosyaya (`.tmp.uniqid`) özel dosya kilidi altında (`LOCK_EX`) yazılır, `fflush()` ile diske basılır ve işletim sistemi seviyesinde atomik `rename()` ile dosya güvenle değiştirilir.

### Karar 2: Durum Yönetiminde State Pattern (Durum Tasarım Deseni) Kullanımı
* **Alternatif:** Controller veya Servis içinde iç içe onlarca `switch-case` ve `if-else` yazmak.
* **Neden Elendi:** Kodun okunabilirliğini ve bakımını imkansızlaştırır; SOLID ilkelerinden Open/Closed prensibini ihlal eder. Yeni bir durum (örneğin "iade_edildi") eklendiğinde tüm dosyanın baştan aşağı değiştirilmesini gerektirir.
* **Alınan Karar:** `OrderStateInterface` altında 6 somut sınıf (`ReceivedState`, `PreparingState`, `ReadyState`, `OnWayState`, `DeliveredState`, `CancelledState`) oluşturuldu. Her durum kendi geçiş kurallarını ve `canCancel()` şartını kendi sınıfında bağımsız olarak kapsülledi.

### Karar 3: XAMPP / WAMP Taşınabilirliği İçin Dinamik Base Path Algoritması
* **Alternatif:** Proje URL'lerini ve asset yollarını `/` veya `/yemek-kurye-sistemi/` olarak statik sabitlerle (hardcoded) tanımlamak.
* **Neden Elendi:** Proje bir bilgisayarda `http://localhost:8000` kök dizininde, hocanın bilgisayarında ise XAMPP altında `http://localhost/yemek-kurye-sistemi/` alt dizininde çalıştırıldığında linkler ve stiller 404 hatasına düşmektedir.
* **Alınan Karar:** `Request::detectBasePath()` metodu geliştirildi. Bu metot `$_SERVER['SCRIPT_NAME']` üzerinden çalışılan alt dizini dinamik olarak tespit eder ve `$request->url('/login')` çağrılarında doğru ön eki otomatik olarak ekler.

---

## 4. Yapay Zekâ Günlüğünden 3 Önemli Örnek

Yapay zekâdan alınan desteklerde Mustafa Güreşir tarafından eleştirel denetim yapılmış ve aşağıdaki müdahaleler gerçekleştirilmiştir:

1. **Yapay Zekânın Yanıldığı ve Geliştiricinin Düzelttiği Hata (Zorunlu Madde):**  
   Yapay zekâ, Aşama 2 veri katmanı için ısrarla `SQLite` veritabanı kurmayı, tablo şeması üretmeyi ve PDO bağlantısı yazmayı önermiştir. Aşama 2 şartnamesindeki *"Aşama 2’de MySQL veya başka bir veritabanı kullanmayacaksınız, veriler storage/ klasöründeki JSON dosyalarında tutulmalıdır"* kuralı gözetilerek yapay zekânın kodu reddedilmiş, `SqliteOrderRepository` depodan kaldırılmış ve `JsonOrderRepository` sınıfı sıfırdan uygulanmıştır (`docs/ai-gunlugu.md` Vaka 1).

2. **XAMPP Alt Dizin Yönlendirme Hatasının Düzeltilmesi:**  
   Yapay zekâ, login ve logout yönlendirmelerinde doğrudan `header('Location: /login')` kodunu önermiştir. XAMPP altında `localhost/yemek-kurye-sistemi/` klasöründe bu kod tarayıcıyı `localhost/login`'e yönlendirerek 404 hatası vermiştir. `Request::detectBasePath()` çözümü tasarlanarak dinamik alt dizin yönlendirmesi sağlanmıştır (`docs/ai-gunlugu.md` Vaka 2).

3. **IDOR ve Yetkisiz Erişim Güvenlik Açığı:**  
   Yapay zekâ `OrderController::show` metodunda yalnızca `isset($_SESSION['user_id'])` kontrolü yapmış; farklı bir müşterinin URL'deki `id` parametresini değiştirerek başka siparişleri görebileceği (IDOR açığı) tespit edilerek `OrderService::assertSessionUserCanAccessOrder()` ve `AuthService::assertCanAccessOrder()` sahiplik denetimleri zorunlu kılınmıştır (`docs/ai-gunlugu.md` Vaka 3).

---

## 5. Test Sayısı, PHPStan Sonucu, Sınır Durumları ve İzlenebilirlik

* **Toplam Otomatik Test Sayısı (360 Test — %100 Yeşil):**
  * **PHPUnit 11.5.58 (`vendor/bin/phpunit`):** **54 adet birim/entegrasyon testi (247 assertion)** — 8 test sınıfı (`OrderStateMachineTest`, `CourierAssignmentStrategyTest`, `SecurityAndAuthTest`, `JsonStorageFlockTest`, `OrderControllerTest`, `JsonOrderRepositoryTest`, `SecurityAndRepositoriesTest`, `PlatformFeaturesTest`).
  * **Bağımsız PHP Test Koşucuları:** **32 adet** (`tests/run_tests.php`: 20 + `tests/test_architectural_flows.php`: 12).
  * **Python Mimari & Şartname Testleri:** **59 adet** (`test_architectural_flows.py`: 31 + `run_all_cmd_tests.py`: 20 + `verify_system.py`: 8).
  * **Node.js Arayüz, Scrollytelling, RBAC, Derin Denetim & Platform UI Testleri:** **215 adet** (7 test dosyası).
  * *(Şartnamenin "en az 10 test" beklentisi 36 kat aşılmıştır.)*
* **PHPStan Statik Analiz Sonucu (`vendor/bin/phpstan analyse`):**  
  `phpstan.neon` yapılandırmasıyla tüm `src/` dizini **PHPStan 2.3.7 — Seviye 5 (Level 5)** üzerinde taranmış ve **`[OK] No errors` (0 hata)** sonucu elde edilmiştir.
* **Bellek İçi Sahte Repository (InMemory Repositories):**  
  Şartnamenin *"Testlerde gerçek JSON dosyalarını değiştirmek yerine mümkün olduğunda bellekte çalışan sahte Repository kullanın"* kuralı gereğince `InMemoryOrderRepository`, `InMemoryCourierRepository`, `InMemoryUserRepository` ve `InMemoryRestaurantRepository` sınıfları yazılmış, birim testler sıfır disk I/O ve tam tekrarlanabilirlikle çalıştırılmıştır.
* **Kritik Sınır Durumları (Boundary Cases):**
  1. *Kullanıcı Adı Sınır Değeri:* 2 karakterlik kullanıcı adı reddedilirken 3 karakterlik kullanıcı adı kabul edilir (`SecurityAndAuthTest::testUsernameShorterThanThreeCharsThrowsException` & `testUsernameWithThreeCharsIsAccepted`).
  2. *Parola Sınır Değeri:* 5 karakterlik parola reddedilirken 6 karakterlik parola kabul edilir (`SecurityAndAuthTest::testPasswordShorterThanSixCharsThrowsException` & `testPasswordWithSixCharsIsAccepted`).
  3. *Ters Durum Geçişi:* `yolda` durumundaki siparişin geriye mutfağa döndürülmesi `InvalidOrderTransitionException` (`DomainException`) ile kesin olarak reddedilir (`OrderStateMachineTest::testReverseTransitionFromOnWayToPreparingIsRejected`).
  4. *Boş Kurye Havuzu & Eşit Puan Kırıcı:* Bölgedeki tüm kuryeler meşgul olduğunda `NoAvailableCourierException` fırlatılır; iki kurye eşit puan aldığında aktif görevi daha az olan seçilir (`CourierAssignmentStrategyTest::testEqualScorePrefersLowerActiveOrders`).
* **İzlenebilirlik Tablosu Konumu:** [`docs/izlenebilirlik.md`](izlenebilirlik.md) dosyasında Aşama 1'deki US-01..04 ve FR-01..16 gereksinimleri test metotlarıyla birebir eşleştirilmiştir.

---

## 6. Karşılaşılan 3 Önemli Sorun ve Çözümleri

1. **Eşzamanlı JSON Yazma (Race Condition):**  
   * *Sorun:* İki istek aynı anda sipariş oluşturduğunda `orders.json` dosyasında son yazan ilkinin verisini ezmekteydi.
   * *Çözüm:* `flock(LOCK_EX)` ile dosya kilitleme ve doğrudan asıl dosyaya değil geçici dosyaya (`.tmp`) yazıp `rename` etme stratejisi uygulandı (`JsonStorageHelper`).
2. **Form Gönderiminde Çift Sipariş Riski (F5 Tuşlama):**  
   * *Sorun:* Kullanıcı sipariş verdikten sonra sayfayı yenilediğinde tarayıcı POST isteğini tekrarlayarak mükerrer sipariş açmaktaydı.
   * *Çözüm:* **POST-Redirect-GET (PRG)** akışı kuruldu; işlem başarılı olduğunda kullanıcı HTTP 303 (See Other) ile yönlendirildi ve bildirimler `Session::setFlash()` ile tek seferlik gösterildi.
3. **Mutfak Hazırlığında İptal Butonu Mantığı:**  
   * *Sorun:* Mutfak siparişi hazırlamaya başladığında müşterinin ekranda kalan iptal butonuna basabilmesi.
   * *Çözüm:* Butonun görünürlüğü durum makinesine bağlandı; ayrıca sunucu tarafında `OrderStateMachineService::cancel()` içinde `canCancel()` doğrulaması yapılarak yetkisiz istekler engellendi.

---

## 7. Eleştirmen Geri Bildirimleri ve Değerlendirme

* **Öneri 1 (Kabul Edildi):** *"Kurye seçiminde eğer iki kuryenin puanı tamamen eşitse neye göre karar veriliyor?"*  
  - *Uygulama:* Eşitlik durumunda (`abs($score - $lowestScore) < 0.0001`) aktif sipariş sayısı en az olan kuryeye öncelik veren ikincil kural `ScoreBasedAssignmentStrategy` içine eklendi ve `testEqualScorePrefersLowerActiveOrders` ile doğrulandı.
* **Öneri 2 (Kabul Edildi):** *"Şifreler düz metin tutulmamalı, PHP 8 standardı password_hash kullanılmalı ve form güvenliği CSRF ile desteklenmeli."*  
  - *Uygulama:* `User` modelinde Bcrypt `password_hash` / `password_verify` entegre edildi, giriş/kayıt formlarına `_csrf_token` koruması eklendi ve birim testlerle doğrulandı.
* **Öneri 3 (Gerekçeli Reddedildi):** *"Müşteriye kuryenin anlık canlı GPS konumunu ücretli harita API'si ile takip ettirin."*  
  - *Gerekçe:* Proje şartnamesinin *Kapsam Sınırı* bölümünde *"Harita servisi, canlı konum takibi ve ödeme yoktur; konumlar sabit koordinat veya mahalle olarak tutulur"* hükmü yer almaktadır. Arayüzdeki görsel harita katmanı yalnızca kullanıcı deneyimi için sabit Kadıköy koordinatlarını çizer; arka plandaki tüm mesafe hesaplamaları harici API/GPS olmaksızın sunucu tarafı sabit koordinatlar ve Haversine formülüyle yapılır.

---

## 8. Geliştirici Katkıları (Bireysel Proje — Mustafa Güreşir)

Proje **Mustafa Güreşir** (`https://github.com/mustafaguresir/Cravelo`) tarafından bireysel olarak geliştirilmiş ve savunulmaktadır:
* **Mimari & Arka Plan:** Tek giriş noktalı Front Controller (`public/index.php`), dinamik Router (`src/Core/*`), PRG + CSRF akışı, Controller sınıfları (`AuthController`, `OrderController`, `HomeController`, `ApiController`), `OrderService` iş katmanı, `InvalidOrderTransitionException` / `NoAvailableCourierException`, `bin/seed.php` tohumlayıcı, `JsonStorageHelper` (`flock` + `.tmp` atomik takas), State Pattern & Haversine/Strategy kurye atama motoru, Repository arayüzleri ve `Json*` / `InMemory*` depoları.
* **Kalite, Arayüz & Dokümantasyon:** 54 PHPUnit 11 testi (247 assertion), PHPStan Seviye 5 (0 hata), 306 ek Python/Node/PHP otomasyon testi (toplam 360/360), gerçek `fetch()` AJAX kısmi yenileme (`craveloApiFetch`), modüler `public/js/features/*.js` özellik katmanı (KDS CRUD, kurye vardiya/GPS, yönetici analitik/kurye atama, çift yönlü sohbet, gerçek yorumlar), `docs/izlenebilirlik.md`, `docs/ai-gunlugu.md`, `docs/surec-raporu.md` ve `Cravelo_Asama2_Sunum.pptx`.
* **Git İş Akışı:** Geliştirmeler `main` dalı korunarak özellik dalları (`feat/*`, `fix/*`, `docs/*`) ve Pull Request akışıyla yönetilir.

---

## 9. Aşama 3’te MySQL’e Geçiş Analizi ve Mimari Destek

Aşama 2'nin en büyük mimari başarısı, Controller ve Servis katmanlarının somut dosyalara değil **`RepositoryInterface`** arayüzlerine bağımlı olmasıdır (Dependency Inversion Principle).

### Değişecek Sınıflar:
Aşama 3'te MySQL'e geçildiğinde Controller, Service veya Model sınıflarında **tek bir satır iş kodu dahi değişmeyecektir.**  
Sadece `src/Repository/` altında yeni somut sınıflar yazılacaktır:
* `JsonOrderRepository` ➔ `MySqlOrderRepository`
* `JsonUserRepository` ➔ `MySqlUserRepository`
* `JsonCourierRepository` ➔ `MySqlCourierRepository`
* `JsonRestaurantRepository` ➔ `MySqlRestaurantRepository`
* `JsonMenuItemRepository` ➔ `MySqlMenuItemRepository`

### Öngörülen Riskler ve Çözümler:
* **Eşzamanlılık Riski (Race Condition):** JSON'da `flock` ile çözülen dosya kilidi, MySQL'e geçildiğinde `SELECT ... FOR UPDATE` (Pessimistic Locking) veya `status` sütununa koşullu UPDATE (Optimistic Locking) ile çözülecektir.
* **Transaction Bütünlüğü:** JSON'da geçici dosya ile sağlanan atomiklik, MySQL'de `PDO::beginTransaction()` ve `commit()` ile karşılanacaktır.
* **Foreign Key ve Cascade Kuralları:** JSON dosyalarında ilişkisel bütünlük kod tarafında kontrol edilirken MySQL tablolarında `FOREIGN KEY ... ON DELETE CASCADE` ile veri tabanı seviyesinde garantiye alınacaktır.

Mimarimiz Dependency Inversion ilkesine tam uyumlu olduğu için Aşama 3 geçişi pürüzsüz ve sıfır riskli olacaktır.
