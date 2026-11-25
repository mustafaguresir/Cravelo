<?php
/**
 * Cravelo — Karşılama ve Kimlik Doğrulama Kapısı (Landing / Auth Gate)
 * Şartname Gereksinimi: R1, R2, R3, R4, R5 (Burger Hero Scrollytelling Entegrasyonu)
 * <!-- Cravelo Architecture Engine -->
 */
?>
<!-- Cravelo Architecture Engine -->
<style id="landing-polish-helpers">
    .landing-brand-accent {
        color: var(--primary-2, #FF6B35);
        font-style: normal;
    }
    .landing-brand-badge {
        font-size: 10.5px;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        background: rgba(255, 107, 53, 0.14);
        border: 1px solid rgba(255, 107, 53, 0.28);
        color: var(--primary-2, #FF6B35);
        padding: 3px 9px;
        border-radius: 9999px;
        font-weight: 800;
        line-height: 1.2;
    }
    .landing-metric-top {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 14px;
        margin-bottom: 14px;
        font-size: 18px;
        background: var(--primary-light, rgba(255, 107, 53, 0.12));
        color: var(--primary-2, #FF6B35);
    }
    .landing-metric-top.is-emerald {
        background: var(--success-light, rgba(11, 191, 128, 0.14));
        color: var(--success, #0BBF80);
    }
    .landing-metric-top.is-amber {
        background: var(--yellow-light, rgba(245, 158, 11, 0.14));
        color: var(--yellow, #F59E0B);
    }
    .landing-metric-top.is-blue {
        background: var(--blue-light, rgba(47, 142, 255, 0.14));
        color: var(--blue, #2F8EFF);
    }
    .landing-role-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
    }
    .landing-role-tag {
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: 0.7px;
        text-transform: uppercase;
        color: var(--text-3, #64748B);
        background: var(--surface-2, rgba(255, 255, 255, 0.05));
        border: 1px solid var(--border, rgba(255, 255, 255, 0.08));
        padding: 4px 10px;
        border-radius: 9999px;
    }
    .landing-role-title {
        font-size: 19px;
        font-weight: 800;
        color: var(--text, #F8FAFC);
        letter-spacing: -0.35px;
        margin: 0 0 10px;
    }
    .landing-role-desc {
        font-size: 14px;
        color: var(--text-2, #94A3B8);
        line-height: 1.62;
        margin: 0 0 22px;
        flex-grow: 1;
    }
    .landing-role-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding-top: 14px;
        border-top: 1px solid var(--border, rgba(255, 255, 255, 0.07));
    }
    .landing-role-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12.5px;
        font-weight: 700;
        color: var(--text-2, #94A3B8);
        text-decoration: none;
        transition: color 0.2s ease, transform 0.2s ease;
    }
    .landing-role-card:hover .landing-role-link {
        color: var(--primary-2, #FF6B35);
        transform: translateX(2px);
    }
    .landing-demo-pass-code {
        background: var(--primary-light, rgba(255, 107, 53, 0.14));
        color: var(--primary-2, #FF6B35);
        border: 1px solid rgba(255, 107, 53, 0.3);
        padding: 3px 10px;
        border-radius: 8px;
        font-weight: 800;
        font-size: 13px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .landing-demo-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 24px;
    }
    .landing-banner-content {
        max-width: 640px;
    }
    .landing-banner-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 11.5px;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--primary-2, #FF6B35);
        letter-spacing: 0.9px;
        margin-bottom: 12px;
        background: var(--primary-light, rgba(255, 107, 53, 0.14));
        border: 1px solid rgba(255, 107, 53, 0.28);
        padding: 5px 13px;
        border-radius: 9999px;
    }
    .landing-banner-title {
        font-size: clamp(24px, 3vw, 32px);
        font-weight: 900;
        color: var(--text, #F8FAFC);
        letter-spacing: -0.6px;
        margin: 0 0 10px;
    }
    .landing-banner-desc {
        color: var(--text-2, #94A3B8);
        font-size: 15px;
        line-height: 1.65;
        margin: 0 0 18px;
    }
    .landing-perk-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .landing-perk-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 12.5px;
        font-weight: 700;
        color: var(--text, #F8FAFC);
        background: var(--surface, rgba(255, 255, 255, 0.04));
        border: 1px solid var(--border, rgba(255, 255, 255, 0.09));
        padding: 6px 13px;
        border-radius: 9999px;
    }
    .landing-perk-chip i {
        color: var(--primary-2, #FF6B35);
    }
    .landing-stars {
        display: inline-flex;
        gap: 3px;
        color: #F59E0B;
        font-size: 13.5px;
        margin-bottom: 14px;
    }
    .landing-quote {
        font-size: 14.5px;
        color: var(--text, #E2E8F0);
        line-height: 1.65;
        margin: 0 0 20px;
    }
    .landing-author {
        display: flex;
        align-items: center;
        gap: 11px;
    }
    .landing-author-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FF6B35, #FA4A0C);
        color: #FFFFFF;
        font-size: 13px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .landing-author-name {
        font-size: 13.5px;
        font-weight: 800;
        color: var(--text, #F8FAFC);
    }
    .landing-author-loc {
        font-size: 12px;
        color: var(--text-3, #64748B);
    }
    .site-footer-brand {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        text-decoration: none;
        color: var(--text, #F8FAFC);
        font-size: 21px;
        font-weight: 900;
        letter-spacing: -0.5px;
    }
    .site-footer-brand-icon {
        width: 36px;
        height: 36px;
        border-radius: 11px;
        background: linear-gradient(135deg, #FF6B35, #FA4A0C);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        font-size: 17px;
        box-shadow: 0 4px 14px rgba(255, 107, 53, 0.35);
    }
    .site-footer-desc {
        font-size: 13.5px;
        color: var(--text-2, #94A3B8);
        line-height: 1.65;
        max-width: 310px;
        margin: 0;
    }
    .site-footer-col-title {
        color: var(--text, #F8FAFC);
        font-size: 14px;
        font-weight: 800;
        letter-spacing: -0.2px;
        margin: 0 0 14px;
    }
    .site-footer-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
        font-size: 13.5px;
    }
    .site-footer-list a,
    .site-footer-list span {
        color: var(--text-2, #94A3B8);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: color 0.2s ease;
    }
    .site-footer-list a:hover {
        color: var(--primary-2, #FF6B35);
    }
    .site-footer-legal {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .site-footer-legal a {
        color: var(--text-3, #64748B);
        text-decoration: none;
        transition: color 0.2s ease;
    }
    .site-footer-legal a:hover {
        color: var(--text, #F8FAFC);
    }
    @media (max-width: 768px) {
        .landing-brand-badge {
            display: none;
        }
    }
</style>

<div class="landing-hero" id="landing-hero-container">
    <!-- Minimalist Frosted Glass Top Navbar -->
    <nav class="landing-navbar" id="landing-navbar">
        <a href="<?= $request->url('/') ?>" class="landing-nav-brand">
            <span class="logo-icon"><i class="fa-solid fa-burger"></i></span>
            <span>Crave<em class="landing-brand-accent">lo</em></span>
            <span class="landing-brand-badge">Jet Hızında</span>
        </a>

        <div class="landing-nav-links">
            <a href="#burger-scrolly-container" class="landing-nav-link">Menü</a>
            <a href="#campaigns" class="landing-nav-link">Kampanyalar</a>
            <a href="#courier-apply" class="landing-nav-link">Kurye Ol</a>
        </div>

        <div class="landing-nav-actions" id="landing-nav-actions">
            <button type="button" class="action-icon-btn" id="landing-theme-toggle" title="Açık / Koyu Tema" aria-label="Açık veya Koyu Tema Değiştir" onclick="if(typeof toggleTheme==='function'){toggleTheme();}else{var h=document.documentElement;var n=h.getAttribute('data-theme')==='light'?'dark':'light';h.setAttribute('data-theme',n);try{localStorage.setItem('gustogo-theme',n);}catch(e){}var ic=document.getElementById('landing-theme-ico');if(ic)ic.className=n==='light'?'fa-solid fa-moon':'fa-solid fa-sun';}">
                <i class="fa-solid fa-sun" id="landing-theme-ico"></i>
            </button>
            <a href="<?= $request->url('/login') ?>" class="nav-auth-btn nav-auth-login" id="landing-btn-login-nav">
                <i class="fa-solid fa-right-to-bracket"></i> Giriş Yap
            </a>
            <a href="<?= $request->url('/register') ?>" class="nav-auth-btn nav-auth-register" id="landing-btn-register-nav">
                <i class="fa-solid fa-user-plus"></i> Kayıt Ol
            </a>
            <a href="<?= $request->url('/login') ?>" class="landing-nav-cta" id="landing-btn-main-cta">
                <i class="fa-solid fa-burger"></i> Giriş Yap / Sipariş Ver
            </a>
        </div>
    </nav>

    <!-- 380vh Burger Hero Scrollytelling Section -->
    <section id="burger-scrolly-container">
        <div id="burger-sticky-viewport">
            <!-- Scroll Progress Indicator -->
            <div id="burger-scroll-progress-container">
                <div id="burger-scroll-progress-bar"></div>
            </div>

            <!-- Soft Warm Radial Spotlight -->
            <div id="burger-spotlight"></div>

            <!-- Floating Ambient Spice/Sesame Particles -->
            <div class="burger-ambient-particles">
                <div class="burger-ambient-particle" style="width: 6px; height: 6px; top: 22%; left: 18%; animation-delay: 0s;"></div>
                <div class="burger-ambient-particle" style="width: 8px; height: 8px; top: 40%; left: 82%; animation-delay: 1.5s;"></div>
                <div class="burger-ambient-particle" style="width: 5px; height: 5px; top: 68%; left: 24%; animation-delay: 2.2s;"></div>
                <div class="burger-ambient-particle" style="width: 7px; height: 7px; top: 32%; left: 74%; animation-delay: 3.1s;"></div>
                <div class="burger-ambient-particle" style="width: 6px; height: 6px; top: 78%; left: 78%; animation-delay: 0.8s;"></div>
            </div>

            <!-- Dynamic Turkish Micro-Copy Glass Badge -->
            <div id="burger-microcopy-badge">
                <span class="burger-badge-tag">CRAVELO LEZZET SIRRI</span>
                <h3 id="burger-badge-title">Taze Brioche Ekmeği</h3>
                <p id="burger-badge-subtitle">Tereyağlı, altın sarısı fırın bazı</p>
            </div>

            <!-- Centered Burger Assembly Stage -->
            <div id="burger-stage">
                <!-- Layer 1 (0% scroll, z-index: 1): Toasted golden brioche bottom bun -->
                <div class="burger-layer" data-layer="1">
                    <img src="<?= $request->asset('img/hero/layer-1-bun-bottom.svg') ?>" alt="Taze Brioche Ekmeği (Alt Taban)">
                </div>

                <!-- Layer 2 (15% scroll, z-index: 2): Crisp wavy iceberg lettuce -->
                <div class="burger-layer" data-layer="2">
                    <img src="<?= $request->asset('img/hero/layer-2-lettuce.svg') ?>" alt="Çıtır Taze Kıvırcık Marul">
                </div>

                <!-- Layer 3 (30% scroll, z-index: 3): Flame-grilled beef patty -->
                <div class="burger-layer" data-layer="3">
                    <img src="<?= $request->asset('img/hero/layer-3-patty.svg') ?>" alt="%100 Dana Antrikot Köfte">
                </div>

                <!-- Layer 4 (45% scroll, z-index: 4): Melted cheddar cheese slice -->
                <div class="burger-layer" data-layer="4">
                    <img src="<?= $request->asset('img/hero/layer-4-cheese.svg') ?>" alt="Erimiş Cheddar">
                </div>

                <!-- Layer 5 (60% scroll, z-index: 5): Fresh thick-cut heirloom tomatoes -->
                <div class="burger-layer" data-layer="5">
                    <img src="<?= $request->asset('img/hero/layer-5-tomatoes.svg') ?>" alt="Taze Tarla Domatesleri">
                </div>

                <!-- Layer 6 (75% scroll, z-index: 6): Caramelized onions & dill pickles -->
                <div class="burger-layer" data-layer="6">
                    <img src="<?= $request->asset('img/hero/layer-6-onions-pickles.svg') ?>" alt="Karamelize Soğan ve Kornişon Turşu">
                </div>

                <!-- Layer 7 (90% scroll, z-index: 7): Sesame brioche top bun -->
                <div class="burger-layer" data-layer="7">
                    <img src="<?= $request->asset('img/hero/layer-7-bun-top.svg') ?>" alt="Taze Brioche Ekmeği (Üst Taç)">
                </div>

                <!-- Atmospheric Steam (above patty) -->
                <div id="burger-steam">
                    <img src="<?= $request->asset('img/hero/steam-wisps.svg') ?>" alt="Sıcak Buhar">
                </div>
            </div>

            <!-- Final State (100% scroll): Prominent CTA Button & Quick Search -->
            <div id="burger-final-cta-container">
                <a href="<?= $request->url('/login') ?>" id="btn-hero-order">
                    <i class="fa-solid fa-burger"></i> Hemen Sipariş Ver
                </a>
                <div id="hero-address-search">
                    <i class="fa-solid fa-location-dot"></i>
                    <input type="text" id="hero-address-input" placeholder="Teslimat adresinizi veya restoran arayın...">
                    <button type="button" id="btn-hero-search">Keşfet</button>
                </div>
            </div>

            <!-- Scroll Down Hint Indicator -->
            <div class="burger-scroll-hint">
                <i class="fa-solid fa-angles-down"></i>
                <span>Aşağı Kaydırın • Burger Katmanlarını Keşfedin</span>
            </div>
        </div>
    </section>

    <!-- Post-Hero Showcases Section -->
    <section class="landing-content-section" id="campaigns">
        <div class="landing-content-wrapper">
            <!-- Bento Live Delivery Metrics Strip -->
            <div class="landing-metrics-grid">
                <div class="landing-metric-card">
                    <div class="landing-metric-top"><i class="fa-solid fa-bag-shopping"></i></div>
                    <div class="landing-metric-val">25.000+</div>
                    <div class="landing-metric-label">Tamamlanan Sipariş</div>
                </div>
                <div class="landing-metric-card">
                    <div class="landing-metric-top is-blue"><i class="fa-solid fa-utensils"></i></div>
                    <div class="landing-metric-val">120+</div>
                    <div class="landing-metric-label">Seçkin Partner Mutfak</div>
                </div>
                <div class="landing-metric-card">
                    <div class="landing-metric-top is-emerald"><i class="fa-solid fa-bolt-lightning"></i></div>
                    <div class="landing-metric-val">~22 Dk</div>
                    <div class="landing-metric-label">Ortalama Teslimat Süresi</div>
                </div>
                <div class="landing-metric-card">
                    <div class="landing-metric-top is-amber"><i class="fa-solid fa-star"></i></div>
                    <div class="landing-metric-val">%99.8</div>
                    <div class="landing-metric-label">Müşteri Memnuniyeti</div>
                </div>
            </div>

            <!-- 4-Role Cockpit Showcase -->
            <div class="landing-features" style="margin-bottom: 68px;">
                <div class="landing-section-head">
                    <span class="landing-section-eyebrow">
                        <i class="fa-solid fa-bolt"></i> Uçtan Uca Entegre Yemek & Kurye Ekosistemi
                    </span>
                    <h2>4 Ayrı Rol, 4 Özel Yönetim Kokpiti</h2>
                    <p>Cravelo rol tabanlı mimarisiyle müşteri, mutfak, kurye ve yönetici için sıfır karmaşa ile optimize edilmiş ayrı kokpitler sunar.</p>
                </div>

                <div class="landing-roles-grid">
                    <!-- Customer Role Showcase -->
                    <article class="landing-role-card">
                        <div class="landing-role-head">
                            <div class="landing-role-icon is-emerald"><i class="fa-solid fa-bag-shopping"></i></div>
                            <span class="landing-role-tag">Müşteri</span>
                        </div>
                        <h3 class="landing-role-title">Müşteri Paneli</h3>
                        <p class="landing-role-desc">
                            Akıllı kategori filtreleri, Gel-Al (%20 indirim) veya Kurye modu, akıllı ürün özelleştirici ve 5 adımlı canlı sipariş takibi.
                        </p>
                        <div class="landing-role-footer">
                            <span class="landing-role-pill"><i class="fa-solid fa-check"></i> Gel-Al %20 & Cüzdan</span>
                            <a href="<?= $request->url('/login') ?>?demo=ahmet_musteri" class="landing-role-link">Dene <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </article>

                    <!-- Restaurant Role Showcase -->
                    <article class="landing-role-card">
                        <div class="landing-role-head">
                            <div class="landing-role-icon is-amber"><i class="fa-solid fa-utensils"></i></div>
                            <span class="landing-role-tag">Mutfak KDS</span>
                        </div>
                        <h3 class="landing-role-title">Restoran / KDS</h3>
                        <p class="landing-role-desc">
                            Canlı Mutfak Ekranı (KDS) ile gelen siparişleri tek dokunuşla onaylama, hazırlık sayacı, stok kontrolü ve kuryeye devir.
                        </p>
                        <div class="landing-role-footer">
                            <span class="landing-role-pill"><i class="fa-solid fa-check"></i> Anlık Durum İlerletme</span>
                            <a href="<?= $request->url('/login') ?>?demo=restoran_lezzet" class="landing-role-link">Dene <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </article>

                    <!-- Courier Role Showcase -->
                    <article class="landing-role-card">
                        <div class="landing-role-head">
                            <div class="landing-role-icon is-cyan"><i class="fa-solid fa-motorcycle"></i></div>
                            <span class="landing-role-tag">Kurye Kokpiti</span>
                        </div>
                        <h3 class="landing-role-title">Kurye Kokpiti</h3>
                        <p class="landing-role-desc">
                            Haversine küresel mesafe ve iş yükü puanlamasıyla akıllı görev ataması, canlı Kadıköy harita rotası ve tek tıkla teslimat.
                        </p>
                        <div class="landing-role-footer">
                            <span class="landing-role-pill"><i class="fa-solid fa-check"></i> Canlı Rota & Telemetri</span>
                            <a href="<?= $request->url('/login') ?>?demo=kurye_ali" class="landing-role-link">Dene <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </article>

                    <!-- Admin Role Showcase -->
                    <article class="landing-role-card">
                        <div class="landing-role-head">
                            <div class="landing-role-icon is-rose"><i class="fa-solid fa-shield-halved"></i></div>
                            <span class="landing-role-tag">Yönetim Merkezi</span>
                        </div>
                        <h3 class="landing-role-title">Yönetici Merkezi</h3>
                        <p class="landing-role-desc">
                            Mustafa Güreşir tam yetkili denetim kokpiti, şehir harita radarı, kurye iş yükü barları, atomik audit log ve canlı rol önizleme.
                        </p>
                        <div class="landing-role-footer">
                            <span class="landing-role-pill"><i class="fa-solid fa-check"></i> Simülasyon & Denetim</span>
                            <a href="<?= $request->url('/login') ?>?demo=admin_mustafa" class="landing-role-link">Dene <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Interactive Demo Credentials Quick-Reference Card -->
            <div class="landing-demo-box" id="landing-demo-guide">
                <div id="landing-demo-box">
                    <span class="landing-section-eyebrow">
                        <i class="fa-solid fa-key"></i> Tek Tıkla Canlı Önizleme
                    </span>
                    <h3 style="margin: 10px 0 8px; font-size: 24px; font-weight: 900; letter-spacing: -0.5px; color: var(--text);">
                        Hızlı Demo Girişi ile Rolleri Deneyin
                    </h3>
                    <p style="margin: 0 0 22px; font-size: 14.5px; color: var(--text-2);">
                        Aşağıdaki demo hesaplarından birine tıklayarak otomatik doldurulmuş giriş ekranına geçin. Ortak parola:
                        <code class="landing-demo-pass-code">hash123</code>
                    </p>

                    <div class="landing-demo-chips">
                        <a href="<?= $request->url('/login') ?>?demo=admin_mustafa" class="landing-demo-chip is-rose">
                            <i class="fa-solid fa-crown"></i> admin_mustafa <span>Yönetici</span>
                        </a>
                        <a href="<?= $request->url('/login') ?>?demo=ahmet_musteri" class="landing-demo-chip is-emerald">
                            <i class="fa-solid fa-bag-shopping"></i> ahmet_musteri <span>Müşteri</span>
                        </a>
                        <a href="<?= $request->url('/login') ?>?demo=restoran_lezzet" class="landing-demo-chip is-amber">
                            <i class="fa-solid fa-fire-burner"></i> restoran_lezzet <span>Restoran</span>
                        </a>
                        <a href="<?= $request->url('/login') ?>?demo=kurye_ali" class="landing-demo-chip is-cyan">
                            <i class="fa-solid fa-motorcycle"></i> kurye_ali <span>Kurye</span>
                        </a>
                    </div>

                    <div class="landing-demo-actions">
                        <a href="<?= $request->url('/login') ?>" class="landing-nav-cta" id="landing-demo-login-btn">
                            <i class="fa-solid fa-right-to-bracket"></i> Giriş Yaparak Bir Rol Seçin
                        </a>
                        <a href="<?= $request->url('/login') ?>" id="btn-landing-demo-login" style="display:none;">Giriş Ekranına Git</a>
                        <a href="<?= $request->url('/register') ?>" class="nav-auth-btn nav-auth-login" id="landing-demo-register-btn">
                            <i class="fa-solid fa-user-plus"></i> Yeni Kayıt Oluştur
                        </a>
                    </div>
                </div>
            </div>

            <!-- Courier Recruitment Banner -->
            <div class="landing-recruitment-banner" id="courier-apply">
                <div class="landing-banner-content">
                    <div class="landing-banner-eyebrow">
                        <i class="fa-solid fa-motorcycle"></i> Cravelo Filosuna Katıl
                    </div>
                    <h3 class="landing-banner-title">Cravelo Kuryesi Ol, Özgürce Kazan</h3>
                    <p class="landing-banner-desc">
                        Kendi çalışma saatlerini belirle, yapay zekâ destekli akıllı rota dağılımı ve jet hızında haftalık ödemelerle Cravelo teslimat filosunda yerini al.
                    </p>
                    <div class="landing-perk-row">
                        <span class="landing-perk-chip"><i class="fa-solid fa-clock"></i> Esnek Saatler</span>
                        <span class="landing-perk-chip"><i class="fa-solid fa-wallet"></i> Haftalık Anında Ödeme</span>
                        <span class="landing-perk-chip"><i class="fa-solid fa-route"></i> Akıllı Rota HUD</span>
                    </div>
                </div>
                <div>
                    <a href="<?= $request->url('/login') ?>?demo=kurye_ali" class="landing-nav-cta" style="padding: 15px 34px; font-size: 15px;">
                        <i class="fa-solid fa-motorcycle"></i> Kurye Kokpitini Dene
                    </a>
                </div>
            </div>

            <!-- Customer Testimonials -->
            <div style="margin-bottom: 24px;">
                <div class="landing-section-head">
                    <span class="landing-section-eyebrow">
                        <i class="fa-solid fa-heart"></i> Gerçek Kullanıcı Deneyimleri
                    </span>
                    <h2>Müşterilerimiz Ne Diyor?</h2>
                    <p>Kadıköy ve çevresindeki binlerce lezzet tutkunu ve partner mutfağın Cravelo yorumları</p>
                </div>

                <div class="landing-testimonials-grid">
                    <article class="landing-testimonial-card">
                        <div class="landing-stars" aria-label="5 üzerinden 5 yıldız">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="landing-quote">"Kadıköy Moda'da 18 dakikada burgerim sıcacık geldi! Kurye canlı haritada sokak sokak izlenebiliyor, hız ve arayüz inanılmaz."</p>
                        <div class="landing-author">
                            <span class="landing-author-avatar">EK</span>
                            <div>
                                <div class="landing-author-name">Elif K.</div>
                                <div class="landing-author-loc">Moda, Kadıköy</div>
                            </div>
                        </div>
                    </article>

                    <article class="landing-testimonial-card">
                        <div class="landing-stars" aria-label="5 üzerinden 5 yıldız">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="landing-quote">"KDS mutfak paneli sayesinde en yoğun akşam saatlerinde bile siparişler hiç karışmıyor. Puan bazlı kurye ataması çok dengeli."</p>
                        <div class="landing-author">
                            <span class="landing-author-avatar" style="background: linear-gradient(135deg, #F59E0B, #D97706);">ŞM</span>
                            <div>
                                <div class="landing-author-name">Şef Mehmet</div>
                                <div class="landing-author-loc">Partner Restoran • Kadıköy</div>
                            </div>
                        </div>
                    </article>

                    <article class="landing-testimonial-card">
                        <div class="landing-stars" aria-label="5 üzerinden 5 yıldız">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="landing-quote">"Gel-Al modundaki %20 anında indirim ve her siparişte biriken Cravelo Puan cüzdanı çok avantajlı, neredeyse her gün kullanıyorum."</p>
                        <div class="landing-author">
                            <span class="landing-author-avatar" style="background: linear-gradient(135deg, #0BBF80, #059669);">AY</span>
                            <div>
                                <div class="landing-author-name">Ahmet Y.</div>
                                <div class="landing-author-loc">Rıhtım, Kadıköy</div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- 4-Column Modern Footer -->
    <footer class="site-footer">
        <div class="site-footer-inner">
            <div class="site-footer-grid">
                <div>
                    <a href="<?= $request->url('/') ?>" class="site-footer-brand">
                        <span class="site-footer-brand-icon"><i class="fa-solid fa-burger"></i></span>
                        <span>Crave<em class="landing-brand-accent">lo</em></span>
                    </a>
                    <p class="site-footer-desc">
                        Mahallenizin en seçkin lezzetlerini süpersonik hızda kapınıza ulaştıran akıllı yemek sipariş ve kurye lojistik platformu.
                    </p>
                </div>

                <div>
                    <h4 class="site-footer-col-title">Keşfet</h4>
                    <ul class="site-footer-list">
                        <li><a href="#burger-scrolly-container">Menü & Restoranlar</a></li>
                        <li><a href="#campaigns">Kampanyalar</a></li>
                        <li><a href="#campaigns">Gel-Al %20 İndirim</a></li>
                        <li><a href="<?= $request->url('/login') ?>">Cravelo Puan Cüzdanı</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="site-footer-col-title">İş Ortaklığı</h4>
                    <ul class="site-footer-list">
                        <li><a href="#courier-apply">Cravelo Kuryesi Ol</a></li>
                        <li><a href="<?= $request->url('/login') ?>?demo=restoran_lezzet">Restoran Mutfak (KDS)</a></li>
                        <li><a href="<?= $request->url('/login') ?>?demo=admin_mustafa">Yönetici Denetim Merkezi</a></li>
                        <li><a href="#courier-apply">Akıllı Filo Yönetimi</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="site-footer-col-title">Güvenlik & İletişim</h4>
                    <ul class="site-footer-list">
                        <li><span><i class="fa-solid fa-location-dot" style="color: var(--primary-2, #FF6B35);"></i> Kadıköy, İstanbul</span></li>
                        <li><span><i class="fa-solid fa-headset" style="color: var(--success, #0BBF80);"></i> 7/24 Canlı Destek</span></li>
                        <li><span><i class="fa-solid fa-shield-halved" style="color: var(--blue, #2F8EFF);"></i> 256-Bit SSL & Bcrypt Güvenliği</span></li>
                    </ul>
                </div>
            </div>

            <div class="site-footer-bottom">
                <div>© 2026 Cravelo. Tüm hakları saklıdır. — Akıllı Yemek Teslimat ve Kurye Takip Teknolojileri A.Ş.</div>
                <div class="site-footer-legal">
                    <a href="#">Kullanım Koşulları</a>
                    <a href="#">KVKK & Gizlilik</a>
                    <a href="#">Çerez Politikası</a>
                </div>
            </div>
        </div>
    </footer>
</div>
<script>
    (function () {
        var ic = document.getElementById('landing-theme-ico');
        if (ic) {
            var t = document.documentElement.getAttribute('data-theme') || 'dark';
            ic.className = t === 'light' ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
        }
    })();
</script>
