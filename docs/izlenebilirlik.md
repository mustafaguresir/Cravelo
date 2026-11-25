# Cravelo — Aşama 2 İzlenebilirlik Matrisi (Traceability Matrix)

Bu belge, **Aşama 1** Yazılım Gereksinim Şartnamesi (`docs/srs.md`) içerisinde tanımlanan Kullanıcı Hikâyeleri (User Stories - US), Fonksiyonel Gereksinimler (FR) ve Fonksiyonel Olmayan Gereksinimler (NFR) ile **Aşama 2** kapsamında yazılan **44 adet PHPUnit 11 birim testi (189 assertion)**, çekirdek kural, sınır ve entegrasyon testlerinin birebir eşleştirmesini ve mutasyon testi laboratuvar kanıtlarını içerir. (**Geliştirici:** Mustafa Güreşir)

---

## 👥 1. Kullanıcı Hikâyeleri ve Kabul Kriterleri Referansı (Aşama 1 Uyumu)

`docs/srs.md` Bölüm 2.2'de tanımlanan resmi kullanıcı hikâyeleri:

| Hikâye Kodu | Rol & Başlık | Kullanıcı Hikâyesi Özeti | Temel Kabul Kriterleri | Eşleşen FR Kodları |
| :--- | :--- | :--- | :--- | :--- |
| **US-01** | **Müşteri** Sipariş & Canlı Takip | Bir müşteri olarak sepet oluşturup sipariş vermek ve durumunu haritada yalnızca kendi kuryemi görerek anlık izlemek istiyorum. | Sipariş `alindi` durumuyla başlar. Pişmeye geçmeden önce iptal edilebilir, pişerken iptal edilemez. Canlı takip F5'siz senkronize olur. | FR-01, FR-03, FR-12 |
| **US-02** | **Restoran** Mutfak & Hazırlık | Bir restoran yetkilisi olarak mutfak ekranında siparişleri sırayla hazırlamak, paket hazır olunca kurye çağırmak ve günlük ciromu izlemek istiyorum. | Sırasız durum atlaması yapılamaz. Pişen sipariş iptal edilemez. Paket hazır olunca kurye tetiklenir. Günlük satış özeti raporlanır. | FR-01, FR-02, FR-03, FR-13, FR-14 |
| **US-03** | **Kurye** Teslimatı & Akıllı Atama | Bir kurye olarak restoran mesafeme ve anlık iş yüküme göre bana en adil ve en uygun siparişlerin otomatik atanmasını istiyorum. | Seçim Haversine mesafesi ve $Puan = Mesafe + 2 \times Aktif$ formülüyle yapılır. Yoldaki sipariş geriye mutfağa dönemez. | FR-02, FR-04, FR-05, FR-06, FR-07, FR-11 |
| **US-04** | **Yönetici** Denetimi & Güvenlik | Bir sistem yöneticisi olarak sipariş akışını denetlemek, parolaların şifreli tutulmasını ve müşteri verilerinin izole edilmesini güvenceye almak istiyorum. | Parolalar Bcrypt ile şifrelenir. IDOR 403 ile engellenir. Veritabanısız depolamada `flock(LOCK_EX)` ile atomik bütünlük sağlanır. | FR-08, FR-09, FR-10, FR-11, FR-15, FR-16, NFR-01, NFR-02 |

---

## 🎯 2. İzlenebilirlik Tablosu (SRS FR Kodları ile %100 Birebir Eşleşme)

Aşağıdaki tablo, `docs/srs.md` belgesindeki FR-01 ila FR-16 numaralandırması ve açıklamalarıyla eksiksiz örtüşmektedir:

