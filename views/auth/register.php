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

            <?php $selectedCountry = (string)($oldCountryCode ?? '+90'); ?>
            <div class="mb-3">
                <label class="form-label" for="reg-phone-input">Telefon Numarası <span style="color: var(--crv-text-3); font-weight: 500;">(Ülke koduna göre)</span></label>
                <div class="crv-phone-group">
                    <select name="country_code" id="reg-country-code" class="crv-country-select" aria-label="Ülke Telefon Kodu">
                        <option value="+90"  <?= $selectedCountry === '+90'  ? 'selected' : '' ?>>🇹🇷 +90</option>
                        <option value="+49"  <?= $selectedCountry === '+49'  ? 'selected' : '' ?>>🇩🇪 +49</option>
                        <option value="+44"  <?= $selectedCountry === '+44'  ? 'selected' : '' ?>>🇬🇧 +44</option>
                        <option value="+1"   <?= $selectedCountry === '+1'   ? 'selected' : '' ?>>🇺🇸 +1</option>
                        <option value="+994" <?= $selectedCountry === '+994' ? 'selected' : '' ?>>🇦🇿 +994</option>
                        <option value="+33"  <?= $selectedCountry === '+33'  ? 'selected' : '' ?>>🇫🇷 +33</option>
                        <option value="+31"  <?= $selectedCountry === '+31'  ? 'selected' : '' ?>>🇳🇱 +31</option>
                        <option value="+39"  <?= $selectedCountry === '+39'  ? 'selected' : '' ?>>🇮🇹 +39</option>
                        <option value="+34"  <?= $selectedCountry === '+34'  ? 'selected' : '' ?>>🇪🇸 +34</option>
                        <option value="+971" <?= $selectedCountry === '+971' ? 'selected' : '' ?>>🇦🇪 +971</option>
                        <option value="+966" <?= $selectedCountry === '+966' ? 'selected' : '' ?>>🇸🇦 +966</option>
                        <option value="+7"   <?= $selectedCountry === '+7'   ? 'selected' : '' ?>>🇷🇺 +7</option>
                    </select>
                    <div class="crv-input-wrap">
                        <i class="fa-solid fa-phone crv-input-icon"></i>
                        <input type="tel" name="phone" id="reg-phone-input" class="form-control" inputmode="numeric" autocomplete="tel-national" maxlength="13" value="<?= htmlspecialchars($oldPhone ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="555 123 45 67" required>
                    </div>
                </div>
                <div class="crv-phone-meta">
                    <span id="reg-phone-hint">Türkiye (+90): Başında 0 olmadan 10 hane</span>
                    <span id="reg-phone-counter" class="crv-phone-counter">0 / 10</span>
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

