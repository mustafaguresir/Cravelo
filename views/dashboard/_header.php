<!-- Unified Cravelo Frosted Glass Header Navigation -->
<header class="topnav">
    <a class="brand" onclick="handleBrandClick()" title="Ana Görünüme Git" aria-label="Cravelo Ana Sayfa">
        <div class="brand-logo"><i class="fa-solid fa-burger"></i></div>
        <span>Crave<em>lo</em></span>
    </a>

    <!-- Role-Specific Center Telemetry Banners (Admin / Courier / Restaurant) -->
    <div class="role-center-banner admin-center-banner" id="banner-admin" style="display:none;">
        <span class="status-pill danger"><i class="fa-solid fa-shield-halved"></i> YÖNETİCİ</span>
        <strong>Cravelo Komuta & Denetim Merkezi</strong>
    </div>
    <div class="role-center-banner courier-center-banner" id="banner-courier" style="display:none;">
        <span class="status-pill info"><i class="fa-solid fa-motorcycle"></i> KURYE</span>
        <strong>Kurye Teslimat & Canlı Kokpit</strong>
    </div>
    <div class="role-center-banner restaurant-center-banner" id="banner-restaurant" style="display:none;">
        <span class="status-pill warning"><i class="fa-solid fa-utensils"></i> RESTORAN</span>
        <strong>Mutfak Ekranı (KDS) & Menü Masası</strong>
    </div>

    <!-- Müşteriye Özel Adres Seçici (Customer Only) -->
    <button type="button" class="nav-address customer-only-ui" onclick="openAddressModal()" id="btn-nav-address" title="Teslimat Adresini Değiştir">
        <i class="fa-solid fa-location-dot pin"></i>
        <span id="nav-addr-label">Kadıköy Moda, İstanbul</span>
        <i class="fa-solid fa-chevron-down" style="font-size:10.5px;color:var(--text-3);margin-left:auto;"></i>
    </button>

    <!-- Müşteriye Özel Teslimat Türü Segmented Switch (Customer Only) -->
    <div class="del-mode-toggle customer-only-ui" id="del-mode-toggle" role="group" aria-label="Teslimat Yöntemi">
        <button type="button" class="del-mode-btn active" id="btn-del-courier" onclick="switchDeliveryMode('courier')" title="Kurye ile Adrese Teslim">
            <i class="fa-solid fa-motorcycle"></i> Adrese Teslim
        </button>
        <button type="button" class="del-mode-btn" id="btn-del-takeaway" onclick="switchDeliveryMode('takeaway')" title="Restorandan Gel-Al: 0 ₺ Teslimat + %20 Ek İndirim">
            <i class="fa-solid fa-person-walking"></i> Gel-Al <span class="badge-discount-tag">%20 İndirim</span>
        </button>
    </div>

    <!-- Müşteriye Özel Expanding Omnibox Arama Çubuğu (Customer Only) -->
    <div class="nav-search customer-only-ui" id="global-search-box">
        <input type="text" id="global-search" placeholder="Restoran veya lezzetli bir yemek arayın…" oninput="globalSearch()" autocomplete="off">
        <i class="fa-solid fa-magnifying-glass"></i>
    </div>

    <div class="nav-actions">
        <!-- Müşteriye Özel Sepetim Butonu (Customer Only) -->
        <button type="button" class="nav-cart-btn customer-only-ui" onclick="openCartModal()" id="btn-nav-cart" title="Sepetimi Görüntüle">
            <i class="fa-solid fa-bag-shopping"></i>
            <span class="nav-cart-text">Sepetim</span>
            <span class="cart-badge" id="nav-cart-count">0</span>
        </button>

        <!-- Müşteriye Özel Siparişlerim Butonu (Customer Only) -->
        <button type="button" class="nav-orders-btn customer-only-ui" onclick="openOrdersTab()" id="btn-nav-orders" title="Siparişlerimi Görüntüle ve Kuryemi Takip Et">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span class="nav-orders-text">Siparişlerim</span>
            <span class="cart-badge" id="nav-orders-badge" style="display:none;">0</span>
        </button>

        <!-- Theme Toggle (Light / Dark) -->
        <button type="button" class="action-icon-btn" onclick="toggleTheme()" title="Açık / Koyu Tema" aria-label="Temayı Değiştir">
            <i class="fa-solid fa-sun" id="theme-ico"></i>
        </button>

        <!-- Unauthenticated Auth Actions (Giriş Yap & Kayıt Ol) -->
        <div id="nav-auth-actions" style="display: <?= (!empty($currentUser) || \App\Core\Session::has('user')) ? 'none' : 'flex' ?>; align-items: center; gap: 8px;">
            <a href="<?= $request->url('/login') ?>" class="nav-auth-btn nav-auth-login" id="header-btn-login">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span>Giriş Yap</span>
            </a>
            <a href="<?= $request->url('/register') ?>" class="nav-auth-btn nav-auth-register" id="header-btn-register">
                <i class="fa-solid fa-user-plus"></i>
                <span>Kayıt Ol</span>
            </a>
        </div>

        <!-- Authenticated User Actions -->
        <div id="nav-user-actions" style="display: <?= (!empty($currentUser) || \App\Core\Session::has('user')) ? 'flex' : 'none' ?>; align-items: center; gap: 10px;">
            <!-- User Profile Pill -->
            <div class="user-pill" onclick="openAccountModal()" role="button" tabindex="0" title="Profil & Cravelo Cüzdan">
                <div class="user-avatar" id="nav-avatar"><?= htmlspecialchars(substr($user['fullName'] ?? $currentUser['fullName'] ?? 'A', 0, 1), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="user-meta">
                    <div class="user-name" id="nav-name"><?= htmlspecialchars($user['fullName'] ?? $currentUser['fullName'] ?? 'Kullanıcı', ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="user-role-badge" id="nav-role"><i class="fa-solid fa-coins"></i> <?= htmlspecialchars(strtoupper($user['role'] ?? $currentUser['role'] ?? 'Giriş Yapıldı'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <!-- Safe Logout Button -->
            <button type="button" onclick="logoutUser(event)" class="nav-logout-btn" title="Güvenli Çıkış Yap">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Çıkış Yap</span>
            </button>
        </div>

        <script>
            (function() {
                try {
                    var s = JSON.parse(localStorage.getItem('cravelo_session') || localStorage.getItem('kuryo_session') || localStorage.getItem('tgo_session') || localStorage.getItem('gustogo_session') || '{}');
                    if (s && s.fullName) {
                        var el = document.getElementById('nav-name');
                        if (el) el.innerText = s.fullName;
                        var rEl = document.getElementById('nav-role');
                        if (rEl && s.role === 'admin') rEl.innerText = 'SİSTEM YÖNETİCİSİ';
                    }
                } catch(e) {}
            })();
        </script>
    </div>
</header>

<!-- Floating Bottom Cart Bar (Theme-Adaptive Frosted Glass Pill) -->
<div class="floating-cart-bar" id="floating-cart-bar" onclick="openCartModal()" role="button" tabindex="0" title="Sepeti İncele">
    <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg, var(--primary), var(--primary-2));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:15px;box-shadow:0 4px 14px var(--primary-glow);flex-shrink:0;" id="float-cart-count">1</div>
        <div>
            <div style="font-size:13.5px;font-weight:800;color:var(--text);letter-spacing:-0.2px;" id="float-cart-text">Sepetinizde 1 lezzet var</div>
            <div style="font-size:15px;color:var(--primary);font-weight:900;" id="float-cart-total">250.00 ₺</div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
        <button type="button" class="btn btn-outline btn-sm" onclick="event.stopPropagation(); openCartModal();" style="border-radius:9999px;padding:9px 18px;font-size:13px;">
            <i class="fa-solid fa-basket-shopping"></i> Sepetim
        </button>
        <button type="button" class="btn btn-primary btn-sm" onclick="event.stopPropagation(); openCheckoutModal();" style="border-radius:9999px;padding:9px 22px;font-size:13px;">
            <i class="fa-solid fa-credit-card"></i> Öde
        </button>
    </div>
</div>

<main class="main-container" id="main-container">
    <!-- Yönetici Canlı Önizleme Geri Dönüş Barı -->
    <div id="admin-return-bar" class="admin-return-bar" style="display:none;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;border-radius:12px;background:rgba(255,255,255,0.16);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <span style="font-size:13.5px;line-height:1.4;">
                <strong>Yönetici Canlı Önizleme Modu:</strong> Şu anda başka bir rolün Cravelo arayüzünü inceliyorsunuz.
            </span>
        </div>
        <button type="button" class="btn btn-sm" onclick="adminPreviewView('admin')" style="background:#FFFFFF;color:#0F172A;border-radius:9999px;padding:8px 18px;font-weight:800;box-shadow:0 4px 12px rgba(0,0,0,0.15);white-space:nowrap;">
            <i class="fa-solid fa-arrow-left"></i> Yönetici Merkezine Dön
        </button>
    </div>
