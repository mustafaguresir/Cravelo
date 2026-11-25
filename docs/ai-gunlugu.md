# Cravelo — Yapay Zekâ Kullanım Günlüğü (AI Log)

Bu belge, **Cravelo** projesinin Aşama 2 (Veritabanısız Çalışan Prototip) geliştirilme sürecinde yapay zekâ (Claude, ChatGPT, Antigravity) araçlarının nasıl bir "teknik danışman, kod üretici ve denetçi" olarak kullanıldığını, sunulan önerilerin Proje Yöneticisi ve Tek Geliştirici **Mustafa Güreşir** tarafından nasıl eleştirel bir süzgeçten geçirildiğini, hangi kısımların reddedildiğini ve **yapay zekânın yanıldığı somut hataların nasıl tespit edilip düzeltildiğini** resmi olarak kayıt altına almaktadır.

---

## 📌 1. Yapay Zekâ Kullanım Özeti Tablosu

| Tarih | Kullanılan Araç | Danışılan Konu / Görev | Yapay Zekânın Önerisi | Geliştirici İncelemesi ve Kararı | Sonuç / Çıktı |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **14.10.2026** | Claude 3.5 Sonnet | State Pattern UML ve Sınıf Hiyerarşisi | 5 durum sınıfı ve bir State arayüzü (`OrderStateInterface`). | Kod incelendi. `proceed()` metoduna ek olarak `canCancel()` kuralı da arayüze eklendi. | ✅ Kabul edildi ve geliştirildi. |
| **20.10.2026** | ChatGPT-4o | Mesafe ve Kurye Puanlama Formülü | 2D Kartezyen Öklid ($d = \sqrt{\Delta x^2 + \Delta y^2}$) mesafesi önerdi. | **Kısmen Reddedildi:** Şartnamede gerçek dünya koordinatları ve Haversine formülü istendiği için formül Haversine trigonometrisiyle ($R = 6371\text{ km}$) değiştirildi. | ⚠️ Düzeltilerek uyarlandı. |
| **21.10.2026** | Antigravity AI | **Aşama 2 Veri Saklama Mimarisi (KRİTİK HATA)** | Veritabanı tabloları kurmayı ve `PDO SQLite` ile sorgu çalıştırmayı önerdi. | **KESİNLİKLE REDDEDİLDİ:** Aşama 2 şartnamesinde veritabanı kesinlikle yasaktır; verilerin `storage/*.json` dosyalarında `flock` ile tutulması şarttır. | ❌ **Hata Yakalandı & Düzeltildi.** |
| **22.10.2026** | Claude 3.5 Sonnet | Eşzamanlı JSON Yazma Güvenliği | Standart `file_put_contents` fonksiyonunu doğrudan çağırmayı önerdi. | **Yetersiz Bulundu:** Eşzamanlı siparişlerde dosya bozulmasını önlemek için `flock(LOCK_EX)` ve `.tmp` dosyasından atomik `rename` ile yazım kurgulandı. | ⚠️ Güvenlik artırıldı. |
| **23.10.2026** | ChatGPT-4o | IDOR Yetki ve Sahiplik Kontrolü | Sadece kullanıcının oturum açıp açmadığını (`isset($_SESSION['user'])`) kontrol etmeyi önerdi. | **Yetersiz Bulundu:** URL'deki `order_id` parametresi değiştirilerek başkasının verisine erişilebileceği fark edildi; `assertCanAccessOrder` ile sahiplik denetimi eklendi. | ✅ Güvenlik açığı kapatıldı. |
| **19.11.2026** | Claude 3.5 Sonnet | **XAMPP / WAMP Alt Dizin Yönlendirmesi (KRİTİK HATA)** | Yönlendirmelerde mutlak `/login` ve `/css/style.css` kök yollarını kullanmayı önerdi. | **KESİNLİKLE REDDEDİLDİ:** XAMPP ve WAMP ortamında proje `http://localhost/yemek-kurye-sistemi/` alt klasöründe çalıştırıldığında `/login` isteğinin Apache root'una (404 Not Found) düşeceği anlaşıldı. `Request::detectBasePath()` ile dinamik alt dizin çözümleyici geliştirildi. | ❌ **Hata Yakalandı & Düzeltildi.** |