<script>
(function () {
    var COUNTRY_RULES = {
        '+90':  { name: 'Türkiye', min: 10, max: 10, stripZero: true, placeholder: '555 123 45 67', groups: [3, 3, 2, 2], hint: 'Türkiye (+90): Başında 0 olmadan 10 hane' },
        '+49':  { name: 'Almanya', min: 10, max: 11, stripZero: true, placeholder: '151 2345 6789', groups: [3, 4, 4], hint: 'Almanya (+49): 10-11 hane' },
        '+44':  { name: 'Birleşik Krallık', min: 10, max: 10, stripZero: true, placeholder: '7700 900123', groups: [4, 6], hint: 'Birleşik Krallık (+44): 10 hane' },
        '+1':   { name: 'ABD / Kanada', min: 10, max: 10, stripZero: false, placeholder: '202 555 0143', groups: [3, 3, 4], hint: 'ABD / Kanada (+1): 10 hane' },
        '+994': { name: 'Azerbaycan', min: 9, max: 9, stripZero: true, placeholder: '50 123 45 67', groups: [2, 3, 2, 2], hint: 'Azerbaycan (+994): 9 hane' },
        '+33':  { name: 'Fransa', min: 9, max: 9, stripZero: true, placeholder: '6 12 34 56 78', groups: [1, 2, 2, 2, 2], hint: 'Fransa (+33): 9 hane' },
        '+31':  { name: 'Hollanda', min: 9, max: 9, stripZero: true, placeholder: '6 1234 5678', groups: [1, 4, 4], hint: 'Hollanda (+31): 9 hane' },
        '+39':  { name: 'İtalya', min: 9, max: 10, stripZero: false, placeholder: '312 345 6789', groups: [3, 3, 4], hint: 'İtalya (+39): 9-10 hane' },
        '+34':  { name: 'İspanya', min: 9, max: 9, stripZero: false, placeholder: '612 34 56 78', groups: [3, 2, 2, 2], hint: 'İspanya (+34): 9 hane' },
        '+971': { name: 'BAE', min: 9, max: 9, stripZero: true, placeholder: '50 123 4567', groups: [2, 3, 4], hint: 'BAE (+971): 9 hane' },
        '+966': { name: 'Suudi Arabistan', min: 9, max: 9, stripZero: true, placeholder: '50 123 4567', groups: [2, 3, 4], hint: 'Suudi Arabistan (+966): 9 hane' },
        '+7':   { name: 'Rusya', min: 10, max: 10, stripZero: false, placeholder: '912 345 67 89', groups: [3, 3, 2, 2], hint: 'Rusya (+7): 10 hane' }
    };

    var selectEl = document.getElementById('reg-country-code');
    var inputEl = document.getElementById('reg-phone-input');
    var hintEl = document.getElementById('reg-phone-hint');
    var counterEl = document.getElementById('reg-phone-counter');
    if (!selectEl || !inputEl) return;

    function formatByGroups(digits, groups) {
        var parts = [];
        var pos = 0;
        for (var i = 0; i < groups.length && pos < digits.length; i++) {
            parts.push(digits.slice(pos, pos + groups[i]));
            pos += groups[i];
        }
        if (pos < digits.length) {
            parts.push(digits.slice(pos));
        }
        return parts.join(' ');
    }

    function syncPhoneInput(showValidity) {
        var code = selectEl.value || '+90';
        var rule = COUNTRY_RULES[code] || COUNTRY_RULES['+90'];
        var codeDigits = code.replace('+', '');
        var digits = (inputEl.value || '').replace(/\D/g, '');

        if (digits.indexOf(codeDigits) === 0 && digits.length > rule.max) {
            digits = digits.slice(codeDigits.length);
        }
        if (rule.stripZero && digits.charAt(0) === '0') {
            digits = digits.replace(/^0+/, '');
        }
        if (digits.length > rule.max) {
            digits = digits.slice(0, rule.max);
        }

        var formatted = formatByGroups(digits, rule.groups);
        inputEl.value = formatted;
        inputEl.placeholder = rule.placeholder;
        inputEl.maxLength = rule.max + (rule.groups.length - 1);

        var isValid = digits.length >= rule.min && digits.length <= rule.max;
        if (hintEl) hintEl.textContent = rule.hint;
        if (counterEl) {
            counterEl.textContent = digits.length + ' / ' + rule.max + (isValid ? ' ✓' : '');
            counterEl.classList.toggle('is-valid', isValid);
        }

        if (!isValid && (showValidity || digits.length > 0)) {
            var rangeText = (rule.min === rule.max) ? String(rule.max) : (rule.min + '-' + rule.max);
            inputEl.setCustomValidity(rule.name + ' (' + code + ') için telefon numarası ' + rangeText + ' haneli olmalıdır.');
        } else {
            inputEl.setCustomValidity('');
        }
    }

    inputEl.addEventListener('input', function () { syncPhoneInput(false); });
    selectEl.addEventListener('change', function () {
        syncPhoneInput(false);
        inputEl.focus();
    });

    var formEl = inputEl.closest('form');
    if (formEl) {
        formEl.addEventListener('submit', function (ev) {
            syncPhoneInput(true);
            if (!inputEl.checkValidity()) {
                ev.preventDefault();
                inputEl.reportValidity();
            }
        });
    }

    syncPhoneInput(false);
})();
</script>
