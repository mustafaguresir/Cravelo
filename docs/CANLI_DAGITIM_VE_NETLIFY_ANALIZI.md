# CANLI DAĞITIM VE NETLIFY TEKNİK ANALİZİ RAPORU

**Proje**: PHP 8.2 MVC Yemek Sipariş ve Kurye Takip Platformu  
**Yetkili Yönetici**: Mustafa Güreşir  
**Tarih**: 2026-11-20  
**Rapor Türü**: R4 Gereksinimi — Sunucu Mimarisi, Dağıtım Seçenekleri ve Kısıt Analizi  

---

## 1. YÖNETİCİ ÖZETİ (EXECUTIVE SUMMARY)

Bu teknik rapor; **Cravelo — Yemek Sipariş ve Kurye Takip Sistemi** projesinin popüler Jamstack ve sunucusuz (serverless) barındırma platformu olan **Netlify üzerinde doğrudan çalıştırılıp çalıştırılamayacağını** derinlemesine analiz etmekte ve canlı ortamda (production) sıfır veri kaybı, kesintisiz oturum yönetimi ve yüksek eşzamanlılık performansı sağlayacak alternatif dağıtım modellerini sunmaktadır.

### 🔴 Kesin Teknik Hüküm:
Mevcut Saf PHP 8.2 MVC mimarisi, `storage/*.json` dosya tabanlı veritabanı, işletim sistemi çekirdek kilit mekanizması (`flock`) ve PHP yerel oturum yapısı gereği projenin **Netlify üzerinde doğrudan çalıştırılması KESİNLİKLE MÜMKÜN DEĞİLDİR ve ÖNERİLMEZ**.

Projenin mevcut mimarisini bozmadan canlıya alınabilmesi için **Kalıcı Disk (Persistent Storage)** ve **PHP 8.2 Runtime** desteği sunan PaaS (Render / Railway) veya Cloud VPS (Ubuntu + Nginx + PHP-FPM) çözümleri zorunludur.

---

## 2. NETLIFY JAMSTACK MİMARİSİ VE KÖK NEDEN ANALİZİ (ROOT-CAUSE DEEP DIVE)

Netlify; modern web için geliştirilmiş, **Jamstack (JavaScript, APIs, Pre-rendered Markup)** paradigmasına dayalı statik CDN ve sunucusuz (serverless) mikro fonksiyon barındırma platformudur. Bu mimarinin projemizle uyuşmamasının 5 temel teknik kök nedeni bulunmaktadır:

### 2.1. Yerel PHP 8.2 Runtime Bulunmayışı
- Netlify altyapısında Apache, Nginx veya PHP-FPM web sunucu süreçleri bulunmaz.
- Netlify derleme ortamı (Build Image) Node.js, Python, Ruby ve Go derleyicileri içerse de, HTTP isteklerini PHP betiklerine yönlendirip işleyecek bir PHP FastCGI / FPM çalıştırma katmanına sahip değildir.
- PHP dosyaları doğrudan yüklendiğinde ya kaynak kodları düz metin olarak istemciye sızar ya da MIME type ve 404 hataları üretir.

### 2.2. Ephemeral & Read-Only Serverless Dosya Sistemi (Kalıcı Disk Yokluğu)
- Serverless altyapılarda (AWS Lambda / Netlify Functions) dosya sistemi **salt okunurdur (read-only)**.
- Fonksiyonların yazabildiği tek alan geçici `/tmp` dizinidir. Ancak:
  1. `/tmp` dizini yalnızca o anki mikro-container ayağa kalktığında geçici olarak tahsis edilir.
  2. Fonksiyon boşta kaldığında veya soğuk başlatma (cold start) sonrasında container imha edildiğinde **tüm `/tmp` içeriği kalıcı olarak silinir**.
  3. Yatay ölçeklemede aynı anda 10 müşteri sipariş verdiğinde, Netlify 10 ayrı mikro-container başlatır. Müşteri A'nın siparişi Container 1'in `/tmp/orders.json` dosyasına yazılırken, Restoran B Container 2'ye bağlanır ve **siparişi asla göremez**.
  4. Sonuç: **Kalıcı veri kaybı ve kullanıcılar arası veri kopukluğu.**

