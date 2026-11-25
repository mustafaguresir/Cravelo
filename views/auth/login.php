<div class="auth-split">
    <!-- Left Cravelo Brand Showcase Panel -->
    <aside class="auth-brand-panel">
        <div class="auth-brand-top">
            <a href="<?= $request->url('/') ?>" class="auth-brand-lockup">
                <span class="auth-brand-mark"><i class="fa-solid fa-burger"></i></span>
                <span>Crave<em style="color: var(--crv-primary); font-style: normal;">lo</em></span>
            </a>
            <span class="auth-live-pill">
                <span class="auth-live-dot"></span> Kadıköy Canlı
            </span>
        </div>

        <div class="auth-brand-copy">
            <h1>Sıcak lezzetler, <span>akıllı kurye</span> rotasıyla kapında.</h1>
            <p>
                Tek hesapla sipariş verin, canlı haritada kuryenizi sokak sokak izleyin veya KDS, Kurye ve Yönetici kokpitlerine anında bağlanın.
            </p>
            <div class="auth-stat-cards">
                <div class="auth-stat-tile">
                    <div class="auth-stat-val accent">~22 Dk</div>
                    <div class="auth-stat-lbl">Ort. Teslimat</div>
                </div>
                <div class="auth-stat-tile">
                    <div class="auth-stat-val">%20</div>
                    <div class="auth-stat-lbl">Gel-Al İndirimi</div>
                </div>
                <div class="auth-stat-tile">
                    <div class="auth-stat-val">4.9 ★</div>
                    <div class="auth-stat-lbl">25K+ Sipariş</div>
                </div>
            </div>
        </div>

        <div class="auth-eco-list">
            <div class="auth-eco-item">
                <span class="auth-eco-ico" style="background: rgba(16,185,129,0.15); color: #10B981;"><i class="fa-solid fa-bag-shopping"></i></span>
                <span>Müşteri Portalı</span>
            </div>
            <div class="auth-eco-item">
                <span class="auth-eco-ico" style="background: rgba(245,158,11,0.15); color: #F59E0B;"><i class="fa-solid fa-utensils"></i></span>
                <span>Restoran KDS</span>
            </div>
            <div class="auth-eco-item">
                <span class="auth-eco-ico" style="background: rgba(6,182,212,0.15); color: #06B6D4;"><i class="fa-solid fa-motorcycle"></i></span>
                <span>Kurye Kokpiti</span>
            </div>
            <div class="auth-eco-item">
                <span class="auth-eco-ico" style="background: rgba(239,68,68,0.15); color: #EF4444;"><i class="fa-solid fa-shield-halved"></i></span>
                <span>Yönetici Merkezi</span>
            </div>
        </div>
    </aside>

    <!-- Right Interactive Login Form Panel -->
    <section class="auth-form-panel">
        <div class="auth-form-head">
            <h2>Cravelo'ya Hoş Geldiniz</h2>
            <p>Hesabınıza giriş yapın veya tek tıkla bir demo rolü seçin.</p>
        </div>

        <!-- Segmented Tab Switcher -->
        <div class="auth-tabs" role="tablist">
            <a href="<?= $request->url('/login') ?>" class="auth-tab-link active" aria-current="page">
                <i class="fa-solid fa-right-to-bracket"></i> Giriş Yap
            </a>
            <a href="<?= $request->url('/register') ?>" class="auth-tab-link">
                <i class="fa-solid fa-user-plus"></i> Yeni Kayıt Ol
            </a>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show py-2 mb-3" role="alert">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
        <?php endif; ?>

        <div id="demo-fill-hint" class="alert alert-info py-2 px-3 small d-none mb-3" role="status"></div>

        <form action="<?= $request->url('/login') ?>" method="POST" autocomplete="off">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-3">
                <label class="form-label" for="input-username">Kullanıcı Adı veya E-Posta</label>
                <div class="crv-input-wrap">
                    <i class="fa-solid fa-user crv-input-icon"></i>
                    <input type="text" name="username" id="input-username" class="form-control" value="<?= htmlspecialchars($oldUsername ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Örn: admin_mustafa veya ahmet_musteri" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" for="input-password">Parola</label>
                <div class="crv-input-wrap has-toggle">
                    <i class="fa-solid fa-lock crv-input-icon"></i>
                    <input type="password" name="password" id="input-password" class="form-control" placeholder="Parolanız (Demo: hash123)" required>
                    <button type="button" class="crv-pwd-toggle" onclick="togglePwdVisibility('input-password', this)" aria-label="Parolayı Göster veya Gizle">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" id="btn-login-submit" class="btn btn-brand w-100">
                <i class="fa-solid fa-right-to-bracket"></i> Giriş Yap
            </button>
        </form>

        <!-- Tek Tıkla Demo Giriş Kartları (Tüm 10 Kullanıcı) -->
        <div class="auth-demo-box">
            <div class="auth-demo-head">
                <span class="auth-demo-title">
                    <i class="fa-solid fa-bolt" style="color: var(--crv-primary);"></i> Tek Tıkla Demo Girişi (10 Kullanıcı)
                </span>
                <span class="auth-demo-pass">Şifre: hash123</span>
            </div>

            <div class="demo-group-label">Sistem Yöneticisi</div>
            <button type="button" class="demo-pill" data-demo-user="admin_mustafa" onclick="fillLogin('admin_mustafa', 'Mustafa Güreşir (Yönetici)', this)">
                <span>👑 Mustafa Güreşir</span>
                <span class="demo-pill-role is-admin">yönetici</span>
            </button>

            <div class="demo-group-label">Müşteriler</div>
            <div class="demo-grid">
                <button type="button" class="demo-pill" data-demo-user="ahmet_musteri" onclick="fillLogin('ahmet_musteri', 'Ahmet Yılmaz', this)">
                    <span>🛍️ Ahmet Y.</span>
                    <span class="demo-pill-role is-customer">müşteri</span>
                </button>
                <button type="button" class="demo-pill" data-demo-user="ayse_musteri" onclick="fillLogin('ayse_musteri', 'Ayşe Kaya', this)">
                    <span>🛍️ Ayşe K.</span>
                    <span class="demo-pill-role is-customer">müşteri</span>
                </button>
                <button type="button" class="demo-pill" data-demo-user="mehmet_musteri" onclick="fillLogin('mehmet_musteri', 'Mehmet Demir', this)" style="grid-column: span 2;">
                    <span>🛍️ Mehmet Demir</span>
                    <span class="demo-pill-role is-customer">müşteri</span>
                </button>
            </div>

            <div class="demo-group-label">Kuryeler & Restoranlar</div>
            <div class="demo-grid">
                <button type="button" class="demo-pill" data-demo-user="kurye_ali" onclick="fillLogin('kurye_ali', 'Ali Kaya (Kurye)', this)">
                    <span>🛵 Ali Kaya</span>
                    <span class="demo-pill-role is-courier">kurye</span>
                </button>
                <button type="button" class="demo-pill" data-demo-user="kurye_veli" onclick="fillLogin('kurye_veli', 'Veli Demir (Kurye)', this)">
                    <span>🛵 Veli Demir</span>
                    <span class="demo-pill-role is-courier">kurye</span>
                </button>
                <button type="button" class="demo-pill" data-demo-user="kurye_can" onclick="fillLogin('kurye_can', 'Can Öz (E-Bisiklet)', this)">
                    <span>🚲 Can Öz</span>
                    <span class="demo-pill-role is-courier">kurye</span>
                </button>
                <button type="button" class="demo-pill" data-demo-user="restoran_lezzet" onclick="fillLogin('restoran_lezzet', 'Lezzet Kebap (KDS)', this)">
                    <span>🔥 Lezzet Kebap</span>
                    <span class="demo-pill-role is-restaurant">mutfak</span>
                </button>
                <button type="button" class="demo-pill" data-demo-user="restoran_burger" onclick="fillLogin('restoran_burger', 'Burger Lab (KDS)', this)">
                    <span>🍔 Burger Lab</span>
                    <span class="demo-pill-role is-restaurant">mutfak</span>
                </button>
                <button type="button" class="demo-pill" data-demo-user="restoran_pizza" onclick="fillLogin('restoran_pizza', 'Pizza Roma (KDS)', this)">
                    <span>🍕 Pizza Roma</span>
                    <span class="demo-pill-role is-restaurant">mutfak</span>
                </button>
            </div>
        </div>

        <div class="auth-footer-switch">
            Hesabınız yok mu? <a href="<?= $request->url('/register') ?>">Yeni Müşteri Kaydı Aç</a>
        </div>
    </section>
</div>

<script>
function fillLogin(username, label, btnEl) {
    var uEl = document.getElementById('input-username');
    var pEl = document.getElementById('input-password');
    if (uEl) uEl.value = username;
    if (pEl) pEl.value = 'hash123';

    var pills = document.querySelectorAll('.demo-pill');
    for (var i = 0; i < pills.length; i++) {
        pills[i].classList.remove('is-selected');
    }
    if (btnEl) btnEl.classList.add('is-selected');

    var hint = document.getElementById('demo-fill-hint');
    if (hint) {
        hint.classList.remove('d-none');
        hint.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> <strong>' + (label || username) + '</strong> bilgileri dolduruldu. Devam etmek için <strong>Giriş Yap</strong> butonuna tıklayın.';
    }
    var submitBtn = document.getElementById('btn-login-submit');
    if (submitBtn) submitBtn.focus();
}

(function () {
    try {
        var params = new URLSearchParams(window.location.search);
        var demo = params.get('demo');
        if (demo) {
            var target = document.querySelector('.demo-pill[data-demo-user="' + demo.replace(/"/g, '') + '"]');
            fillLogin(demo, target ? target.textContent.trim() : demo, target);
        }
    } catch (e) {}
})();
</script>