| SRS FR Kodu | İlgili Kullanıcı Hikâyesi | SRS Tanımı ve Kabul Kriteri | İlgili PHPUnit / Otomatik Test Metodu | Test Türü | Durum |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **FR-01** | **US-01 / US-02** | **Sıralı Durum Akışı:** Sipariş durumu yalnızca `alindi ➔ hazirlaniyor ➔ hazir ➔ yolda ➔ teslim_edildi` sırasında ilerleyebilir. Yeni sipariş mutlak suretle `alindi` başlar. | `OrderStateMachineTest::testOrderStartsInReceivedState`<br>`OrderStateMachineTest::testValidSequentialTransitionFlow`<br>`OrderControllerTest::testCreateOrderInitialStateIsAlindiAndAssignsCourier` | Olağan Akış / Controller | ✅ GEÇTİ |
| **FR-02** | **US-02 / US-03** | **Geçersiz Geçişlerin Kesin Reddi:** `yolda` durumundaki bir sipariş geriye mutfağa (`hazirlaniyor`/`alindi`) dönemez; sırasız durum atlamaları reddedilir (`InvalidOrderTransitionException` / `DomainException` — HTTP 400). | `OrderStateMachineTest::testReverseTransitionFromOnWayToPreparingIsRejected`<br>`OrderStateMachineTest::testInvalidSkipTransitionThrowsException` | **Sınır Durumu / İhlal (Sunum Kanıtı 1)** | ✅ GEÇTİ |
| **FR-03** | **US-01 / US-02** | **Koşullu İptal Yetkisi:** Müşteri siparişi yalnızca `alindi` iken iptal edebilir. Mutfak yemeği pişirmeye (`hazirlaniyor`) aldıktan sonra iptal kesinlikle reddedilir. | `OrderStateMachineTest::testOrderCanBeCancelledBeforePreparation`<br>`OrderStateMachineTest::testOrderCannotBeCancelledWhilePreparing` | **Çekirdek Kural / İptal Kilidi** | ✅ GEÇTİ |
| **FR-04** | **US-03** | **Haversine Küresel Mesafe Hesabı:** Restoran ile kurye arasındaki küresel büyük daire mesafesi $R = 6371\text{ km}$ ile hesaplanır (Hata payı < 0.1 km). | `CourierAssignmentStrategyTest::testHaversineDistanceCalculationIsAccurate` | Matematiksel Doğrulama | ✅ GEÇTİ |
| **FR-05** | **US-03** | **Ağırlıklı Kurye Puanlama Formülü:** Müsait kuryeler $Puan = Mesafe + 2 \times AktifSipariş$ formülüyle puanlanır; eşitlikte aktif siparişi az olan seçilir. | `CourierAssignmentStrategyTest::testLowerWorkloadCourierIsSelectedOverCloserBusyCourier`<br>`CourierAssignmentStrategyTest::testEqualScorePrefersLowerActiveOrders` | **Çekirdek Algoritma & Eşitlik Kuralı** | ✅ GEÇTİ |
| **FR-06** | **US-03** | **Kurye Seçim Test Doğrulaması:** Yakında ama meşgul olan Ali (0.71 km, 2 sipariş $\rightarrow$ 4.71) yerine, uzakta ama boşta olan Veli (1.31 km, 0 sipariş $\rightarrow$ 1.31) seçilmelidir. | `CourierAssignmentStrategyTest::testSameDistanceDifferentWorkloadSelectsIdleCourier`<br>`verify_system.py::test_proof_2` | **Eşitlik & Öncelik (Sunum Kanıtı 2)** | ✅ GEÇTİ |
| **FR-07** | **US-03** | **Müsait Kurye Yoksa Bekleme Kuyruğu:** Bölgede uygun kurye kalmadığında sistem çökmeyip `NoAvailableCourierException` fırlatarak siparişi hazır kuyruğunda tutar. | `CourierAssignmentStrategyTest::testEmptyCandidatesListThrowsException` | Sınır Durumu / Kuyruk | ✅ GEÇTİ |
| **FR-08** | **US-04** | **Veri Saklama (Asgari 7 Varlık / JSON Deposu):** Aşama 2 gereği ilişkisel veriler SQL veritabanı olmaksızın `storage/*.json` dosyalarında 7 ana varlık modeline uygun saklanır. | `JsonStorageFlockTest::testAtomicWriteAndReadWithFlock`<br>`verify_system.py::test_proof_4` | Depolama Bütünlüğü | ✅ GEÇTİ |
| **FR-09** | **US-04** | **Atomik Dosya İşlemleri (flock & Transaction):** Veri yazımları `flock(LOCK_EX)` dosya kilidi ve `.tmp` atomik dosya değişimi ile yarış durumlarına karşı korunur. | `JsonStorageFlockTest::testFlockExclusiveLockRejectsConcurrentNonBlockingLock`<br>`JsonStorageFlockTest::testAtomicWritePreservesOriginalFileIfWriteFails` | Eşzamanlılık / Atomiklik | ✅ GEÇTİ |
| **FR-10** | **US-04** | **Çoklu Nesne İlişkileri:** Sipariş, müşteri, restoran, kurye ve menü kalemleri 5 ilişkili nesne halinde taranıp `OrderService` üzerinden yönetilir. | `OrderControllerTest::testOrderLifecycleStateTransitions`<br>`JsonOrderRepositoryTest::testFindByIdWithRelations` | İlişkisel Bütünlük | ✅ GEÇTİ |
| **FR-11** | **US-03 / US-04** | **Eşzamanlı Kurye Seçimi Koruması:** İki siparişin aynı kuryeyi aynı anda kapmasını önleyen atomik kilit, koşullu durum güncellemesi ve iş yükü artırımı denetlenir. | `JsonStorageFlockTest::testFlockExclusiveLockRejectsConcurrentNonBlockingLock`<br>`JsonOrderRepositoryTest::testConditionalStatusUpdatePreventsStaleOverwrite`<br>`CourierAssignmentStrategyTest::testWorkloadIncrement` | Concurrency Guard | ✅ GEÇTİ |
| **FR-12** | **US-01** | **Sayfa Yenilemesiz Canlı Takip & AJAX:** Müşteri takip paneli F5 yapılmaksızın tek giriş noktası (`/api/order-status`, `/api/kds-orders`, `/api/courier-tasks`) üzerinden `fetch()` ile senkronize olur; beklenmeyen HTTP hatasında uyarı gösterir. | `public/js/app.js` (`craveloApiFetch`)<br>`public/js/state.js`<br>`verify_system.py::test_proof_3` | **Canlı Senkronizasyon (Sunum Kanıtı 3)** | ✅ GEÇTİ |
| **FR-13** | **US-02** | **Restoran Günlük Satış Raporu:** Restoran mutfak panelinde o gün teslim edilen siparişlerin adedi ve toplam cirosu dinamik hesaplanır. | `/api?action=daily_report`<br>`OrderControllerTest::testDailySummaryCalculation`<br>`JsonOrderRepositoryTest::testDailyReportFiltersByRestaurantAndDate` | Raporlama & Agregasyon | ✅ GEÇTİ |
| **FR-14** | **US-02** | **Restoran ve Ürün Sayısı Kotası:** Sistem en az 3 restoran ve en az 10 ürün barındırmalıdır (Projemizde 7 restoran ve `bin/seed.php` ile 24 / SPA ile 27 ürün aktiftir). | `verify_system.py::test_proof_5` | Kota Denetimi | ✅ GEÇTİ |
| **FR-15** | **US-04** | **Parola Güvenliği (password_hash & verify):** Parolalar Bcrypt ile tuzlanarak hash'lenir; asla düz metin saklanmaz. Tek korumalı yönetici (`admin_mustafa` — Mustafa Güreşir) tohumlanmıştır. | `SecurityAndAuthTest::testUserPasswordHashingAndVerification`<br>`SecurityAndAuthTest::testCannotRegisterAdminUser`<br>`SecurityAndAuthTest::testNonCustomerRegistrationThrowsException` | Güvenlik / Rol Kilidi | ✅ GEÇTİ |
| **FR-16** | **US-04** | **Girdi Sınır Denetimi (Boundary Values):** 3 karakterden kısa kullanıcı adı ve 6 karakterden kısa parola girişleri bağımsız testlerle reddedilir; geçerliler onaylanır. | `SecurityAndAuthTest::testUsernameShorterThanThreeCharsThrowsException`<br>`SecurityAndAuthTest::testUsernameWithThreeCharsIsAccepted`<br>`SecurityAndAuthTest::testPasswordShorterThanSixCharsThrowsException`<br>`SecurityAndAuthTest::testPasswordWithSixCharsIsAccepted` | **Ayrık Sınır Değer Testleri** | ✅ GEÇTİ |
| **NFR-01** | **US-04** | **IDOR Yetkisiz Erişim Engeli:** Müşteri A, başka bir müşterinin sipariş numarasını manipüle ederek detaylarına erişemez (`403 Forbidden`). | `SecurityAndAuthTest::testIdorProtectionPreventsUnauthorizedOrderAccess` | Güvenlik / IDOR | ✅ GEÇTİ |
| **NFR-02** | **US-04** | **RBAC Yetkilendirme:** Yönetici (`admin`) rolü denetim ve audit amacıyla tüm sipariş kayıtlarını sorgulayabilir. | `SecurityAndAuthTest::testAdminRoleCanAccessAnyOrder` | Yetki / RBAC | ✅ GEÇTİ |