---

## 🔍 2. Detaylı Vaka İncelemeleri ve Hata Analizleri

### Vaka 1 (Zorunlu Kriter: Yapay Zekânın Yanıldığı ve Geliştiricinin Düzelttiği Hata — Veritabanı Dayatması)

* **Problem / Danışılan Konu:**  
  Aşama 2 için veri kalıcılığı ve sipariş kayıt mekanizmasının tasarlanması.
* **Yapay Zekânın Hatalı Önerisi:**  
  Yapay zekâ, PHP projelerinde standart pratik olduğu gerekçesiyle projenin kök dizininde bir SQLite veritabanı (`database.sqlite`) oluşturmayı, `SqliteOrderRepository` sınıfı yazmayı ve verileri SQL `INSERT INTO orders ...` cümleleriyle saklamayı önerdi. Hatta migration dosyaları dahi üretti.
* **Geliştiricinin Hatayı Fark Etmesi:**  
  Proje Yöneticisi ve Geliştirici **Mustafa Güreşir**, dersin resmi Aşama 2 şartnamesini (Madde 2: *JSON ile Veri Saklama ve Repository Yapısı*) incelemiştir. Şartnamede açıkça şu hüküm yer almaktaydı:  
  > *"Aşama 2’de MySQL veya başka bir veritabanı kullanmayacaksınız. Uygulamanızın kalıcı verileri storage/ gibi bir klasördeki JSON dosyalarında tutulmalıdır. Veri erişimini Repository katmanında toplamalısınız: Her Repository için bir arayüz tanımlayın. JSON kullanan Repository sınıfları bu arayüzleri uygulasın."*  
  Yapay zekânın bu kuralı göz ardı ettiği ve projeye şartnameye aykırı bir veritabanı bağımlılığı soktuğu tespit edilmiştir.
* **Uygulanan Düzeltme:**  
  Yapay zekânın sunduğu SQL ve SQLite kodları (`SqliteOrderRepository.php`) projeden tamamen kaldırılmıştır. Bunun yerine `OrderRepositoryInterface`, `UserRepositoryInterface`, `RestaurantRepositoryInterface`, `CourierRepositoryInterface` arayüzleri arkasında çalışan, verileri `storage/*.json` dosyalarında saklayan ve `flock` dosya kilidiyle atomik yazım yapan **`JsonOrderRepository`** ve kardeş sınıfları geliştirilmiştir. Bu sayede projenin şartnameye %100 uyumu korunmuş ve Aşama 3'te MySQL'e geçiş için sağlam bir soyutlama altyapısı kurulmuştur.

---

### Vaka 2 (Zorunlu Kriter: Yapay Zekânın Yanıldığı ve Geliştiricinin Düzelttiği Hata — XAMPP / WAMP Alt Dizin Yönlendirmesi)

* **Problem / Danışılan Konu:**  
  Uygulamanın XAMPP ve WAMP sunucularında Apache `mod_rewrite` ile çalıştırılması ve Router/Controller yönlendirmeleri.
* **Yapay Zekânın Hatalı Önerisi:**  
  Yapay zekâ, Front Controller yönlendirmelerinde ve HTML asset linklerinde mutlak yollar önermiştir:
  ```php
  header("Location: /login"); // Yapay zekanın önerisi
  <link rel="stylesheet" href="/css/style.css">
  ```
* **Geliştiricinin Hatayı Fark Etmesi:**  
  **Mustafa Güreşir**, projenin XAMPP'te `C:\xampp\htdocs\yemek-kurye-sistemi` klasörüne kopyalandığında adresin `http://localhost/yemek-kurye-sistemi/` olduğunu fark etmiştir. Yapay zekânın önerdiği `/login` yönlendirmesi tarayıcıyı `http://localhost/login` adresine götürmekte ve Apache 404 Not Found hatası vermektedir. Aynı şekilde CSS ve JS dosyaları da yüklenememektedir.