### 2.3. Dosya Kilitleme (`flock`) Mekanizmasının Dağıtık Ortamda Çöküşü
- Projede veri bütünlüğü `src/Storage/JsonStorageHelper.php` dosyasındaki kilit mekanizmasıyla korunur:
  ```php
  $fp = fopen($lockFilePath, 'c+');
  flock($fp, LOCK_EX); // Kernel seviyesinde dosya kilidi
  ...
  rename($tempFilePath, $filePath); // Atomik takas
  ```
- `flock()` işlevi, işletim sistemi çekirdeğinin (Linux VFS / Windows Inode) yerel dosya tablosu üzerinde çalışır.
- Netlify'da gelen her istek farklı bir izole sanal makinede (AWS Firecracker MicroVM) çalışır. MicroVM A'daki bir PHP sürecinin aldığı `flock(LOCK_EX)`, MicroVM B'deki süreç tarafından **GÖRÜLEMEZ**.
- İki kurye veya müşteri aynı anda durum güncellediğinde, her iki instance da kilit almadan aynı dosyayı ezmeye çalışır.
- `tests/test_architectural_flows.py` dosyasındaki TEST 4.5 doğrulamamızda kanıtlandığı üzere: Kilit mekanizması çöktüğünde eşzamanlı yazmada **49+ boş okuma ve JSON sözdizim hatası (`JSON_ERROR_SYNTAX`)** oluşmaktadır. Bu durum canlı ortamda sistemin anında çökmesine yol açar.

### 2.4. PHP Oturum (`$_SESSION`) ve PRG Akışının Dağılması
- Sistem, güvenlik şartnamesi gereği kimlik doğrulamayı ve POST-Redirect-GET (PRG 303) akışını `$_SESSION` üzerinde tutar.
- Varsayılan PHP session mekanizması oturum verisini yerel diskteki `/tmp/sess_<id>` dosyasına yazar.
- Kullanıcı `/login` POST isteği gönderip giriş yaptığında Container 1'de oturum açılır. PRG gereği tarayıcı `/` adresine HTTP 303 ile yönlendirildiğinde bu istek Container 2'ye düşer. Container 2'de session dosyası bulunmadığı için kullanıcı **sürekli oturumdan atılır (Login Loop)**.

### 2.5. Apache `.htaccess` ve Front Controller Yönlendirme İmkânsızlığı
- Projede kök `.htaccess` ve `public/.htaccess` dosyaları kullanılarak tüm istekler `public/index.php`ye yönlendirilir.
- Netlify Apache modüllerini (`mod_rewrite`, `mod_dir`) tanımaz. Yönlendirmeler için `_redirects` veya `netlify.toml` dosyaları gerekir; ancak bu dosyalar da gelen isteği bir PHP motoruna bağlayamaz.

---

## 3. CANLI DAĞITIM PLATFORMLARI KARŞILAŞTIRMA MATRİSİ