---

## 🔬 3. Testleri Bozarak Doğrulama (Mutation Testing Laboratuvarı)

Şartname yönergesinde istenen:
> *"Testlerin gerçekten ilgili davranışı ölçtüğünü doğrulayın. Önemli bir kuralı geçici olarak bozduğunuzda ilgili testin başarısız olduğunu gösterin."*

maddesi uyarınca iki temel iş kuralı üzerinde kontrollü mutasyon deneyi yürütülmüş, testlerin hatayı derhal yakaladığı ve kod eski haline döndürüldüğünde testlerin tekrar yeşile döndüğü terminal çıktılarıyla kayıt altına alınmıştır.

---

### 🧪 Deney 1: Sipariş İptal Kuralının Kasıtlı Bozulması (FR-03)

* **Hedef Sınıf / Metot:** `src/Service/State/PreparingState.php::canCancel()`
* **Doğrulayan Test:** `OrderStateMachineTest::testOrderCannotBeCancelledWhilePreparing` (ve CMD Test 8)
* **Kuralın Özü:** Yemeği mutfakta hazırlanan müşteri siparişi iptal edilemez; `DomainException` fırlatılmalıdır.

#### 1. Mutasyon Öncesi Normal Durum (Test Başarılı)
```bash
vendor/bin/phpunit --filter testOrderCannotBeCancelledWhilePreparing
```
**Terminal Çıktısı (OK):**
```text
PHPUnit 11.5.58 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.35
Configuration: phpunit.xml

.                                                                   1 / 1 (100%)

Time: 00:00.042, Memory: 8.00 MB

OK (1 test, 1 assertion)
```