* **Uygulanan Düzeltme:**  
  Yapay zekânın statik kök dizin yaklaşımı reddedilmiştir. `src/Core/Request.php` sınıfına `detectBasePath()` algoritması eklenmiştir:
  ```php
  $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
  $basePath = preg_replace('#(^|/)public$#', '', $scriptDir);
  ```
  Bu algoritma sayesinde proje ister XAMPP subfolder'ında (`/yemek-kurye-sistemi`), ister WAMP'ta, ister `php -S localhost:8000 -t public` ile root'ta çalıştırılsın, tüm rotalar (`$request->url('/login')`) ve assetler (`$request->asset('css/style.css')`) dinamik olarak hatasız çözülmektedir.

---

### Vaka 3: Eşzamanlı Yazma (Race Condition) ve Geçici Dosya (.tmp) Atomikliği

* **Problem:** İki müşteri aynı anda sipariş verdiğinde `orders.json` dosyasının bozulma riski.
* **Yapay Zekânın Önerisi:**  
  ```php
  file_put_contents('orders.json', json_encode($orders, JSON_PRETTY_PRINT));
  ```
* **Geliştiricinin İncelemesi ve Kararı:**  
  Yapay zekânın bu önerisi eşzamanlı isteklerde "Race Condition" yaratmakta ve yazma esnasında elektrik veya sunucu kesintisi olursa dosyanın 0 bayt (boş) kalmasına yol açmaktadır.
* **Düzeltme:**  
  Aşama 2 şartnamesinin 2. maddesinde belirtilen yönerge uygulanmış; veri önce geçici bir dosyaya (`orders.json.tmp.xxxx`) `flock(LOCK_EX)` ile yazılmış, `fflush()` ile diske basılmış ve ardından işletim sistemi seviyesinde atomik `rename()` komutuyla asıl dosyanın üzerine güvenle geçirilmiştir (`src/Storage/JsonStorageHelper.php`).

---

### Vaka 4: Kurye Puanlama Formülü ve Mesafe Hassasiyeti (Haversine vs Öklid)

* **Problem:** Restoran ile kuryeler arasındaki mesafenin ölçülmesi ve sipariş için en uygun kuryenin seçilmesi.
* **Yapay Zekânın Önerisi:**  
  Yapay zekâ kurye seçimini sadece 2D Kartezyen Öklid mesafesine ($d = \sqrt{\Delta x^2 + \Delta y^2}$) ve en yakın kuryeye göre sıralamayı önermiştir.
* **Geliştiricinin Müdahalesi:**  
  1. İstanbul Kadıköy gerçek koordinatlarında (Enlem/Boylam) dünyanın küresel eğriliği nedeniyle düzlemsel mesafenin %30'a varan sapma ürettiği görülmüş ve Haversine formülü ($R = 6371\text{ km}$) uygulanmıştır.
  2. Yalnızca en yakın kuryeyi seçmenin o kuryeye aşırı iş yükü yığdığı ve teslimatları geciktirdiği belirlenmiş; formüle kuryenin anlık iş yükü eklenmiştir:
     $$\text{Puan} = \text{Mesafe (km)} + 2 \times \text{Aktif Sipariş Sayısı}$$
  Bu sayede daha uzaktaki fakat boşta olan kurye önceliklendirilerek lojistik denge sağlanmıştır.

---

## 💡 3. Yapay Zekâ Kullanım İlkeleri ve Değerlendirme

1. **AI Kodları Asla Doğrudan Kabul Edilmedi:** Üretilen her PHP sınıfı, arayüz ve JavaScript fonksiyonu PSR-12 standartları, şartname kısıtları ve bellek sızıntısı risklerine karşı satır satır denetlendi ve PHPStan Seviye 5 (0 hata) ile doğrulandı.
2. **Güvenlik Açıkları Manuel Olarak Kapatıldı:** Yapay zekânın ürettiği kodlarda oturum kontrolü yüzeyselken, IDOR (Insecure Direct Object References), XSS (`htmlspecialchars`), CSRF token doğrulama (`_csrf_token`) ve POST-Redirect-GET (PRG) akışı bizzat mimariye entegre edildi.
3. **Test Odaklı Doğrulama:** Yapay zekâ tarafından önerilen algoritmalar önce 31 PHPUnit birim testiyle ve mutasyon testleriyle doğrulandı; kuralları geçemeyen hiçbir kod depoya dahil edilmedi.
