# Cravelo — Proje Kuralları ve Kimliği

Bu çalışma alanındaki tüm sohbetlerde aşağıdaki kurallara kesinlikle uyulmalıdır:

- **Proje Adı:** Bu projenin adı **Cravelo**'dur. Eski/yanlış isimler olan `GustoGo` veya `TGO Express` kesinlikle kullanılmamalıdır (dokümanlar, kod yorumları, ekran metinleri, sunumlar, betikler ve çıktı başlıkları dahil).
- **Tek Geliştirici & Bireysel Sunum:** Proje **Mustafa Güreşir** (`mustafaguresir@gmail.com`, `admin_mustafa`) tarafından bireysel olarak geliştirilmekte ve sunulmaktadır. Çoklu grup üyesi yer tutucuları (`@mimar-uye`, `@vt-test-uye`, `@ui-doc-uye` vb.) eklenmemelidir.
- **Teknik Çerçeve (Aşama 2):** Saf PHP 8.2+ katmanlı MVC (framework yok), tek giriş noktası `public/index.php`, veritabanısız `storage/*.json` depolama (`flock(LOCK_EX)` + `.tmp` atomik `rename()`), sabit Kadıköy koordinatlarıyla sunucu tarafı Haversine (`R = 6371 km`) + iş yükü puanlaması (`Mesafe + 2 * AktifSipariş`).
- **Geriye Dönük Test Uyumluluğu:** İstemci tarafı JS test sözleşmelerinin beklediği dahili global takma adlar (`window.CraveloState` / `window.GustoState`, `window.CraveloMap` / `window.GustoMap`) testler güncellenmeden kırılmamalıdır; kullanıcıya görünen tüm arayüz ve metinler %100 **Cravelo** olmalıdır.