#### 2. Kasıtlı Kod Müdahalesi (Mutasyon Diff)
Hazırlık aşamasındaki `canCancel` metodu iptale izin verecek şekilde bozuldu:
```diff
--- a/src/Service/State/PreparingState.php
+++ b/src/Service/State/PreparingState.php
@@ -18,6 +18,6 @@
     public function canCancel(Order $order): bool
     {
-        return false;
+        return true; // [MUTASYON]: Hazırlanan siparişin iptaline bilerek izin verildi!
     }
 }
```

#### 3. Mutasyon Sonrası Test Çıktısı (Kuralın Bozulduğunu Kanıtlayan Hata)
```bash
vendor/bin/phpunit --filter testOrderCannotBeCancelledWhilePreparing
```
**Terminal Çıktısı (FAIL - Beklenen Hata Yakalandı):**
```text
PHPUnit 11.5.58 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.35
Configuration: phpunit.xml

F                                                                   1 / 1 (100%)

Time: 00:00.051, Memory: 8.00 MB

There was 1 failure:

1) Tests\Unit\OrderStateMachineTest::testOrderCannotBeCancelledWhilePreparing
Failed asserting that exception of type "DomainException" is thrown.

FAILURES!
Tests: 1, Assertions: 1, Failures: 1.
```
*(Ayrıca `python tests/run_all_cmd_tests.py` komutu çalıştırıldığında `[X HATA] TEST 8: Pişen yemek iptal edilemez kuralı ihlal edildi!` çıktısıyla süreç kırılmıştır).*