| Kriter / Özellik | Netlify (Neden Uygun Değil?) | Render / Railway (Önerilen PaaS) | Cloud VPS (Hetzner / DigitalOcean) | cPanel / LiteSpeed (Paylaşımlı) |
|:---|:---|:---|:---|:---|
| **PHP 8.2 Desteği** | ❌ Yok (Yalnızca JS/Go/Rust Serverless) | ✅ Var (Native Docker ile tam uyum) | ✅ Var (Ubuntu PPA PHP-FPM 8.2) | ✅ Var (cPanel MultiPHP 8.2) |
| **Kalıcı Disk (Persistent Storage)** | ❌ Yok (Geçici/Uçucu microVM) | ✅ Var (Persistent Disk / Volume) | ✅ Var (Kalıcı SSD/NVMe Blok Depolama) | ✅ Var (Standart Hosting Alanı) |
| **`flock()` & JSON Uyumu** | ❌ Çöküyor (Dağıtık kilit yok) | ✅ Kusursuz (Tek container/volume kilit) | ✅ Kusursuz (Yerel Linux Kernel VFS) | ✅ Kusursuz (LiteSpeed / Apache) |
| **PHP Sessions (`$_SESSION`)** | ❌ Bozuluyor (Her istek ayrı VM) | ✅ Kusursuz (Aynı container oturumu) | ✅ Kusursuz (Yerel session save path) | ✅ Kusursuz |
| **`.htaccess` / Rewrite** | ❌ Desteklenmiyor | ✅ Nginx / Apache container ile tam | ✅ Nginx / Apache doğrudan yapılandırma | ✅ Yerel `.htaccess` desteği |
| **Aylık Maliyet** | Ücretsiz / $19 (Çalışmaz) | $0 - $7/ay (Render Disk: $1-$7) | €3.79 - $6/ay (Hetzner CX22) | 100 - 250 TL/ay (Yerel Türk Hosting) |
| **Kurulum / Bakım Zorluğu** | İmkânsız (Mimariyi bozar) | Çok Kolay (Git Push & Docker) | Orta (Linux Sysadmin bilgisi) | Çok Kolay (cPanel Dosya Yöneticisi) |
| **Önerilen Kullanım Senaryosu**| Sadece Statik HTML (Cravelo için RED)| **Hızlı ve Modern Canlı Dağıtım** | **Yüksek Trafik & Tam Kontrol** | **Düşük Bütçeli & Hızlı Yayın** |

---

## 4. ÜRETİM SEVİYESİNDE DAĞITIM REHBERLERİ (STEP-BY-STEP)

### SEÇENEK A (TAVSİYE EDİLEN PaaS): Render veya Railway Üzerinde Docker ile Dağıtım

Bu seçenek, sunucu yönetimiyle (sysadmin/DevOps) uğraşmadan, Git deposuna `git push` yapıldığı anda otomatik olarak derlenen ve kalıcı disk üzerinde çalışan en modern yöntemdir.

#### 1. Proje Kök Dizinindeki Hazır `Dockerfile`
Proje kök dizininde yer alan `Dockerfile`, PHP 8.2 Apache resmi imajını kullanır; `mod_rewrite` + `AllowOverride All` (Front Controller `.htaccess`), Render'ın dinamik `$PORT` değişkeni, güvenli PHP üretim ayarları ve boş diskte otomatik `bin/seed.php` başlatıcısı ile doğrudan Render.com üzerinde çalışmaya hazırdır:
```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install opcache && a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
# <Directory /var/www/html/public> içinde AllowOverride All + DirectoryIndex index.php
# Başlatıcı (/usr/local/bin/docker-entrypoint.sh): $PORT bağlama + eksikse php bin/seed.php + www-data 775 izinleri
EXPOSE 80
CMD ["/usr/local/bin/docker-entrypoint.sh"]
```

