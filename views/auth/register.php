<div class="auth-split">
    <!-- Left Cravelo Brand Showcase Panel -->
    <aside class="auth-brand-panel">
        <div class="auth-brand-top">
            <a href="<?= $request->url('/') ?>" class="auth-brand-lockup">
                <span class="auth-brand-mark"><i class="fa-solid fa-burger"></i></span>
                <span>Crave<em style="color: var(--crv-primary); font-style: normal;">lo</em></span>
            </a>
            <span class="auth-live-pill">
                <span class="auth-live-dot"></span> Anında Aktivasyon
            </span>
        </div>

        <div class="auth-brand-copy">
            <h1>Dakikalar içinde katıl, <span>ilk siparişinde</span> avantajı yakala.</h1>
            <p>
                Cravelo müşteri hesabınızla Kadıköy'ün seçkin mutfaklarına erişin, Gel-Al'da %20 indirim kazanın ve her siparişte Cravelo Puan biriktirin.
            </p>
            <div class="auth-stat-cards">
                <div class="auth-stat-tile">
                    <div class="auth-stat-val accent">0 ₺</div>
                    <div class="auth-stat-lbl">Gel-Al Teslimat</div>
                </div>
                <div class="auth-stat-tile">
                    <div class="auth-stat-val">120+</div>
                    <div class="auth-stat-lbl">Partner Mutfak</div>
                </div>
                <div class="auth-stat-tile">
                    <div class="auth-stat-val">Bcrypt</div>
                    <div class="auth-stat-lbl">Güvenli Şifreleme</div>
                </div>
            </div>
        </div>

        <div class="auth-eco-list">
            <div class="auth-eco-item">
                <span class="auth-eco-ico" style="background: rgba(16,185,129,0.15); color: #10B981;"><i class="fa-solid fa-percent"></i></span>
                <span>Gel-Al %20 İndirim</span>
            </div>
            <div class="auth-eco-item">
                <span class="auth-eco-ico" style="background: rgba(255,107,53,0.15); color: #FF6B35;"><i class="fa-solid fa-wallet"></i></span>
                <span>Cravelo Cüzdan</span>
            </div>
            <div class="auth-eco-item">
                <span class="auth-eco-ico" style="background: rgba(6,182,212,0.15); color: #06B6D4;"><i class="fa-solid fa-map-location-dot"></i></span>
                <span>Canlı Harita Takibi</span>
            </div>
            <div class="auth-eco-item">
                <span class="auth-eco-ico" style="background: rgba(245,158,11,0.15); color: #F59E0B;"><i class="fa-solid fa-users"></i></span>
                <span>Ortak Grup Sepeti</span>
            </div>
        </div>
    </aside>

    <!-- Right Interactive Registration Form Panel -->
    <section class="auth-form-panel">
        <div class="auth-form-head">
            <h2>Yeni Müşteri Hesabı</h2>
            <p>Bilgilerinizi girin, saniyeler içinde Cravelo deneyimine başlayın.</p>
        </div>

        <!-- Segmented Tab Switcher -->
        <div class="auth-tabs" role="tablist">
            <a href="<?= $request->url('/login') ?>" class="auth-tab-link">
                <i class="fa-solid fa-right-to-bracket"></i> Giriş Yap
            </a>
            <a href="<?= $request->url('/register') ?>" class="auth-tab-link active is-register" aria-current="page">
                <i class="fa-solid fa-user-plus"></i> Yeni Kayıt Ol
            </a>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show py-2 mb-3" role="alert">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
        <?php endif; ?>

        <div class="crv-notice-banner">
            <i class="fa-solid fa-shield-halved"></i>
            <span><strong>Güvenlik İlkesi (FR-15):</strong> Dış kayıt ile yalnızca <strong>Müşteri</strong> hesabı oluşturulur; parolalar Bcrypt ile korunur.</span>
        </div>

        <form action="<?= $request->url('/register') ?>" method="POST" autocomplete="off">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-3">
                <label class="form-label" for="reg-full-name">Ad Soyad</label>
                <div class="crv-input-wrap">
                    <i class="fa-solid fa-id-card crv-input-icon"></i>
                    <input type="text" name="full_name" id="reg-full-name" class="form-control" value="<?= htmlspecialchars($oldFullName ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Örn: Zeynep Demir" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="reg-username-input">Kullanıcı Adı <span style="color: var(--crv-text-3); font-weight: 500;">(En az 3 karakter)</span></label>
                <div class="crv-input-wrap">
                    <i class="fa-solid fa-at crv-input-icon"></i>
                    <input type="text" name="username" id="reg-username-input" class="form-control" value="<?= htmlspecialchars($oldUsername ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Örn: zeynep_demir" minlength="3" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="reg-phone-input">Telefon Numarası</label>
                <div class="crv-input-wrap">
                    <i class="fa-solid fa-phone crv-input-icon"></i>
                    <input type="tel" name="phone" id="reg-phone-input" class="form-control" value="<?= htmlspecialchars($oldPhone ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="0555 123 45 67">
                </div>
            </div>

            <div class="crv-form-row mb-4">
                <div>
                    <label class="form-label" for="reg-pass-input">Parola <span style="color: var(--crv-text-3); font-weight: 500;">(Min 6)</span></label>
                    <div class="crv-input-wrap has-toggle">
                        <i class="fa-solid fa-lock crv-input-icon"></i>
                        <input type="password" name="password" id="reg-pass-input" class="form-control" placeholder="••••••••" minlength="6" required>
                        <button type="button" class="crv-pwd-toggle" onclick="togglePwdVisibility('reg-pass-input', this)" aria-label="Parolayı Göster">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="reg-pass-confirm-input">Parola Tekrarı</label>
                    <div class="crv-input-wrap has-toggle">
                        <i class="fa-solid fa-check-double crv-input-icon"></i>
                        <input type="password" name="password_confirm" id="reg-pass-confirm-input" class="form-control" placeholder="••••••••" minlength="6" required>
                        <button type="button" class="crv-pwd-toggle" onclick="togglePwdVisibility('reg-pass-confirm-input', this)" aria-label="Parola Tekrarını Göster">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-brand btn-brand-emerald w-100">
                <i class="fa-solid fa-user-plus"></i> Hesabımı Oluştur
            </button>
        </form>

        <div class="auth-footer-switch">
            Zaten bir hesabınız var mı? <a href="<?= $request->url('/login') ?>">Giriş Yap</a>
        </div>
    </section>
</div>