#### 4. Düzeltme (Rollback) ve Testin Tekrar Yeşile Dönüşü
```bash
git checkout src/Service/State/PreparingState.php
vendor/bin/phpunit --filter testOrderCannotBeCancelledWhilePreparing
```
**Terminal Çıktısı (Tekrar Başarılı):**
```text
.                                                                   1 / 1 (100%)
OK (1 test, 1 assertion)
```
**Laboratuvar Çıkarımı:** Testin `DomainException` fırlatılma zorunluluğunu eksiksiz denetlediği, kural kaldırıldığında anında kırmızıya düştüğü kanıtlanmıştır.

---

### 🧪 Deney 2: Kurye Puanlama Formülünün Bozulması (FR-05 & FR-06)

* **Hedef Sınıf / Metot:** `src/Service/Courier/ScoreBasedAssignmentStrategy.php::findBestCourier()`
* **Doğrulayan Test:** `CourierAssignmentStrategyTest::testLowerWorkloadCourierIsSelectedOverCloserBusyCourier` (ve CMD Test 11)
* **Kuralın Özü:** $Puan = Mesafe + 2 \times AktifSipariş$. Meşgul ama yakın olan Ali yerine boşta ama uzak olan Veli seçilmelidir.

#### 1. Mutasyon Öncesi Normal Durum (Test Başarılı)
```bash
vendor/bin/phpunit --filter testLowerWorkloadCourierIsSelectedOverCloserBusyCourier
```
**Terminal Çıktısı (OK):**
```text
PHPUnit 11.5.58 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.35
Configuration: phpunit.xml

.                                                                   1 / 1 (100%)

Time: 00:00.038, Memory: 8.00 MB

OK (1 test, 2 assertions)
```

#### 2. Kasıtlı Kod Müdahalesi (Mutasyon Diff)
İş yükü çarpanı ($+ 2 \times Aktif$) kasten silinip sadece saf mesafeye göre seçim yapılması sağlandı:
```diff
--- a/src/Service/Courier/ScoreBasedAssignmentStrategy.php
+++ b/src/Service/Courier/ScoreBasedAssignmentStrategy.php
@@ -50,3 +50,3 @@
-            $score = round($distanceKm + (2.0 * $courier->activeOrdersCount), 2);
+            $score = round($distanceKm, 2); // [MUTASYON]: 2*Aktif çarpanı silindi!
```

#### 3. Mutasyon Sonrası Test Çıktısı (Kuralın Bozulduğunu Kanıtlayan Hata)
```bash
vendor/bin/phpunit --filter testLowerWorkloadCourierIsSelectedOverCloserBusyCourier
```
**Terminal Çıktısı (FAIL - Beklenen Hata Yakalandı):**
```text
PHPUnit 11.5.58 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.35
Configuration: phpunit.xml

F                                                                   1 / 1 (100%)

Time: 00:00.045, Memory: 8.00 MB

There was 1 failure:

1) Tests\Unit\CourierAssignmentStrategyTest::testLowerWorkloadCourierIsSelectedOverCloserBusyCourier
Failed asserting that two strings are identical.
--- Expected
+++ Actual
@@ @@
-'Veli Demir'
+'Ali Kaya'

tests/Unit/CourierAssignmentStrategyTest.php:48

FAILURES!
Tests: 1, Assertions: 2, Failures: 1.
```

#### 4. Düzeltme (Rollback) ve Testin Tekrar Yeşile Dönüşü
```bash
git checkout src/Service/Courier/ScoreBasedAssignmentStrategy.php
vendor/bin/phpunit --filter testLowerWorkloadCourierIsSelectedOverCloserBusyCourier
```
**Terminal Çıktısı (Tekrar Başarılı):**
```text
.                                                                   1 / 1 (100%)
OK (1 test, 2 assertions)
```
**Laboratuvar Çıkarımı:** Testin $+ 2 \times Aktif$ çarpanını gerçekten ölçtüğü, bu çarpan olmadan yanlış kurye seçildiğinde testin derhal başarısız olduğu kesin olarak doğrulanmıştır.

---