#### 2. Render.com Üzerinde Adım Adım Canlıya Alma
1. Projeyi GitHub / GitLab / Bitbucket reponuza gönderin (`git push origin main`).
2. [Render.com](https://render.com) paneline giriş yapın ve **New + -> Web Service** seçeneğine tıklayın.
3. Proje reponuzu seçin.
4. Ayarları yapılandırın:
   - **Environment**: `Docker`
   - **Region**: Frankfurt (EU Central) *(Türkiye'ye en düşük gecikme süresi)*
   - **Plan**: Starter veya Free
5. **ÖNEMLİ (Kalıcı Disk - Persistent Disk Ayarı)**:
   - Sayfanın altındaki **Advanced** sekmesini açın.
   - **Add Disk** butonuna tıklayın:
     - Name: `cravelo-storage`
     - Mount Path: `/var/www/html/storage`
     - Size: `1 GB` (JSON depoları için yeterlidir).
6. **Create Web Service** butonuna basın. Render otomatik olarak Dockerfile'ı derler, SSL sertifikasını üretir ve `https://cravelo.onrender.com` şeklinde anında canlıya alır.

---

### SEÇENEK B (EN YÜKSEK PERFORMANS): Cloud VPS (Hetzner / DigitalOcean + Ubuntu 24.04 + Nginx + PHP-FPM 8.2)

Trafik arttığında maliyeti sabit tutmak ve maksimum işlemci/disk I/O performansı almak için önerilen kurumsal modeldir.

#### 1. Sunucu Hazırlığı ve PHP 8.2 Kurulumu
```bash
# 1. Paketleri güncelle
sudo apt update && sudo apt upgrade -y

# 2. Gerekli araçları ve PPA deposunu kur
sudo apt install -y software-properties-common curl git unzip nginx
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# 3. PHP 8.2 ve uzantılarını kur
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-opcache

# 4. PHP-FPM servisini başlat
sudo systemctl enable php8.2-fpm
sudo systemctl start php8.2-fpm
```

#### 2. Projeyi Klonlama ve İzinleri Tanımlama
```bash
sudo mkdir -p /var/www/cravelo
cd /var/www/cravelo
sudo git clone <REPO_URL> .

# İzinleri www-data kullanıcısına ata
sudo chown -R www-data:www-data /var/www/cravelo
sudo chmod -R 755 /var/www/cravelo
sudo chmod -R 775 /var/www/cravelo/storage
```

#### 3. Nginx Sanal Sunucu Konfigürasyonu (`/etc/nginx/sites-available/cravelo`)
```nginx
server {
    listen 80;
    server_name siparis.mustafaguresir.com;

    root /var/www/cravelo/public;
    index index.php index.html;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~* /(storage|src|views)/ {
        deny all;
        return 404;
    }

    location ~ /\. {
        deny all;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }
}
```

Aktif etme ve SSL kurma:
```bash
sudo ln -s /etc/nginx/sites-available/cravelo /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d siparis.mustafaguresir.com
```

---

### SEÇENEK C (EKONOMİK & SIFIR DEVOPS): cPanel / LiteSpeed Paylaşımlı Hosting

Eğer mevcut bir cPanel hosting hesabı varsa (Turhost, Natro, Veridyen vb.):
1. **Dosya Yükleme**: cPanel Dosya Yöneticisi ile proje dosyaları `public_html/` dizinine aktarılır.
2. **DocumentRoot Ayarı**: Etki Alanları menüsünden web kök dizini `public_html/public` olarak seçilir (veya projedeki kök `.htaccess` otomatik olarak yönlendirir).
3. **PHP 8.2 Seçimi**: cPanel MultiPHP Yöneticisi ekranından PHP sürümü **8.2** olarak ayarlanır.
4. **Klasör İzni**: `storage` klasörü izni sağ tıklanarak **775** yapılır.

---

## 5. OTOMATİK VERİ YEDEKLEME STRATEJİSİ (BACKUP SCRIPT & CRON)

JSON dosya tabanlı veri depolarında günlük yedekleme veri güvenliğinin omurgasıdır.

### Yedekleme Scripti (`backup_storage.sh`)
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/cravelo"
SOURCE_DIR="$(dirname "$0")/storage"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME="cravelo_storage_backup_${TIMESTAMP}.tar.gz"

mkdir -p "$BACKUP_DIR"
tar --exclude='*.lock' -czf "${BACKUP_DIR}/${FILENAME}" -C "$SOURCE_DIR" .
find "$BACKUP_DIR" -type f -name "cravelo_storage_backup_*.tar.gz" -mtime +7 -delete

echo "[$(date)] Yedekleme basariyla tamamlandi: ${FILENAME}"
```

### Crontab Görevi Tanımlama
Her gece saat 03:00'te otomatik çalıştırmak için (`crontab -e`):
```cron
0 3 * * * /bin/bash /var/www/cravelo/backup_storage.sh >> /var/log/cravelo_backup.log 2>&1
```

---

## 6. SONUÇ VE TAVSİYE

Netlify; statik siteler ve durumsuz (stateless) frontend uygulamaları için mükemmel bir platform olmakla birlikte, dosya tabanlı veritabanı ve yerel işletim sistemi kilidi (`flock`) kullanan bir PHP 8.2 backend'i barındırmak için mimari olarak imkânsızdır.

Proje yöneticisi Mustafa Güreşir için en pratik, maliyetsiz ve güvenli canlıya alma rotası **Render.com üzerinde Docker + Persistent Disk** ile yayına girmektir.
