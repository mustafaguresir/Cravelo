    <!-- ======================================================== -->
    <!-- 1. MÜŞTERİ PANELİ (CRAVELO CUSTOMER PORTAL)              -->
    <!-- ======================================================== -->
    <div id="view-customer">
        <!-- 1. Category Discovery Squircle Tiles (DoorDash / Wolt Style) -->
        <div class="stories-bar" id="stories-bar" role="tablist" aria-label="Mutfak Kategorileri">
            <div class="story-item" data-cat="Burger" onclick="filterCategory('Burger')" title="Burger Restoranları" role="tab" tabindex="0">
                <div class="story-ring"><img class="story-img" src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=150&q=80" alt="Burger"></div>
                <span class="story-label">Burgerler</span>
            </div>
            <div class="story-item" data-cat="Kebap" onclick="filterCategory('Kebap')" title="Kebap & Dürüm Restoranları" role="tab" tabindex="0">
                <div class="story-ring"><img class="story-img" src="https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=150&q=80" alt="Kebap"></div>
                <span class="story-label">Kebap & Dürüm</span>
            </div>
            <div class="story-item" data-cat="Pizza" onclick="filterCategory('Pizza')" title="Pizzacılar" role="tab" tabindex="0">
                <div class="story-ring"><img class="story-img" src="https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=150&q=80" alt="Pizza"></div>
                <span class="story-label">Pizzalar</span>
            </div>
            <div class="story-item" data-cat="Sushi" onclick="filterCategory('Sushi')" title="Sushi Bar Restoranları" role="tab" tabindex="0">
                <div class="story-ring"><img class="story-img" src="https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&w=150&q=80" alt="Sushi"></div>
                <span class="story-label">Sushi Bar</span>
            </div>
            <div class="story-item" data-cat="Tatlı" onclick="filterCategory('Tatlı')" title="Tatlı & Waffle Mekanları" role="tab" tabindex="0">
                <div class="story-ring"><img class="story-img" src="https://images.unsplash.com/photo-1562376552-0d160a2f238d?auto=format&fit=crop&w=150&q=80" alt="Tatlı"></div>
                <span class="story-label">Tatlı & Waffle</span>
            </div>
            <div class="story-item" data-cat="Tavuk" onclick="filterCategory('Tavuk')" title="Çıtır Tavuk Restoranları" role="tab" tabindex="0">
                <div class="story-ring"><img class="story-img" src="https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58?auto=format&fit=crop&w=150&q=80" alt="Tavuk"></div>
                <span class="story-label">Çıtır Tavuk</span>
            </div>
            <div class="story-item" data-cat="Balık" onclick="filterCategory('Balık')" title="Deniz Ürünleri & Balıkçılar" role="tab" tabindex="0">
                <div class="story-ring"><img class="story-img" src="https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=150&q=80" alt="Balık"></div>
                <span class="story-label">Deniz Ürünleri</span>
            </div>
        </div>

        <!-- 2. Editorial Hero Banner (Uber Eats & DoorDash Standard) -->
        <section class="hero-banner" aria-label="Cravelo Öne Çıkanlar">
            <div class="hero-content">
                <div class="hero-tag">
                    <span class="hero-tag-pulse"></span>
                    <i class="fa-solid fa-bolt"></i> Cravelo Express • ~22 Dk Sıcak Teslimat
                </div>
                <h1 class="hero-title">
                    Canın ne çekiyorsa,<br>
                    <span class="hero-highlight">en hızlı kuryeyle</span> kapında!
                </h1>
                <p class="hero-desc">
                    Kadıköy'ün en seçkin mutfaklarından taptaze lezzetler. Akıllı kurye rota motoruyla yemeğin soğumadan dakikalar içinde masanda.
                </p>
                <div class="hero-actions">
                    <button type="button" class="hero-btn" onclick="scrollToRestaurants()">
                        <span>Lezzetleri Keşfet</span> <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    <div class="hero-quick-perks">
                        <span class="perk-chip"><i class="fa-solid fa-person-walking" style="color:var(--primary);"></i> Gel-Al %20 İndirim</span>
                        <span class="perk-chip"><i class="fa-solid fa-leaf" style="color:var(--success);"></i> Doğa Dostu</span>
                    </div>
                </div>
            </div>
            <div class="hero-img-wrap">
                <img class="hero-main-img" src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=640&q=80" alt="Cravelo Gurme Lezzetler">
                <div class="hero-floating-badge hero-badge-eta">
                    <div class="hero-badge-icon"><i class="fa-solid fa-motorcycle"></i></div>
                    <div>
                        <div class="hero-badge-label">Ortalama Teslimat</div>
                        <div class="hero-badge-val">~22 Dakika</div>
                    </div>
                </div>
                <div class="hero-floating-badge hero-badge-rating">
                    <i class="fa-solid fa-star" style="color:#F59E0B;font-size:18px;"></i>
                    <div>
                        <div class="hero-badge-val">4.9 / 5.0</div>
                        <div class="hero-badge-label">14.8k+ Değerlendirme</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Flash Deals Section -->
        <section id="flash-deals-section">
            <div class="sec-head">
                <div>
                    <div class="sec-title"><i class="fa-solid fa-bolt" style="color:var(--danger);"></i> Flaş İndirimler & Süper Fırsatlar</div>
                    <div class="sec-sub">Sınırlı süre için kaçırılmayacak şef fırsatları</div>
                </div>
                <div class="flash-timer-pill">
                    <i class="fa-regular fa-clock" style="color:var(--primary);"></i>
                    <span>Kalan Süre:</span>
                    <strong id="flash-timer" style="color:var(--primary);font-weight:900;font-variant-numeric:tabular-nums;">02:44:18</strong>
                </div>
            </div>
            <div class="flash-grid" id="flash-grid"></div>
        </section>

        <!-- 4. Chef Picks / Popular Showcase Section -->
        <section id="popular-showcase-section">
            <div class="sec-head">
                <div>
                    <div class="sec-title"><i class="fa-solid fa-fire" style="color:var(--primary);"></i> Şefin Seçimleri & Çok Satanlar</div>
                    <div class="sec-sub">Kadıköy'de en çok sipariş edilen ve tam puan alan tabaklar</div>
                </div>
            </div>
            <div class="pop-food-grid" id="pop-food-grid"></div>
        </section>

        <!-- 5. Restaurants & Smart Sort Section -->
        <section id="rest-section">
            <div class="sec-head">
                <div>
                    <div class="sec-title" id="rest-section-title"><i class="fa-solid fa-store" style="color:var(--primary);"></i> Çevrendeki Restoranlar</div>
                    <div class="sec-sub">Kadıköy teslimat adresine olan yakınlık ve hız skoruna göre sıralanıyor</div>
                </div>
                <div class="badge badge-musait" id="rest-count-badge">7 Restoran açık</div>
            </div>

            <!-- Category Filter Pills -->
            <div class="cat-scroll" id="cat-pills"></div>

            <!-- Smart Sort Chips -->
            <div class="sort-bar" role="group" aria-label="Restoran Sıralama">
                <button type="button" class="sort-chip active" id="sort-fast" onclick="setSortMode('fast')">
                    <i class="fa-solid fa-bolt"></i> En Hızlı Teslimat
                </button>
                <button type="button" class="sort-chip" id="sort-rating" onclick="setSortMode('rating')">
                    <i class="fa-solid fa-star"></i> En Yüksek Puan
                </button>
                <button type="button" class="sort-chip" id="sort-promo" onclick="setSortMode('promo')">
                    <i class="fa-solid fa-tag"></i> Kampanyalılar
                </button>
                <button type="button" class="sort-chip" id="sort-delivery" onclick="setSortMode('delivery')">
                    <i class="fa-solid fa-motorcycle"></i> Düşük Teslimat Ücreti
                </button>
            </div>

            <div class="rest-grid" id="rest-grid"></div>
        </section>

        <!-- 6. Uber Eats-Style Restaurant Menu & Sticky Cart Detail View -->
        <section id="menu-section" class="customer-grid" style="display:none;">
            <div>
                <button type="button" class="btn btn-outline" style="margin-bottom:18px;border-radius:9999px;padding:9px 18px;" onclick="showRestaurants()">
                    <i class="fa-solid fa-arrow-left"></i> Restoranlara Dön
                </button>

                <div class="menu-header">
                    <img class="menu-header-img" id="mh-img" src="" alt="Restoran Görseli">
                    <div style="flex:1;min-width:0;">
                        <div class="menu-header-name" id="mh-name">Restoran Adı</div>
                        <div class="menu-header-sub">
                            <span class="mh-badge"><i class="fa-solid fa-star" style="color:var(--yellow);"></i> <strong id="mh-rating">4.8</strong></span>
                            <span class="mh-badge"><i class="fa-solid fa-clock" style="color:var(--primary);"></i> <strong id="mh-time">25-35</strong> dk</span>
                            <span class="mh-badge"><i class="fa-solid fa-location-dot" style="color:var(--blue);"></i> <strong id="mh-loc">Moda, Kadıköy</strong></span>
                        </div>
                    </div>
                </div>

                <div class="food-grid" id="food-grid"></div>
            </div>

            <!-- Sticky Checkout Cart & Live Order Banner Sidebar -->
            <aside class="cart-sticky">
                <div class="cart-box">
                    <div class="cart-head">
                        <div class="cart-title">
                            <i class="fa-solid fa-bag-shopping" style="color:var(--primary);"></i>
                            <span>Sepetim</span>
                        </div>
                        <span class="cart-count-badge" id="cart-badge">0</span>
                    </div>

                    <!-- Group Order & Delivery Type Bar -->
                    <div class="cart-top-pills">
                        <span class="cart-mode-indicator" id="cart-mode-indicator"><i class="fa-solid fa-motorcycle"></i> Adrese Teslim</span>
                        <button type="button" class="btn-group-pill" id="btn-group-cart" onclick="openGroupOrderModal()" title="Arkadaşlarınla ortak sepet oluştur">
                            <i class="fa-solid fa-users"></i> <span id="group-cart-label">Ortak Sepet</span>
                        </button>
                    </div>

                    <div class="cart-body">
                        <div style="font-size:12.5px;color:var(--text-3);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                            <i class="fa-solid fa-store" style="color:var(--primary);font-size:11.5px;"></i>
                            <span>Restoran:</span>
                            <strong id="cart-from" style="color:var(--text);">—</strong>
                        </div>

                        <div class="cart-empty" id="cart-empty">
                            <i class="fa-solid fa-basket-shopping"></i>
                            <p>Sepetiniz şu anda boş.<br>Menüden dilediğiniz lezzetleri ekleyin.</p>
                        </div>

                        <div id="cart-items" style="display:none;"></div>

                        <!-- Eco-friendly Delivery Option in Cart -->
                        <div class="cart-eco-row" id="cart-eco-area" style="display:none;">
                            <label class="eco-checkbox-label">
                                <input type="checkbox" id="cart-eco-check" checked onchange="toggleEcoFriendly(this.checked)">
                                <span><i class="fa-solid fa-leaf" style="color:var(--success);"></i> Doğa Dostu Yeşil Teslimat (Çatal/Bıçak İstemiyorum)</span>
                            </label>
                        </div>

                        <!-- Promo Coupon Field -->
                        <div class="coupon-box" id="cart-coupon-area" style="display:none;">
                            <input type="text" class="coupon-input" id="cart-coupon-code" placeholder="KUPON KODU (örn: CRAVELO30)">
                            <button type="button" class="coupon-btn" onclick="applyCartCouponInput()">Uygula</button>
                        </div>

                        <!-- Cart Totals Summary -->
                        <div class="cart-summary" id="cart-summ" style="display:none;">
                            <div class="summ-row"><span>Ara Toplam</span><span id="s-sub">0.00 ₺</span></div>
                            <div class="summ-row discount" id="s-discount-row" style="display:none;"><span>İndirim</span><span id="s-disc">-0.00 ₺</span></div>
                            <div class="summ-row"><span>Teslimat</span><span id="s-del">0.00 ₺</span></div>
                            <div class="summ-row"><span>Tahmini Süre</span><span id="s-time">~0 dk</span></div>
                            <div class="summ-row total"><span>Ödenecek Tutar</span><span id="s-total">0.00 ₺</span></div>
                        </div>

                        <button type="button" class="btn-order" id="btn-order" disabled onclick="openCheckoutModal()">
                            <i class="fa-solid fa-credit-card"></i> <span>Siparişi Tamamla & Öde</span>
                        </button>
                    </div>
                </div>

                <!-- Active Order Quick Notification Banner -->
                <div id="active-order-floating-banner" class="panel-card" style="display:none;border-color:var(--primary);padding:18px;margin-top:16px;margin-bottom:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="live-pulse-dot"></span>
                            <span style="font-weight:800;font-size:13.5px;color:var(--text);" id="menu-active-order-title">Aktif Siparişiniz Var</span>
                        </div>
                        <span class="status-pill primary" id="menu-active-order-status">Yolda</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-2);margin-bottom:14px;line-height:1.45;" id="menu-active-order-courier">Kuryeniz siparişinizi teslim etmek üzere yola çıktı.</div>
                    <button type="button" class="btn btn-sm btn-primary" onclick="openOrdersTab()" style="width:100%;border-radius:9999px;padding:10px 16px;">
                        <i class="fa-solid fa-map-location-dot"></i> Kuryeyi Canlı Takip Et
                    </button>
                </div>
            </aside>
        </section>
    </div>