## 🛡️ 4. Ayrık Sınır Değerleri & Güvenlik Doğrulama Tablosu (FR-16)

Önceki birleşik testte kullanıcı adı ve parola aynı anda hatalı gönderildiği için kısa parola denetimi bağımsız ölçülemiyordu. Aşama 2 kapsamında 4 ayrık sınır testi uygulanmıştır:

| Sınır Test Senaryosu | Gönderilen Değer | Beklenen Davranış | Test Metodu | Sonuç |
| :--- | :--- | :--- | :--- | :--- |
| **2 Karakterli Kullanıcı Adı** | `usr = "ab"`, `pwd = "123456"` | `InvalidArgumentException` fırlatılır | `SecurityAndAuthTest::testUsernameShorterThanThreeCharsThrowsException` | ✅ GEÇTİ |
| **3 Karakterli Kullanıcı Adı** | `usr = "abc"`, `pwd = "123456"` | Başarıyla kabul edilir ve kaydedilir | `SecurityAndAuthTest::testUsernameWithThreeCharsIsAccepted` | ✅ GEÇTİ |
| **5 Karakterli Parola** | `usr = "gecerli"`, `pwd = "12345"` | `InvalidArgumentException` fırlatılır | `SecurityAndAuthTest::testPasswordShorterThanSixCharsThrowsException` | ✅ GEÇTİ |
| **6 Karakterli Parola** | `usr = "gecerli"`, `pwd = "123456"` | Başarıyla kabul edilir ve Bcrypt hash üretilir | `SecurityAndAuthTest::testPasswordWithSixCharsIsAccepted` | ✅ GEÇTİ |

---

## 🔒 5. Gerçek flock(LOCK_EX) Dosya Kilidi & Atomik Takas Doğrulaması (FR-09)

`tests/Unit/JsonStorageFlockTest.php` dosyasında yalnızca dosya yazma/okuma değil, gerçek işletim sistemi dosya kilitleme ve arıza anında atomik koruma mekanizmaları doğrulanmıştır:

1. **Eşzamanlı Kilit Reddi (`testFlockExclusiveLockRejectsConcurrentNonBlockingLock`):**  
   Birinci dosya tanıtıcısı `$h1` tarafından `flock($h1, LOCK_EX)` özel kilidi tutulurken, ikinci bir tanıtıcı `$h2` üzerinden `flock($h2, LOCK_EX | LOCK_NB)` ile kilit alma denemesi yapılmış; işletim sisteminin kilit vermeyi reddettiği (`assertFalse`) ve `$wouldBlock === 1` bayrağının set edildiği doğrulanmıştır. `$h1` kilidi bıraktığında (`LOCK_UN`), `$h2` başarıyla kilit alabilmiştir.
2. **Arıza Halinde Orijinal Dosyanın Korunması (`testAtomicWritePreservesOriginalFileIfWriteFails`):**  
   Geçersiz / encode edilemeyen bir veri bloğu yazılmaya çalışıldığında sistemin geçici dosyayı çöpe attığı ve hedef dosyanın içeriğini %100 orijinal haliyle koruduğu (bozulmadığı veya sıfırlanmadığı) kanıtlanmıştır.

---

## 🚀 6. Hızlı Doğrulama Komutları

Sistemdeki **335 otomatik test** ve **PHPStan Seviye 5 statik analizi** tek komutla veya toplu betikle doğrulanabilir:

```bash
# 1. PHPUnit 11 Birim Testleri (44 Test, 189 Assertion — InMemory sahte depolar):
vendor/bin/phpunit --testdox

# 2. PHPStan Statik Analiz (Seviye 5 — 0 Hata):
vendor/bin/phpstan analyse

# 3. Python Çekirdek Şartname, Mimari Akış ve 3 Zorunlu Kanıt Testleri (59 Test):
python tests/test_architectural_flows.py
python tests/run_all_cmd_tests.py
python tests/verify_system.py

# 4. Tüm PHPUnit, PHPStan, PHP, Python ve Node.js testlerini birleştiren toplu batch dosyası:
TESTLERI_CALISTIR.bat
```
